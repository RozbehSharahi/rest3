<?php

namespace RozbehSharahi\Rest3\Tests\Functional;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class FunctionalTestBase extends FunctionalTestCase
{

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/rest3','typo3conf/ext/rexample'];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['fluid'];

    /**
     * @return object|ObjectManager
     */
    public function getObjectManager()
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }

    /**
     * @return ConfigurationManagerInterface
     */
    public function getConfigurationManager()
    {
        return $this->getObjectManager()->get(ConfigurationManagerInterface::class);
    }

    /**
     * @return array
     */
    public function getConfiguration() {
        return $this->getConfigurationManager()->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );
    }
}