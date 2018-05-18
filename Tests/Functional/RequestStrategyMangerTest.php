<?php

namespace TYPO3\CMS\Core\Tests\Functional;

use RozbehSharahi\Rest3\RequestStrategy\RequestStrategyManager;
use RozbehSharahi\Rest3\Tests\Functional\FunctionalTestBase;
use RozbehSharahi\Rexample\MessageRoute;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;

class RequestStrategyMangerTest extends FunctionalTestBase
{

    /**
     * @test
     */
    public function canRunByStrategy()
    {
        /** @var RequestStrategyManager $requestStrategyManager */
        $requestStrategyManager = $this->getObjectManager()->get(RequestStrategyManager::class);
        $response = $requestStrategyManager->run(
            'dispatcher',
            [
                'className' => MessageRoute::class,
                'methodName' => 'dispatch'
            ],
            [
                new ServerRequest(),
                new Response()
            ]
        );
        self::assertEquals('This response was provided by ' . MessageRoute::class, $response->getBody()->__toString());
    }

}