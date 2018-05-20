<?php

namespace RozbehSharahi\Rest3\Route;

use RozbehSharahi\Rest3\Exception;

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
     * @param string $routeKey
     * @param string $actionName
     * @return bool
     * @throws \Exception
     */
    public function hasAccess(string $routeKey, string $actionName): bool
    {
        $configuration = $this->routeManager->getRouteConfiguration($routeKey);

        // Make sure permissions will be arrays
        $configuration['permissions'] = $configuration['permissions'] ?: [];

        if (!is_array($configuration['permissions'])) {
            throw new \Exception("Bad configuration on permission `routes.$routeKey.permissions`");
        }

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

}
