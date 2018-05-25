<?php

namespace RozbehSharahi\Rest3\Route;

use RozbehSharahi\Rest3\Service\ConfigurationService;
use TYPO3\CMS\Core\Utility\ArrayUtility;

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
     * @param string|null $key
     * @return mixed
     * @throws \Exception
     */
    public function getRouteConfiguration(string $route, string $key = null)
    {
        // Configuration has to exist
        if (!$this->hasRouteConfiguration($route)) {
            throw new \Exception('No route configuration for ' . $route);
        }

        $configuration = $this->prepareConfiguration(
            $this->getRouteConfigurations()[$route]
        );

        // Configuration must be valid
        if (!$this->routeConfigurationIsValid($configuration)) {
            throw new \Exception("Route configuration not valid for route: $route");
        }

        if (!$key) {
            return $configuration;
        }

        if ($key && !ArrayUtility::isValidPath($configuration, $key, '.')) {
            return null;
        }

        return ArrayUtility::getValueByPath($configuration, $key, '.');
    }

    /**
     * Checks for existence of a route
     *
     * @param string $route
     * @return bool
     */
    public function hasRouteConfiguration(string $route): bool
    {
        return !empty($this->getRouteConfigurations()[$route]);
    }

    /**
     * @param array $routeConfiguration
     * @return bool
     */
    protected function routeConfigurationIsValid(array $routeConfiguration): bool
    {
        return (
            (is_null($routeConfiguration['readOnlyProperties']) || is_array($routeConfiguration['readOnlyProperties'])) &&
            (is_null($routeConfiguration['permissions']) || is_array($routeConfiguration['permissions']))
        );
    }

    /**
     * @param $configuration
     * @return array
     */
    protected function prepareConfiguration(array $configuration): array
    {
        $configuration['readOnlyProperties'] = $configuration['readOnlyProperties'] ?: [];
        $configuration['permissions'] = $configuration['permissions'] ?: [];
        return $configuration;
    }

}
