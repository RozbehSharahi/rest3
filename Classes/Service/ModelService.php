<?php

namespace RozbehSharahi\Rest3\Service;

use RozbehSharahi\Rest3\Exception;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
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
            if (!ObjectAccess::isPropertySettable($model, $attributeName)) {
                throw Exception::create()
                    ->addError("Relation `$attributeName` can not be set on " . get_class($model));
            }
            if ($dataMap->getColumnMap($attributeName)->getTypeOfRelation() !== ColumnMap::RELATION_HAS_ONE) {
                throw Exception::create()
                    ->addError('Currently it is only possible to set has one relation ships');
            }
            $targetType = $this->dataMapper->getType(get_class($model), $attributeName);
            $value = $this->propertyMapper->convert($attributeValue, $targetType, $propertyMappingConfiguration);
            ObjectAccess::setProperty($model, $attributeName, $value);
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