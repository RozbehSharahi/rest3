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
            new ServerRequest('GET', new Uri('/rest3/seminar/does-not-exist/definitely/not')),
            new Response()
        );

        self::assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function canFindAll()
    {
        $this->setUpTestWebsite();
        $this->setUpDatabaseData('tx_rexample_domain_model_seminar', [
            [
                'title' => 'First Seminar',
            ],
            [
                'title' => 'Second Seminar',
            ]
        ]);

        /** @var DispatcherInterface $dispatcher */
        $dispatcher = $this->getObjectManager()->get(DispatcherInterface::class);
        $response = $dispatcher->dispatch(
            new ServerRequest('GET', new Uri('/rest3/seminar')),
            new Response()
        );
        self::assertEquals(200, $response->getStatusCode());
        self::assertCount(2, json_decode($response->getBody()));
    }

    /**
     * @test
     */
    public function canShow()
    {
        $this->setUpTestWebsite();
        $this->setUpDatabaseData('tx_rexample_domain_model_seminar', [
            [
                'title' => 'A Seminar',
            ]
        ]);

        /** @var DispatcherInterface $dispatcher */
        $dispatcher = $this->getObjectManager()->get(DispatcherInterface::class);
        $response = $dispatcher->dispatch(
            new ServerRequest('GET', new Uri('/rest3/seminar/1/')),
            new Response()
        );
        self::assertEquals(200, $response->getStatusCode());
        self::assertNotEmpty($response->getBody()->__toString());
    }

}
