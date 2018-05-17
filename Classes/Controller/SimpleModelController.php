<?php

namespace RozbehSharahi\Rest3\Controller;

use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\stream_for;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RozbehSharahi\Rest3\Exception;
use RozbehSharahi\Rest3\Normalizer\RestNormalizer;
use RozbehSharahi\Rest3\Route\RouteAccessControlInterface;
use RozbehSharahi\Rest3\Service\ModelService;
use RozbehSharahi\Rest3\Service\RequestService;
use RozbehSharahi\Rest3\Service\ResponseService;
use TYPO3\CMS\Core\Http\DispatcherInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;

class SimpleModelController implements DispatcherInterface
{

    /**
     * Will hold the full qualified model name
     *
     * @var null
     */
    protected $modelName = null;

    /**
     * @var null
     */
    protected $repositoryName = null;

    /**
     * @var RepositoryInterface
     */
    protected $repository;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @param ObjectManager $objectManager
     */
    public function injectObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
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
     * @var RouteAccessControlInterface
     */
    protected $accessControl;

    /**
     * @param RouteAccessControlInterface $accessControl
     */
    public function injectAccessControl(RouteAccessControlInterface $accessControl)
    {
        $this->accessControl = $accessControl;
    }

    /**
     * @var PersistenceManager
     */
    protected $persistenceManager;

    /**
     * @param PersistenceManager $persistenceManager
     */
    public function injectPersistenceManager(PersistenceManager $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * @var ModelService
     */
    protected $modelService;

    /**
     * @param ModelService $modelService
     */
    public function injectModelService(ModelService $modelService)
    {
        $this->modelService = $modelService;
    }

    /**
     * Main method to dispatch a request and its response to a callable object
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $routeKey
     * @return ResponseInterface
     * @throws Exception
     */
    public function dispatch(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $routeKey = ''
    ): ResponseInterface {
        $router = new \AltoRouter();

        $this->configureRoutes($request, $response, $routeKey, $router);

        // Evaluate the route
        $match = $router->match('/' . explode('/', $request->getUri()->getPath(), 4)[3], $request->getMethod());

        // In case we have a match
        if (!$match || !is_callable($match['target'])) {
            throw Exception::create()->addError('Route could not be interpreted');
        }

        // No match
        return call_user_func_array(
            $match['target'],
            array_merge($match['params'], [$request, $response])
        );
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function showOptions(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->responseService->jsonResponse(null)->withHeader('Allow', 'HEAD,GET,PUT,DELETE,OPTIONS');
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function findAll(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->responseService->jsonResponse(
            $this->restNormalizer->normalize(
                $this->getRepository()->findAll()->toArray(),
                $this->requestService->getIncludes($request)
            )
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int $id
     * @return ResponseInterface
     */
    protected function show(ServerRequestInterface $request, ResponseInterface $response, $id): ResponseInterface
    {
        /** @var DomainObjectInterface $model */
        $model = $this->getRepository()->findByUid($id);
        $this->assert(!empty($model), 'Not found');
        return $this->responseService->jsonResponse(
            $this->restNormalizer->normalize(
                $model,
                $this->requestService->getIncludes($request)
            )
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int $id
     * @param string $attributeName
     * @return ResponseInterface
     */
    protected function showAttribute(
        ServerRequestInterface $request,
        ResponseInterface $response,
        $id,
        string $attributeName
    ): ResponseInterface {
        /** @var DomainObjectInterface $model */
        $model = $this->getRepository()->findByUid($id);
        $this->assert(!empty($model), 'Not found');
        return $this->responseService->jsonResponse(
            $this->restNormalizer->normalize(
                $model->_getProperties()[$attributeName],
                $this->requestService->getIncludes($request)
            )
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int $id
     * @return ResponseInterface
     * @throws Exception
     */
    protected function update(ServerRequestInterface $request, ResponseInterface $response, $id): ResponseInterface
    {
        /** @var AbstractDomainObject $model */
        $model = $this->getRepository()->findByUid($id);
        $requestData = $this->requestService->getData($request);

        $this->assert(!empty($model), "Not found ($id)");
        $this->assertUpdateRequest($requestData);

        // Update
        $this->modelService->writeIntoModelByRequestData($model, $requestData);

        $this->getRepository()->update($model);
        $this->persistenceManager->persistAll();

        return $this->responseService->jsonResponse(
            $this->restNormalizer->normalize(
                $model,
                $this->requestService->getIncludes($request)
            )
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     */
    protected function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        /** @var AbstractDomainObject $model */
        $model = new $this->modelName;
        $requestData = $this->requestService->getData($request);
        $this->assertUpdateRequest($requestData);

        // Write into model
        $this->modelService->writeIntoModelByRequestData($model, $requestData);

        $this->getRepository()->add($model);
        $this->persistenceManager->persistAll();

        return $this->responseService->jsonResponse(
            $this->restNormalizer->normalize(
                $model,
                $this->requestService->getIncludes($request)
            )
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int $id
     * @return ResponseInterface
     * @throws Exception
     */
    protected function delete(ServerRequestInterface $request, ResponseInterface $response, $id): ResponseInterface
    {
        /** @var AbstractDomainObject $model */
        $model = $this->getRepository()->findByUid($id);
        $this->assert(!empty($model), "Not found ($id)");
        $this->getRepository()->remove($model);
        $this->persistenceManager->persistAll();
        return $this->responseService->jsonResponse(
            $this->restNormalizer->normalize(
                $this->modelName . " with ID `$id` was deleted"
            )
        );
    }

    /**
     * @return RepositoryInterface
     */
    protected function getRepository(): RepositoryInterface
    {
        if (is_null($this->repository)) {
            /** @var RepositoryInterface $repository */
            $repository = clone $this->objectManager->get($this->repositoryName);
            $this->repository = $repository;
            $this->repository->setDefaultQuerySettings((new Typo3QuerySettings())
                ->setRespectStoragePage(false)
                ->setLanguageMode('strict')
                ->setLanguageUid($this->requestService->getCurrentLanguageUid())
            );
        }
        return $this->repository;
    }

    /**
     * @param array $requestData
     */
    protected function assertUpdateRequest(array $requestData = null): void
    {
        $this->assert(
            $requestData !== null &&
            !(
                empty($requestData['data']['attributes']) &&
                empty($requestData['data']['relationships'])
            ),
            'Empty update request, you have to set attributes or relations'
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

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $routeKey
     * @param \AltoRouter|mixed $router
     */
    protected function configureRoutes(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $routeKey,
        $router
    ): void {
        $router->map('OPTIONS', '/?',
            function () use ($request, $response, $routeKey) {
                $this->accessControl->assertAccess($routeKey, 'showOptions');
                return $this->showOptions($request, $response);
            });
        $router->map('GET', '/?',
            function () use ($request, $response, $routeKey) {
                $this->accessControl->assertAccess($routeKey, 'findAll');
                return $this->findAll($request, $response);
            });
        $router->map('GET', '/[i:id]/?',
            function ($id) use ($request, $response, $routeKey) {
                $this->accessControl->assertAccess($routeKey, 'show');
                return $this->show($request, $response, $id);
            });
        $router->map('GET', '/[i:id]/[a:attributeName]/?',
            function ($id, $attributeName) use ($request, $response, $routeKey) {
                $this->accessControl->assertAccess($routeKey, 'showAttribute');
                return $this->showAttribute($request, $response, $id, $attributeName);
            });
        $router->map('PATCH', '/[i:id]/?',
            function ($id) use ($request, $response, $routeKey) {
                $this->accessControl->assertAccess($routeKey, 'update');
                return $this->update($request, $response, $id);
            });
        $router->map('POST', '/?',
            function () use ($request, $response, $routeKey) {
                $this->accessControl->assertAccess($routeKey, 'create');
                return $this->create($request, $response);
            });
        $router->map('DELETE', '/[i:id]/?',
            function ($id) use ($request, $response, $routeKey) {
                $this->accessControl->assertAccess($routeKey, 'delete');
                return $this->delete($request, $response, $id);
            });
    }

}
