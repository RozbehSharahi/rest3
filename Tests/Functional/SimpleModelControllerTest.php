<?php

namespace RozbehSharahi\Rest3\Tests\Functional;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use RozbehSharahi\Rest3\DispatcherInterface;

class SimpleModelControllerTest extends FunctionalTestBase
{

    /**
     * @test
     */
    public function shows404OnNonExistingRoute()
    {
        /** @var DispatcherInterface $dispatcher */
        $dispatcher = $this->getObjectManager()->get(DispatcherInterface::class);
        $response = $dispatcher->dispatch(
            new ServerRequest('GET',new Uri('/rest3/seminar/does-not-exist/definitely/not')),
            new Response()
        );

        self::assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function canFindAll()
    {
        /** @var DispatcherInterface $dispatcher */
        $dispatcher = $this->getObjectManager()->get(DispatcherInterface::class);
        $response = $dispatcher->dispatch(
            new ServerRequest('GET',new Uri('/rest3/seminar')),
            new Response()
        );
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('Find all was called',$response->getBody());
    }

}
