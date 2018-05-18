<?php

namespace TYPO3\CMS\Core\Tests\Functional;

use RozbehSharahi\Rest3\DispatcherInterface;
use RozbehSharahi\Rest3\Tests\Functional\FunctionalTestBase;
use RozbehSharahi\Rexample\MessageRoute;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;

class DispatcherTest extends FunctionalTestBase
{

    /**
     * @test
     */
    public function canSeeSeminarRoute()
    {
        /** @var DispatcherInterface $dispatcher */
        $dispatcher = $this->getObjectManager()->get(DispatcherInterface::class);
        $request = new ServerRequest('/rest3/message', 'GET');
        $response = $dispatcher->dispatch($request, new Response());

        self::assertEquals(
            'This response was provided by ' . MessageRoute::class,
            $response->getBody()->__toString()
        );
    }

}