<?php

namespace RozbehSharahi\Rest3;

use Doctrine\Common\Util\Inflector;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\stream_for;
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
        $response = $this->run($request, $response);
        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return Response|ResponseInterface|static
     */
    protected function run(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($this->isRestRootCall($request)) {
            return $response->withBody(stream_for('Welcome to Rest3'));
        }

        $this->authenticate($request);

        if (!$this->routeManager->hasRouteConfiguration($this->requestService->getRouteKey($request))) {
            $restException = Exception::create()->addError('This route does not exists', 404);
            return new Response(
                $restException->getStatusCode(),
                array_replace_recursive($restException->getHeaders(), $this->responseService->getAdditionalHeaders()),
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
                array_replace_recursive($restException->getHeaders(), $this->responseService->getAdditionalHeaders()),
                stream_for($restException->getErrorJson())
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