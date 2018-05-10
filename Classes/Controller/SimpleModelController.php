<?php

namespace RozbehSharahi\Rest3\Controller;

use Doctrine\Common\Util\Inflector;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\stream_for;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RozbehSharahi\Rest3\BootstrapDispatcher;
use TYPO3\CMS\Core\Http\DispatcherInterface;

class SimpleModelController implements DispatcherInterface
{

    /**
     * Will hold the full qualified model name
     *
     * @var null
     */
    protected $modelName = null;

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
        $router->setBasePath(BootstrapDispatcher::getEntryPoint() . '/' . $this->getRouteKey($request) . '/');

        $router->map('GET', '/?', function () use ($request, $response) {
            return $this->findAll($request, $response);
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
        return $response->withBody(stream_for('Find all was called'));
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

}
