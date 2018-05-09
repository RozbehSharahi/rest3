<?php

namespace RozbehSharahi\Rest3\Tests\Functional;

use RozbehSharahi\Rest3\Route\RouteManagerInterface;

class RouteManagerTest extends FunctionalTestBase
{

    /**
     * @test
     */
    public function canGetRouteConfigurations()
    {
        /** @var RouteManagerInterface $routeManager */
        $routeManager = $this->getObjectManager()->get(RouteManagerInterface::class);
        self::assertTrue(is_array($routeManager->getRouteConfigurations()));
    }

    /**
     * @test
     */
    public function canGetRouteConfiguration()
    {
        /** @var RouteManagerInterface $routeManager */
        $routeManager = $this->getObjectManager()->get(RouteManagerInterface::class);

        self::assertTrue($routeManager->hasRouteConfiguration('seminar'));

        $configuration = $routeManager->getRouteConfiguration('seminar');
        self::assertEquals('class-method', $configuration['strategy']);
    }

    /**
     * @test
     */
    public function canCheckIfRouteExists()
    {
        /** @var RouteManagerInterface $routeManager */
        $routeManager = $this->getObjectManager()->get(RouteManagerInterface::class);

        self::assertTrue($routeManager->hasRouteConfiguration('seminar'));
        self::assertFalse($routeManager->hasRouteConfiguration('car'));
    }

    /**
     * @test
     * @expectedException \Exception
     */
    public function throwsExceptionOnNotExistingRouteAccess()
    {
        /** @var RouteManagerInterface $routeManager */
        $routeManager = $this->getObjectManager()->get(RouteManagerInterface::class);
        $routeManager->getRouteConfiguration('car');
    }

}