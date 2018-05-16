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
     * @return DomainObjectInterface
     * @throws Exception
     */
    public function writeIntoModelByRequestData(DomainObjectInterface $model, array $requestData): DomainObjectInterface
    {
        $modelName = get_class($model);
        $dataMap = $this->dataMapper->getDataMap($modelName);
        $propertyMappingConfiguration = $this->buildPropertyMappingConfiguration();

        // Write attributes
        foreach ($requestData['data']['attributes'] ?: [] as $attributeName => $attributeValue) {
            if (!ObjectAccess::isPropertySettable($model, $attributeName)) {
                throw Exception::create()
                    ->addError("Property `$attributeName` does not exist on " . get_class($model));
            }
            ObjectAccess::setProperty($model, $attributeName, $attributeValue);
        }

        // Write relations
        foreach ($requestData['data']['relationships'] ?: [] as $attributeName => $attributeValue) {
            $relationType = $dataMap->getColumnMap($attributeName)->getTypeOfRelation();

            if (is_null($relationType)) {
                throw Exception::create()->addError($attributeName . ' is not of type relation');
            }

            if (!ObjectAccess::isPropertySettable($model, $attributeName)) {
                throw Exception::create()
                    ->addError("Relation `$attributeName` can not be set throw setter on " . get_class($model));
            }

            // 1:n
            if ($relationType === ColumnMap::RELATION_HAS_MANY) {
                throw Exception::create()
                    ->addError('1:n relation can not be set. Please set by foreign table');
            }

            // n:1
            if ($relationType === ColumnMap::RELATION_HAS_ONE) {
                $targetType = $this->dataMapper->getType(get_class($model), $attributeName);
                $value = $this->propertyMapper->convert($attributeValue, $targetType, $propertyMappingConfiguration);
                ObjectAccess::setProperty($model, $attributeName, $value);
            }

            // m:n
            if ($relationType === ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
                $itemType = $this->dataMapper->getType(get_class($model), $attributeName);
                $targetType = ObjectStorage::class . '<' . $itemType . '>';
                $value = $this->propertyMapper->convert($attributeValue, $targetType, $propertyMappingConfiguration);
                ObjectAccess::setProperty($model, $attributeName, $value);
            }
        }

        return $model;
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

}