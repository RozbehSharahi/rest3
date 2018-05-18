<?php

namespace RozbehSharahi\Rest3\Route;

use RozbehSharahi\Rest3\Exception;
use RozbehSharahi\Rest3\Service\ConfigurationService;
use RozbehSharahi\Rest3\Service\FrontendUserService;
use TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;

class RouteAccessControl implements RouteAccessControlInterface
{

    /**
     * @var RouteManagerInterface
     */
    protected $routeManager;

    /**
     * @param RouteManagerInterface $routeManager
     */
    public function injectRouteManager(RouteManagerInterface $routeManager)
    {
        $this->routeManager = $routeManager;
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
     * @param string $routeKey
     * @param string $actionName
     * @return bool
     * @throws \Exception
     */
    public function hasAccess(string $routeKey, string $actionName): bool
    {
        $configuration = $this->routeManager->getRouteConfiguration($routeKey);
        $userGroupConfiguration = $this->getUserGroupSettings()['routes'][$routeKey];

        // Make sure permissions will be arrays
        $configuration['permissions'] = $configuration['permissions'] ?: [];
        $userGroupConfiguration['permissions'] = $userGroupConfiguration['permissions'] ?: [];

        if (!is_array($configuration['permissions']) || !is_array($userGroupConfiguration['permissions'])) {
            throw new \Exception("Bad configuration on permission `routes.$routeKey.permissions`");
        }

        // Merge configurations
        $configuration = array_replace_recursive(
            $configuration,
            $userGroupConfiguration
        );

        // Explicitly configured
        if ($configuration['permissions'][$actionName] !== null) {
            return (
                $configuration['permissions'][$actionName] === '1' ||
                $configuration['permissions'][$actionName] === 'true'
            );
        }

        return $configuration['restrictive'] ? false : true;
    }

    /**
     * @param string $routeKey
     * @param string $actionName
     * @throws Exception
     */
    public function assertAccess(string $routeKey, string $actionName): void
    {
        if (!$this->hasAccess($routeKey, $actionName)) {
            throw Exception::create()->addError('Permission denied');
        }
    }

    /**
     * User group settings
     *
     * This one is complex, it will merge the all settings defined on user group layer. Sorting of user groups
     * is not yet implemented and will follow.
     *
     * This might be moved to configuration service to have this already solved earlier. Still I'm not sure
     * if it is not too magick.
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
        $this->configurationService->getTypoScriptParser()->parse($settings);
        $setup = $this->configurationService->getTypoScriptParser()->setup;

        return $this->configurationService->getTypoScriptService()->convertTypoScriptArrayToPlainArray($setup);
    }

}
