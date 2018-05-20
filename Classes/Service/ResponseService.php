<?php

namespace RozbehSharahi\Rest3\Service;

use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\stream_for;
use Psr\Http\Message\ResponseInterface;

class ResponseService
{

    /**
     * @var ConfigurationService
     */
    protected $configurationService;

    /**
     * @param ConfigurationService $configurationService
     */
    public function injectConfigurationService(ConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;
    }

    /**
     * @param mixed $data
     * @param int $statusCode
     * @return ResponseInterface
     * @throws \Exception
     */
    public function jsonResponse($data, $statusCode = 200): ResponseInterface
    {
        return new Response(
            $statusCode,
            $this->getDefaultHeaders(),
            stream_for(json_encode($data))
        );
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getDefaultHeaders(): array
    {
        $defaultHeaders = $this->configurationService->getSetting('defaultHeaders');
        if (!is_null($defaultHeaders) && !is_array($defaultHeaders)) {
            throw new \Exception('Bad configuration for defaultHeaders');
        }
        return $defaultHeaders;
    }

}
