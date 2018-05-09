<?php

namespace RozbehSharahi\Rexample;

use function GuzzleHttp\Psr7\stream_for;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\DispatcherInterface;

class MessageRoute implements DispatcherInterface
{

    /**
     * Main method to dispatch a request and its response to a callable object
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $response->withBody(stream_for('This response was provided by ' . self::class));
    }
}