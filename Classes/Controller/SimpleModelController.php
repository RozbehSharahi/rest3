<?php

namespace RozbehSharahi\Rest3\Controller;

use Doctrine\Common\Util\Inflector;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\stream_for;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RozbehSharahi\Rest3\BootstrapDispatcher;
use RozbehSharahi\Rest3\Exception;
use RozbehSharahi\Rest3\Normalizer\RestNormalizer;
use RozbehSharahi\Rest3\Service\RequestService;
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
     * Main method to dispatch a request and its response to a callable object
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $routeKey
     * @return ResponseInterface
     */
    public function dispatch(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $routeKey = ''
    ): ResponseInterface {
        $router = new \AltoRouter();
        $router->setBasePath(BootstrapDispatcher::getEntryPoint() . '/' . $this->requestService->getRouteKey($request));

        $router->map('OPTIONS', '/?', function () use ($request, $response) {
            return $this->showOptions($request, $response);
        });
        $router->map('GET', '/?', function () use ($request, $response) {
            return $this->findAll($request, $response);
        });
        $router->map('GET', '/[i:id]/?', function ($id) use ($request, $response) {
            return $this->show($request, $response, $id);
        });
        $router->map('GET', '/[i:id]/[a:attributeName]/?', function ($id, $attributeName) use ($request, $response) {
            return $this->showAttribute($request, $response, $id, $attributeName);
        });
        $router->map('PATCH', '/[i:id]/?', function ($id) use ($request, $response) {
            return $this->update($request, $response, $id);
        });

        // In case we have a match
        $match = $router->match($request->getUri()->getPath(), $request->getMethod());
        if ($match && is_callable($match['target'])) {
            return call_user_func_array(
                $match['target'],
                array_merge($match['params'], [$request, $response])
            );
        }

        // No match
        return $this->jsonResponse('404', 404);
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function showOptions(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->jsonResponse(null)->withHeader('Allow', 'HEAD,GET,PUT,DELETE,OPTIONS');
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function findAll(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->jsonResponse(
            $this->restNormalizer->normalize(
                $this->getRepository()->findAll()->toArray(),
                $this->getIncludeByRequest($request)
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
        return $this->jsonResponse(
            $this->restNormalizer->normalize(
                $model,
                $this->getIncludeByRequest($request)
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
        return $this->jsonResponse(
            $this->restNormalizer->normalize(
                $model->_getProperties()[$attributeName],
                $this->getIncludeByRequest($request)
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
        foreach ($requestData['data']['attributes'] ?: [] as $attributeName => $attributeValue) {
            if (!$model->_hasProperty($attributeName)) {
                throw new Exception("Property `$attributeName` does not exist on " . get_class($model));
            }
            $model->_setProperty($attributeName, $attributeValue);
        }

        $this->getRepository()->update($model);
        $this->persistenceManager->persistAll();

        return $this->jsonResponse(
            $this->restNormalizer->normalize(
                $model,
                $this->getIncludeByRequest($request)
            )
        );
    }

    /**
     * @param mixed $data
     * @param int $statusCode
     * @return ResponseInterface
     */
    protected function jsonResponse($data, $statusCode = 200): ResponseInterface
    {
        return new Response(
            $statusCode,
            ['Content-Type' => 'application/json'],
            stream_for(json_encode($data))
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
            );
        }
        return $this->repository;
    }

    /**
     * @param ServerRequestInterface $request
     * @return array
     */
    protected function getIncludeByRequest(ServerRequestInterface $request): array
    {
        return $request->getQueryParams()['include'] ? explode(',', $request->getQueryParams()['include']) : [];
    }

    /**
     * @param array $requestData
     */
    protected function assertUpdateRequest(array $requestData): void
    {
        $this->assert(
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
            throw new Exception(json_encode($message));
        }
    }

}
