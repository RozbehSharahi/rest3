<?php

namespace RozbehSharahi\Rest3\Controller;

use Doctrine\Common\Util\Inflector;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\stream_for;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RozbehSharahi\Rest3\BootstrapDispatcher;
use TYPO3\CMS\Core\Http\DispatcherInterface;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
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
        $router->setBasePath(BootstrapDispatcher::getEntryPoint() . '/' . $this->getRouteKey($request));

        $router->map('GET', '/?', function () use ($request, $response) {
            return $this->findAll($request, $response);
        });

        $router->map('GET', '/[i:id]/?', function ($id) use ($request, $response) {
            return $this->show($request, $response, $id);
        });

        // In case we have a match
        $match = $router->match($request->getUri()->getPath(), $request->getMethod());
        if ($match && is_callable($match['target'])) {
            return call_user_func_array(
                $match['target'],
                array_merge($match['params'], [$request, $response])
            );
        }
        return $this->jsonResponse('404', 404);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function findAll(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->jsonResponse($this->getRepository()->findAll()->toArray());
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
     * Gets the current route key
     *
     * Example '/rest3/seminar/1` => `seminar`
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    protected function getRouteKey(ServerRequestInterface $request): string
    {
        $routeKey = explode('/', trim($request->getUri()->getPath(), '/'))[1];
        return Inflector::singularize($routeKey);
    }

    /**
     * @return RepositoryInterface
     */
    protected function getRepository(): RepositoryInterface
    {
        if (is_null($this->repository)) {
            /** @var RepositoryInterface $repository */
            $repository = $this->objectManager->get($this->repositoryName);
            $this->repository = $repository;
        }
        return $this->repository;
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param int $id
     * @return ResponseInterface
     */
    protected function show(RequestInterface $request, ResponseInterface $response, $id): ResponseInterface
    {
        /** @var DomainObjectInterface $model */
        $model = $this->getRepository()->findByUid($id);
        return $this->jsonResponse(json_encode($model->_getProperties()));
    }

}
