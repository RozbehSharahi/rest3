<?php

namespace RozbehSharahi\Rest3\Normalizer;

use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

class Normalizer
{

    /**
     * @var array
     */
    protected $includedStore = [];

    /**
     * @var DomainObjectNormalizerInterface[]
     */
    protected $domainObjectNormalizers = [];

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
     * Init object
     */
    public function initializeObject()
    {
        /** @var DomainObjectNormalizer $domainObjectNormalizer */
        $domainObjectNormalizer = $this->objectManager->get(DomainObjectNormalizer::class);

        /** @var DomainObjectNormalizer $frontendUserNormalizer */
        $frontendUserNormalizer = $this->objectManager->get(FrontendUserNormalizer::class);

        $this->addDomainObjectNormalizer($domainObjectNormalizer->setNormalizer($this));
        $this->addDomainObjectNormalizer($frontendUserNormalizer->setNormalizer($this));
    }

    /**
     * @param mixed $input
     * @param array $include
     * @return array|mixed
     */
    public function normalize($input, array $include = [])
    {
        // Simple values
        if (!is_array($input) && !$input instanceof \Traversable && !$input instanceof DomainObjectInterface) {
            return $this->normalizeValue($input, 'message');
        }

        $data = null;

        // We have to reset always
        $this->includedStore = [];

        // Lists
        if (is_array($input) || $input instanceof \Traversable) {
            // We make sure to have an array
            $input = $input instanceof \Traversable ? array_values(iterator_to_array($input)) : $input;

            $data = array_map(function (DomainObjectInterface $object) use ($include) {
                return $this->normalizeDomainObject($object, $include);
            }, $input);
        }

        // Single
        if ($input instanceof DomainObjectInterface) {
            $data = $this->normalizeDomainObject($input, $include);
        }

        // Only data root can have includes
        return [
            'data' => $data,
            'included' => array_values($this->includedStore)
        ];
    }

    /**
     * Normalize simple values that are not models
     *
     * @param mixed $value
     * @param string $wrap
     * @return mixed|string
     */
    public function normalizeValue($value, $wrap = null)
    {
        if ($value instanceof \DateTime) {
            $value = $value->format(DATE_ATOM);
        }

        return $wrap ? [$wrap => $value] : $value;
    }

    /**
     * Include recursively to store, and take care if already there.
     *
     * This one will merge the include normalization, because it may be requested from different paths,
     * with different relations. And it will also continue the recursion.
     *
     * @param DomainObjectInterface $model
     * @param array $include
     */
    public function addModelToIncludeStore(DomainObjectInterface $model, array $include)
    {
        $modelHash = $this->getModelHash($model);
        $this->includedStore[$modelHash] = array_replace_recursive(
            $this->includedStore[$modelHash] ?: [],
            $this->normalizeDomainObject($model, $include)
        );
    }

    /**
     * @param DomainObjectNormalizerInterface $restNormalizer
     * @return $this;
     */
    public function addDomainObjectNormalizer(DomainObjectNormalizerInterface $restNormalizer)
    {
        $this->domainObjectNormalizers[] = $restNormalizer;
        return $this;
    }

    /**
     * @param mixed $input
     * @param array $include
     * @return mixed
     * @throws \Exception
     */
    public function normalizeDomainObject($input, array $include = [])
    {
        $normalizers = $this->getSortedNormalizers();
        foreach ($normalizers as $normalizer) {
            if ($normalizer->canNormalize($input)) {
                return $normalizer->normalizeDomainObject($input, $include);
            }
        }
        throw new \Exception('Could not find a normalizer for one of the inputs');
    }

    /**
     * @return DomainObjectNormalizerInterface[]
     */
    public function getSortedNormalizers()
    {
        $normalizers = $this->domainObjectNormalizers;
        usort($normalizers,
            function (DomainObjectNormalizerInterface $normalizer1, DomainObjectNormalizerInterface $normalizer2) {
                return $normalizer1->getPriority() <=> $normalizer2->getPriority();
            });
        return array_reverse($normalizers);
    }

    /**
     * @param DomainObjectInterface $relationModel
     * @return string
     */
    protected function getModelHash(DomainObjectInterface $relationModel): string
    {
        return get_class($relationModel) . '-' . $relationModel->getUid();
    }

}
