<?php

namespace RozbehSharahi\Rest3\Route;

interface RouteManagerInterface
{

    /**
     * Gets a sorted list of all providers
     *
     * @return array
     */
    public function getRouteConfigurations(): array;

    /**
     * Gets the Provider for a specific configuration
     *
     * @param string $route
     * @param string|null $key
     * @return mixed
     */
    public function getRouteConfiguration(string $route, string $key = null);

    /**
     * Checks for existence of a route
     *
     * @param string $route
     * @return bool
     */
    public function hasRouteConfiguration(string $route): bool;

}