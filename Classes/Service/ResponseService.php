<?php

namespace RozbehSharahi\Rest3\Service;

use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\stream_for;
use Psr\Http\Message\ResponseInterface;

class ResponseService
{

    /**
     * @param mixed $data
     * @param int $statusCode
     * @return ResponseInterface
     */
    public function jsonResponse($data, $statusCode = 200): ResponseInterface
    {
        return new Response(
            $statusCode,
            ['Content-Type' => 'application/json'],
            stream_for(json_encode($data))
        );
    }

}
