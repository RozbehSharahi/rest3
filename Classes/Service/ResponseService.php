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
        $headers = [
            'Content-Type' => 'application/json'
        ];

        foreach ($this->getAdditionalHeaders() as $key => $value) {
            $headers[$key] = $value;
        }

        return new Response($statusCode, $headers, stream_for(json_encode($data)));
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getAdditionalHeaders(): array
    {
        $additionalHeaders = $this->configurationService->getSetting('additionalHeaders') ?: [];
        if (!is_null($additionalHeaders) && !is_array($additionalHeaders)) {
            throw new \Exception('Bad configuration for additionalHeaders');
        }
        return $additionalHeaders;
    }

}
