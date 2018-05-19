<?php

namespace RozbehSharahi\Rest3\Normalizer;

use TYPO3\CMS\Extbase\Domain\Model\FrontendUser;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;

class FrontendUserNormalizer extends DomainObjectNormalizer
{
    /**
     * @var array
     */
    protected $excludedAttributes = ['password'];

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return 60;
    }

    /**
     * @param DomainObjectInterface $model
     * @return bool
     */
    public function canNormalize(DomainObjectInterface $model): bool
    {
        return $model instanceof FrontendUser;
    }

}
