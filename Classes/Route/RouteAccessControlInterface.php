<?php

namespace RozbehSharahi\Rest3\Route;

interface RouteAccessControlInterface
{
    /**
     * @param string $routeKey
     * @param string $actionName
     * @return bool
     */
    public function hasAccess(string $routeKey, string $actionName): bool;

    /**
     * @param string $routeKey
     * @param string $actionName
     */
    public function assertAccess(string $routeKey, string $actionName): void;
}
