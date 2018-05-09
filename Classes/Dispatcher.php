<?php

namespace RozbehSharahi\Rest3;

use Doctrine\Common\Util\Inflector;
use function GuzzleHttp\Psr7\stream_for;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RozbehSharahi\Rest3\RequestStrategy\RequestStrategyManagerInterface;
use RozbehSharahi\Rest3\Route\RouteManagerInterface;

class Dispatcher implements DispatcherInterface, \TYPO3\CMS\Core\Http\DispatcherInterface
{
    /**
     * @var string
     */
    protected $entryPoint = '/rest3';

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
     * @var RequestStrategyManagerInterface
     */
    protected $requestStrategyManager;

    /**
     * @param RequestStrategyManagerInterface $requestStrategyManager
     */
    public function injectRequestStrategyManager(RequestStrategyManagerInterface $requestStrategyManager)
    {
        $this->requestStrategyManager = $requestStrategyManager;
    }

    /**
     * Main method to dispatch a request and its response to a callable object
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($this->isRestRootCall($request)) {
            return $response->withBody(stream_for('Welcome to Rest3'));
        }

        if(!$this->routeManager->hasRouteConfiguration($this->getRouteKey($request))) {
            return $response->withBody(stream_for('This route does not exist'))->withStatus('404');
        }

        $configuration = $this->routeManager->getRouteConfiguration($this->getRouteKey($request));

        return $this->requestStrategyManager->run(
            $configuration['strategy'],
            $configuration,
            [$request, $response]
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function isRestRootCall(ServerRequestInterface $request): bool
    {
        return trim($request->getUri()->getPath(), '/') === trim($this->entryPoint, '/');
    }

    /**
     * Gets the current route key
     *
     * Example '/rest3/seminar/1` => `seminar`
     *
     * @param ServerRequestInterface $request
     * @return mixed
     */
    protected function getRouteKey(ServerRequestInterface $request)
    {
        $routeKey = explode('/', trim($request->getUri()->getPath(), '/'))[1];
        return Inflector::singularize($routeKey);
    }

    /**
     * @return string
     */
    public function getEntryPoint(): string
    {
        return $this->entryPoint;
    }

}