<?php

namespace RozbehSharahi\Rest3\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RozbehSharahi\Rest3\Exception;
use RozbehSharahi\Rest3\Normalizer\RestNormalizer;
use RozbehSharahi\Rest3\Service\FrontendUserService;
use RozbehSharahi\Rest3\Service\RequestService;
use RozbehSharahi\Rest3\Service\ResponseService;
use TYPO3\CMS\Core\Http\DispatcherInterface;
use TYPO3\CMS\Extbase\Domain\Model\FrontendUser;

class LoginController implements DispatcherInterface
{

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
     * @var RestNormalizer
     */
    protected $restNormalizer;

    /**
     * @param RestNormalizer $restNormalizer
     */
    public function injectRestNormalizer(RestNormalizer $restNormalizer)
    {
        $this->restNormalizer = $restNormalizer;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     */
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $path = explode('/', $request->getUri()->getPath(), 4)[3];

        if (trim($path, '/') === 'sign-in') {
            return $this->login($request, $response);
        }

        if (trim($path, '/') === 'sign-out') {
            return $this->logout($request, $response);
        }

        if (trim($path, '/') === 'user') {
            return $this->user($request, $response);
        }

        throw Exception::create()->addError('Could no determine task');
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     */
    protected function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $requestData = $this->requestService->getParameters($request);

        $this->assert(!empty($requestData['username']), 'User name not send !');
        $this->assert(!empty($requestData['password']), 'Password not send !');

        if (!$this->frontendUserService->checkCredentials($requestData['username'], $requestData['password'])) {
            throw Exception::create()->addError('Wrong credentials');
        }

        /** @var FrontendUser $user */
        $user = $this->frontendUserService->findUser($requestData['username']);
        $this->frontendUserService->loginUser($user);

        return $this->responseService->jsonResponse([
            'message' => 'Logged in successfully'
        ]);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->frontendUserService->logoutCurrentUser();

        return $this->responseService->jsonResponse([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     */
    protected function user(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if(!$this->frontendUserService->getCurrentUser()) {
            throw Exception::create()->addError('Not logged in');
        }

        return $this->responseService->jsonResponse(
            $this->restNormalizer->normalize(
                $this->frontendUserService->getCurrentUser(),
                $this->requestService->getIncludes($request)
            )
        );
    }

    /**
     * @param bool $assertion
     * @param mixed $message
     * @throws Exception
     */
    protected function assert(bool $assertion, $message)
    {
        if (!$assertion) {
            throw Exception::create()->addError($message);
        }
    }

}
