<?php

namespace RozbehSharahi\Rest3\ListHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ListHandlerInterface
{

    const QUERY_PARAM = '_list';

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param $routeKey
     * @return ResponseInterface
     */
    public function list(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $routeKey
    ): ResponseInterface;

}
