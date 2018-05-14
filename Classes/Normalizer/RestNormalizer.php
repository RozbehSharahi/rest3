<?php

namespace RozbehSharahi\Rest3\Normalizer;

use RozbehSharahi\Rest3\Exception;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;


class RestNormalizer
{

    /**
     * @var DataMapper
     */
    protected $dataMapper;

    /**
     * @var array
     */
    protected $includedStore = [];

    /**
     * @param DataMapper $dataMapper
     */
    public function injectDataMapper(DataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
    }

    /**
     * @param mixed $input
     * @param array $include
     * @return array|mixed
     */
    public function normalize($input, $include = [])
    {
        // Simple values
        if (!is_array($input) && !$input instanceof \Traversable && !$input instanceof DomainObjectInterface) {
            return $this->extractValue($input, 'message');
        }

        $data = null;

        // We have to reset always
        $this->includedStore = [];

        // Lists
        if (is_array($input) || $input instanceof \Traversable) {
            // We make sure to have an array
            $input = $input instanceof \Traversable ? array_values(iterator_to_array($input)) : $input;

            $data = array_map(function (DomainObjectInterface $object) use ($include) {
                return $this->extractModel($object, $include);
            }, $input);
        }

        // Single
        if ($input instanceof DomainObjectInterface) {
            $data = $this->extractModel($input, $include);
        }

        // Only data root can have includes
        return [
            'data' => $data,
            'included' => array_values($this->includedStore)
        ];
    }

    /**
     * @param DomainObjectInterface $model
     * @param array $include
     * @return array
     */
    protected function extractModel(DomainObjectInterface $model, array $include = [])
    {
        $data = [];

        $data['id'] = $model->getUid();
        $data['type'] = get_class($model);
        $data['attributes'] = $this->getAttributes($model);

        // Get relations if present
        $data['relationships'] = $this->getRelations($model, $include);

        return $data;
    }

    /**
     * @param DomainObjectInterface $model
     * @return array
     */
    protected function getAttributes($model)
    {
        $attributes = [];
        $className = get_class($model);
        $dataMap = $this->dataMapper->getDataMap(get_class($model));

        foreach ($model->_getProperties() as $propertyName => $propertyValue) {
            $propertyMap = $dataMap->getColumnMap($propertyName);

            // Only non relations allowed
            if ($propertyMap && $propertyMap->getTypeOfRelation() === ColumnMap::RELATION_NONE) {
                $attributes[$propertyName] = $this->extractValue($propertyValue);
            }

            // Additional allowed fields
            if (property_exists($className, 'additionalRestProperties') &&
                in_array($propertyName, $className::$additionalRestProperties)
            ) {
                $attributes[$propertyName] = $model->{'get' . ucfirst($propertyName)}();
            }
        }
        return $attributes;
    }

    /**
     * @param DomainObjectInterface $model
     * @param array $include
     * @return array
     * @throws Exception
     */
    protected function getRelations($model, array $include = [])
    {
        $relations = [];
        foreach ($this->getSameLevelIncludes($include) as $relationName) {

            // Check for being a real relation
            $propertyMap = $this->dataMapper->getDataMap(get_class($model))->getColumnMap($relationName);
            if (is_null($propertyMap) || $propertyMap->getTypeOfRelation() === 'RELATION_NONE') {
                throw Exception::create()->addError("`$relationName` is not a relation of " . get_class($model));
            }

            // Set relation
            $relations[$relationName] = $this->getRelationPointer($model, $relationName, $include);
        }
        return $relations;
    }

    /**
     * @param DomainObjectInterface $model
     * @param string $relationName
     * @param array $include
     * @return array
     * @throws \Exception
     */
    protected function getRelationPointer(DomainObjectInterface $model, string $relationName, array $include = [])
    {
        $modelProperties = $model->_getProperties();
        $propertyValue = $modelProperties[$relationName];
        $propertyMap = $this->dataMapper->getDataMap(get_class($model))->getColumnMap($relationName);

        if ($propertyMap === null) {
            throw new \Exception($relationName . ' does not exist on ' . get_class($model));
        }

        // Has one but no assignment
        if ($propertyMap->getTypeOfRelation() === ColumnMap::RELATION_HAS_ONE &&
            is_null($propertyValue)) {
            // This can be problematic ! due to merge of relations
            return ['data' => null];
        }

        // Has one
        if ($propertyMap->getTypeOfRelation() === ColumnMap::RELATION_HAS_ONE &&
            $propertyValue instanceof DomainObjectInterface) {
            $relationModel = $propertyValue;

            $this->addModelToIncludeStore(
                $relationModel,
                $this->getNextLevelIncludeFor($relationName, $include)
            );
            return [
                'data' => [
                    'id' => $relationModel->getUid(),
                    'type' => get_class($relationModel),
                ]
            ];
        }

        // Has many relations
        if ($propertyMap->getTypeOfRelation() === ColumnMap::RELATION_HAS_MANY &&
            $propertyValue instanceof ObjectStorage) {
            return [
                'data' => array_map(function (DomainObjectInterface $relationModel) use ($include, $relationName) {
                    $this->addModelToIncludeStore(
                        $relationModel,
                        $this->getNextLevelIncludeFor($relationName, $include)
                    );
                    return [
                        'id' => $relationModel->getUid(),
                        'type' => get_class($relationModel),
                    ];
                }, $propertyValue->toArray())
            ];
        }

        return ['data' => null];
    }

    /**
     * @param string $relationName
     * @param array $include
     * @return array
     */
    private function getNextLevelIncludeFor(string $relationName, array $include)
    {
        $nextLevelInclude = array_filter($include, function ($key) use ($relationName) {
            return strpos($key, $relationName . '.') === 0;
        });

        return array_map(function ($key) use ($relationName) {
            return preg_replace('/^' . $relationName . '\.' . '/', '', $key);
        }, $nextLevelInclude);
    }

    /**
     * @param $include
     * @return array
     */
    protected function getSameLevelIncludes($include): array
    {
        return array_filter($include, function ($key) {
            return strpos($key, '.') === false;
        });
    }

    /**
     * Extract simple values that are not models
     *
     * @param mixed $value
     * @param string $wrap
     * @return mixed|string
     */
    protected function extractValue($value, $wrap = null)
    {
        if ($value instanceof \DateTime) {
            $value = $value->format(DATE_ATOM);
        }

        return $wrap ? [$wrap => $value] : $value;
    }

    /**
     * @param DomainObjectInterface $relationModel
     * @return string
     */
    protected function getModelHash(DomainObjectInterface $relationModel): string
    {
        return get_class($relationModel) . '-' . $relationModel->getUid();
    }

    /**
     * Include recursively to store, and take care if already there.
     *
     * This one will merge the include extraction, because it may be requested from different paths,
     * with different relations. And it will also continue the recursion.
     *
     * @param DomainObjectInterface $model
     * @param array $include
     */
    protected function addModelToIncludeStore(DomainObjectInterface $model, array $include)
    {
        $modelHash = $this->getModelHash($model);
        $this->includedStore[$modelHash] = array_replace_recursive(
            $this->includedStore[$modelHash] ?: [],
            $this->extractModel($model, $include)
        );
    }


}