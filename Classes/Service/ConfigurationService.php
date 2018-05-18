<?php

namespace RozbehSharahi\Rest3\Service;

use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

class ConfigurationService
{

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @param ConfigurationManager $configurationManager
     */
    public function injectConfigurationManager(ConfigurationManager $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * @var TypoScriptService
     */
    protected $typoScriptService;

    /**
     * @param TypoScriptService $typoScriptService
     */
    public function injectTypoScriptService(TypoScriptService $typoScriptService)
    {
        $this->typoScriptService = $typoScriptService;
    }

    /**
     * @var TypoScriptParser
     */
    protected $typoScriptParser;

    /**
     * @param TypoScriptParser $typoScriptParser
     */
    public function injectTypoScriptParser(TypoScriptParser $typoScriptParser)
    {
        $this->typoScriptParser = $typoScriptParser;
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        $settings = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        )['plugin.']['tx_rest3.']['settings.'];

        // If there is no settings at all we return an empty routes configuration
        return !empty($settings) ? $this->typoScriptService->convertTypoScriptArrayToPlainArray($settings) : [
            'routes' => []
        ];
    }

    /**
     * @param string $path
     * @return array|null
     */
    public function getSetting(string $path)
    {
        try {
            return ArrayUtility::getValueByPath($this->getSettings(), $path, '.');
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * @return TypoScriptService
     */
    public function getTypoScriptService()
    {
        return $this->typoScriptService;
    }

    /**
     * @return TypoScriptParser
     */
    public function getTypoScriptParser()
    {
        return $this->typoScriptParser;
    }

}