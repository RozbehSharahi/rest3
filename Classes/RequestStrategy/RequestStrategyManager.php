<?php

namespace RozbehSharahi\Rest3\RequestStrategy;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class RequestStrategyManager implements RequestStrategyManagerInterface
{
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
     * @param string $strategy
     * @param array $configuration
     * @param array $parameters
     * @return ResponseInterface
     * @throws \Exception
     */
    public function run(string $strategy, array $configuration, array $parameters = []): ResponseInterface
    {
        if ($strategy === 'dispatcher') {
            return $this->runClassMethod($configuration, $parameters);
        }

        throw new \Exception("No strategy implementation for: `$strategy`");
    }

    /**
     * @param array $configuration
     * @param array $parameters
     * @return ResponseInterface
     * @throws \Exception
     */
    protected function runClassMethod(array $configuration, array $parameters): ResponseInterface
    {
        if (empty($configuration['className']) || empty($configuration['methodName'])) {
            throw new \Exception('Strategy `dispatcher` needs a `className` and a `methodName`');
        }
        $object = $this->objectManager->get($configuration['className']);
        return call_user_func_array([$object, $configuration['methodName']], $parameters);
    }

}