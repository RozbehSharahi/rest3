<?php

namespace RozbehSharahi\Rest3;

use Doctrine\Common\Util\Inflector;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\stream_for;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RozbehSharahi\Rest3\RequestStrategy\RequestStrategyManagerInterface;
use RozbehSharahi\Rest3\Route\RouteManagerInterface;
use RozbehSharahi\Rest3\Service\RequestService;

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
     * @var RequestService
     */
    protected $requestService;

    /**
     * @param RequestService $requestService
     */
    public function injectRequestService(RequestService $requestService)
    {
        $this->requestService = $requestService;
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

        if (!$this->routeManager->hasRouteConfiguration($this->requestService->getRouteKey($request))) {
            $restException = Exception::create()->addError('This route does not exists', 404);
            return new Response(
                $restException->getStatusCode(),
                $restException->getHeaders(),
                $restException->getErrorJson()
            );
        }

        $configuration = $this->routeManager->getRouteConfiguration($this->requestService->getRouteKey($request));

        // We render the response or an rest exception
        try {
            return $this->requestStrategyManager->run(
                $configuration['strategy'],
                $configuration,
                [$request, $response, $this->requestService->getRouteKey($request)]
            );
        } catch (Exception $restException) {
            return new Response(
                $restException->getStatusCode(),
                $restException->getHeaders(),
                stream_for($restException->getErrorJson())
            );
        }
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
     * @return string
     */
    public function getEntryPoint(): string
    {
        return $this->entryPoint;
    }

}