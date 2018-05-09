<?php

namespace RozbehSharahi\Rest3\Route;

use RozbehSharahi\Rest3\Service\ConfigurationService;

class RouteManager implements RouteManagerInterface
{

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
     * Gets a sorted list of all providers
     *
     * @return array
     */
    public function getRouteConfigurations(): array
    {
        return $this->configurationService->getSetting('routes');
    }

    /**
     * @param string $route
     * @return array
     * @throws \Exception
     */
    public function getRouteConfiguration(string $route): array
    {
        foreach ($this->getRouteConfigurations() as $routeKey => $routeConfiguration) {
            if ($route === $routeKey && $this->routeConfigurationIsValid($routeConfiguration)) {
                return $routeConfiguration;
            }
        }
        throw new \Exception("Could not find configuration for route: $route");
    }

    /**
     * Checks for existence of a route
     *
     * @param string $route
     * @return bool
     */
    public function hasRouteConfiguration(string $route): bool
    {
        foreach ($this->getRouteConfigurations() as $routeKey => $routeConfiguration) {
            if ($route === $routeKey && $this->routeConfigurationIsValid($routeConfiguration)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $routeConfiguration
     * @return bool
     */
    protected function routeConfigurationIsValid(array $routeConfiguration): bool
    {
        return !empty($routeConfiguration['strategy']);
    }
}