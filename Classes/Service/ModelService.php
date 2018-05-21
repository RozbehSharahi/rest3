<?php

namespace RozbehSharahi\Rest3\Service;

use RozbehSharahi\Rest3\Exception;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationBuilder;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

class ModelService implements SingletonInterface
{

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @param ObjectManager $objectManager
     */
    public function injectObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @var ConfigurationService
     */
    protected $configurationService;

    /**
     * @param ConfigurationService $configurationService
     */
    public function injectConfigurationService(ConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;
    }

    /**
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @param PropertyMapper $propertyMapper
     */
    public function injectPropertyMapper(PropertyMapper $propertyMapper)
    {
        $this->propertyMapper = $propertyMapper;
    }

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
     * @param DomainObjectInterface $model
     * @param array $requestData
     * @param array $excludedProperties
     * @param array $include
     * @return DomainObjectInterface
     * @throws Exception
     */
    public function writeIntoModelByRequestData(
        DomainObjectInterface $model,
        array $requestData,
        array $excludedProperties = [],
        array $include = []
    ): DomainObjectInterface {
        $modelName = get_class($model);
        $dataMap = $this->dataMapper->getDataMap($modelName);
        $propertyMappingConfiguration = $this->buildPropertyMappingConfiguration();

        // Write attributes
        foreach ($requestData['data']['attributes'] ?: [] as $attributeName => $relation) {
            if ((
                !ObjectAccess::isPropertySettable($model, $attributeName) ||
                in_array($attributeName, $excludedProperties)
            )) {
                throw Exception::create()
                    ->addError("Property `$attributeName` can not be set on " . $modelName);
            }
            ObjectAccess::setProperty($model, $attributeName, $relation);
        }

        // Write relations
        foreach ($requestData['data']['relationships'] ?: [] as $attributeName => $relation) {
            $relationType = $dataMap->getColumnMap($attributeName)->getTypeOfRelation();

            // Safe mode
            if((
                $this->configurationService->getSetting('safeModes.setRelations') &&
                !in_array($attributeName,$include)
            )) {
                continue;
            }

            $this->assert(
                !is_null($relationType),
                $attributeName . ' is not of type relation'
            );
            $this->assert(
                ObjectAccess::isPropertySettable($model, $attributeName),
                $attributeName . ' can not be set on' . $modelName
            );
            $this->assert(
                !in_array($attributeName, $excludedProperties),
                $attributeName . ' can not be set on' . $modelName
            );
            $this->assert(
                $relationType !== ColumnMap::RELATION_HAS_MANY,
                '1:n relations can not be set. Please set by foreign table'
            );

            // n:1
            if ($relationType === ColumnMap::RELATION_HAS_ONE) {
                $this->assert(
                    $this->validateHasOneRelation($relation),
                    "Relation `$attributeName` has invalid format and can not be set"
                );

                $targetType = $this->dataMapper->getType($modelName, $attributeName);
                $value = $this->propertyMapper->convert(
                    $relation['data']['id'],
                    $targetType,
                    $propertyMappingConfiguration
                );
                ObjectAccess::setProperty($model, $attributeName, $value);
            }

            // m:n
            if ($relationType === ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
                $this->assert(
                    $this->validateHasManyRelation($relation),
                    "Relation `$attributeName` has invalid format and can not be set"
                );
                $itemType = $this->dataMapper->getType($modelName, $attributeName);
                $targetType = ObjectStorage::class . '<' . $itemType . '>';
                $value = $this->propertyMapper->convert(
                    array_column($relation['data'], 'id'),
                    $targetType,
                    $propertyMappingConfiguration
                );
                ObjectAccess::setProperty($model, $attributeName, $value);
            }
        }

        return $model;
    }

    /**
     * @param array $relation
     * @return bool
     */
    protected function validateHasOneRelation($relation): bool
    {
        return (
            in_array('data', array_keys($relation)) &&
            in_array('id', array_keys($relation['data']))
        );
    }

    /**
     * @param array $relation
     * @return bool
     */
    protected function validateHasManyRelation($relation): bool
    {
        if (!in_array('data', array_keys($relation)) ||
            !is_array($relation['data']) ||
            !$this->isNumericArray($relation['data'])) {
            return false;
        }

        foreach ($relation['data'] as $pointer) {
            if (empty($pointer['id']) || is_array($pointer['id'] || is_object($pointer['id']))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $array
     * @return bool
     */
    protected function isNumericArray(array $array): bool
    {
        return count(array_filter(array_keys($array), 'is_string')) === 0;
    }

    /**
     * @return PropertyMappingConfiguration
     */
    protected function buildPropertyMappingConfiguration()
    {
        /** @var PropertyMappingConfigurationBuilder $builder */
        $builder = $this->objectManager->get(PropertyMappingConfigurationBuilder::class);
        return $builder->build();
    }

    /**
     * @param bool $assertion
     * @param mixed $message
     * @throws Exception
     */
    protected function assert(bool $assertion, $message)
    {
        if (!$assertion) {
            throw Exception::create()->addError($message);
        }
    }

}