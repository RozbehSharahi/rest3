<?php

namespace RozbehSharahi\Rest3\Service;

use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;

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
     * @var FrontendUserService
     */
    protected $frontendUserService;

    /**
     * @param FrontendUserService $frontendUserService
     */
    public function injectFrontendUserService(FrontendUserService $frontendUserService)
    {
        $this->frontendUserService = $frontendUserService;
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
        $settings = !empty($settings) ? $this->typoScriptService->convertTypoScriptArrayToPlainArray($settings) : [
            'routes' => []
        ];

        return array_replace_recursive(
            $settings,
            $this->getUserGroupSettings()
        );
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
     * User group settings
     *
     * This one is complex, it will merge the all settings defined on user group layer. Sorting of user groups
     * is not yet implemented and will follow.
     *
     * @return array
     */
    protected function getUserGroupSettings(): array
    {
        if (!$this->frontendUserService->isLoggedIn()) {
            return [];
        }

        $groupIds = array_map(function (FrontendUserGroup $group) {
            return $group->getUid();
        }, $this->frontendUserService->getCurrentUser()->getUsergroup()->toArray());

        $groupsQuery = $this->frontendUserService->getFrontendUserGroupRepository()->createQuery();
        $groupsQuery->setQuerySettings((new Typo3QuerySettings())->setRespectStoragePage(false));
        $groups = $groupsQuery->matching($groupsQuery->in('uid', $groupIds))->execute(true);

        $settings = '';
        foreach ($groups as $group) {
            $settings .= $group['tx_rest3_settings'] . PHP_EOL;
        }
        $this->getTypoScriptParser()->parse($settings);
        $setup = $this->getTypoScriptParser()->setup;

        return $this->getTypoScriptService()->convertTypoScriptArrayToPlainArray($setup);
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