<?php

namespace RozbehSharahi\Rest3\Normalizer;

use RozbehSharahi\Rest3\Exception;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class DomainObjectNormalizer implements DomainObjectNormalizerInterface
{

    /**
     * @var Normalizer
     */
    protected $normalizer;

    /**
     * @var array
     */
    protected $excludedAttributes = [];

    /**
     * @var DataMapper
     */
    protected $dataMapper;

    /**
     * @param DataMapper $dataMapper
     */
    public function injectDataMapper(DataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return 40;
    }

    /**
     * @param DomainObjectInterface $model
     * @return bool
     */
    public function canNormalize(DomainObjectInterface $model): bool
    {
        return true;
    }

    /**
     * @param $normalizer
     * @return DomainObjectNormalizerInterface
     */
    public function setNormalizer($normalizer)
    {
        $this->normalizer = $normalizer;
        return $this;
    }

    /**
     * @param DomainObjectInterface $model
     * @param array $include
     * @return array
     */
    public function normalizeDomainObject(DomainObjectInterface $model, array $include = [])
    {
        $data = [];

        $data['id'] = $model->getUid();
        $data['type'] = $this->normalizer->getTypeByModelName(get_class($model));
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

            if (in_array($propertyName, $this->excludedAttributes)) {
                continue;
            }

            // Only non relations allowed
            if ($propertyMap && $propertyMap->getTypeOfRelation() === ColumnMap::RELATION_NONE) {
                $attributes[$propertyName] = $this->normalizer->normalizeValue($propertyValue);
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

            $this->normalizer->addModelToIncludeStore(
                $relationModel,
                $this->getNextLevelIncludeFor($relationName, $include)
            );
            return [
                'data' => [
                    'id' => $relationModel->getUid(),
                    'type' => $this->normalizer->getTypeByModelName(get_class($relationModel)),
                ]
            ];
        }

        // Has many relations
        $hasManyRelation = in_array($propertyMap->getTypeOfRelation(), [
            ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY,
            ColumnMap::RELATION_HAS_MANY
        ]);
        if ($hasManyRelation && $propertyValue instanceof ObjectStorage) {
            return [
                'data' => array_map(function (DomainObjectInterface $relationModel) use ($include, $relationName) {
                    $this->normalizer->addModelToIncludeStore(
                        $relationModel,
                        $this->getNextLevelIncludeFor($relationName, $include)
                    );
                    return [
                        'id' => $relationModel->getUid(),
                        'type' => $this->normalizer->getTypeByModelName(get_class($relationModel)),
                    ];
                }, $propertyValue->toArray())
            ];
        }

        throw Exception::create()->addError('Could not normalize relation ' . get_class($model) . '::' . $relationName);
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

}
