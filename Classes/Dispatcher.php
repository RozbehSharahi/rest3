<?php

namespace RozbehSharahi\Rest3;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RozbehSharahi\Rest3\Authentication\TokenManagerInterface;
use RozbehSharahi\Rest3\RequestStrategy\RequestStrategyManagerInterface;
use RozbehSharahi\Rest3\Route\RouteManagerInterface;
use RozbehSharahi\Rest3\Service\FrontendUserService;
use RozbehSharahi\Rest3\Service\RequestService;
use RozbehSharahi\Rest3\Service\ResponseService;
use TYPO3\CMS\Extbase\Domain\Model\FrontendUser;

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
     * @var ResponseService
     */
    protected $responseService;

    /**
     * @param ResponseService $responseService
     */
    public function injectResponseService(ResponseService $responseService)
    {
        $this->responseService = $responseService;
    }

    /**
     * @var TokenManagerInterface
     */
    protected $tokenManager;

    /**
     * @param TokenManagerInterface $tokenManager
     */
    public function injectTokenManager(TokenManagerInterface $tokenManager)
    {
        $this->tokenManager = $tokenManager;
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
     * Main method to dispatch a request and its response to a callable object
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->authenticate($request);

        if ($this->isRestRootCall($request)) {
            return $this->responseService->jsonResponse('Welcome to Rest3');
        }

        if (!$this->routeManager->hasRouteConfiguration($this->requestService->getRouteKey($request))) {
            $restException = Exception::create()->addError('This route does not exist', 404);
            return $this->responseService->jsonResponse(
                $restException->getPayload(),
                $restException->getStatusCode()
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
            return $this->responseService->jsonResponse(
                $restException->getPayload(),
                $restException->getStatusCode()
            );
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @throws \Exception
     */
    protected function authenticate(ServerRequestInterface $request)
    {
        $token = $this->requestService->getParameters($request)[TokenManagerInterface::TOKEN_NAME] ?:
            end($request->getHeader(TokenManagerInterface::TOKEN_NAME));

        if (empty($token)) {
            return;
        }

        if (!$this->tokenManager->validate($token)) {
            throw new \Exception('Invalid token');
        }

        /** @var FrontendUser $user */
        $user = $this->frontendUserService
            ->getFrontendUserRepository()
            ->findByUid($this->tokenManager->getUserByToken($token));

        $this->frontendUserService->authenticateUser($user);
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