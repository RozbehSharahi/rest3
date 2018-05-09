<?php

namespace RozbehSharahi\Rest3\RequestStrategy;

use Psr\Http\Message\ResponseInterface;

interface RequestStrategyManagerInterface
{

    /**
     * @param string $strategy
     * @param array $configuration
     * @param array $parameters
     * @return ResponseInterface
     */
    public function run(string $strategy, array $configuration, array $parameters): ResponseInterface;

}