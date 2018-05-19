<?php

namespace RozbehSharahi\Rest3\Normalizer;

use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;

interface DomainObjectNormalizerInterface
{
    /**
     * @return int
     */
    public function getPriority(): int;

    /**
     * @param DomainObjectInterface $model
     * @return bool
     */
    public function canNormalize(DomainObjectInterface $model): bool;

    /**
     * @param DomainObjectInterface $model
     * @param array $include
     * @return mixed
     */
    public function normalizeDomainObject(DomainObjectInterface $model, array $include = []);

    /**
     * @param $normalizer
     * @return DomainObjectNormalizerInterface
     */
    public function setNormalizer($normalizer);

}
