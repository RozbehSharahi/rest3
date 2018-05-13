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
                'title' => 'First seminar',
            ],
            [
                'title' => 'Second seminar',
            ]
        ]);

        /** @var DispatcherInterface $dispatcher */
        $dispatcher = $this->getObjectManager()->get(DispatcherInterface::class);
        $response = $dispatcher->dispatch(
            new ServerRequest('GET', new Uri('/rest3/seminar')),
            new Response()
        );
        $result = json_decode($response->getBody(),true);
        self::assertEquals(200, $response->getStatusCode());
        self::assertCount(2, $result['data']);
        self::assertEquals('First seminar',$result['data'][0]['attributes']['title']);
        self::assertEquals('Second seminar',$result['data'][1]['attributes']['title']);
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
            (new ServerRequest('GET', new Uri('/rest3/seminar/1/'))),
            new Response()
        );
        $result = json_decode($response->getBody(), true);
        self::assertEquals(200, $response->getStatusCode());
        self::assertNotEmpty($result);
        self::assertEquals('A Seminar', $result['data']['attributes']['title']);
    }

    /**
     * @test
     */
    public function canSeeErrorOnNonExistingModel()
    {
        $this->setUpTestWebsite();

        /** @var DispatcherInterface $dispatcher */
        $dispatcher = $this->getObjectManager()->get(DispatcherInterface::class);
        $response = $dispatcher->dispatch(
            new ServerRequest('GET', new Uri('/rest3/seminar/1/')),
            new Response()
        );
        self::assertEquals(404, $response->getStatusCode());
        self::assertEquals('"Not found"', $response->getBody()->__toString());
    }

    /**
     * @test
     */
    public function canIncludeRelations()
    {
        $this->setUpTestWebsite();
        $this->setUpDatabaseData('tx_rexample_domain_model_seminar', [
            [
                'title' => 'A Seminar',
            ]
        ]);
        $this->setUpDatabaseData('tx_rexample_domain_model_event', [
            [
                'title' => 'First event',
                'seminar' => 1
            ],
            [
                'title' => 'Second event',
                'seminar' => 1
            ]
        ]);

        /** @var DispatcherInterface $dispatcher */
        $dispatcher = $this->getObjectManager()->get(DispatcherInterface::class);
        $response = $dispatcher->dispatch(
            (new ServerRequest('GET', new Uri('/rest3/seminar/1/')))
                ->withQueryParams([
                    'include' => 'events'
                ]),
            new Response()
        );
        $result = json_decode($response->getBody(), true);
        self::assertEquals(200, $response->getStatusCode());
        self::assertNotEmpty($result);
        self::assertEquals('A Seminar', $result['data']['attributes']['title']);
        self::assertCount(2, $result['data']['relationships']['events']['data']);
    }

    /**
     * @test
     */
    public function canSeeNullOnEmptyHasOneRelation()
    {
        $this->setUpTestWebsite();
        $this->setUpDatabaseData('tx_rexample_domain_model_event', [
            [
                'title' => 'First event',
                'seminar' => 0
            ]
        ]);
        /** @var DispatcherInterface $dispatcher */
        $dispatcher = $this->getObjectManager()->get(DispatcherInterface::class);
        $response = $dispatcher->dispatch(
            (new ServerRequest('GET', new Uri('/rest3/event/1/')))
                ->withQueryParams([
                    'include' => 'seminar'
                ]),
            new Response()
        );
        $result = json_decode($response->getBody(), true);
        self::assertNotEmpty($result['data']['relationships']['seminar']);
        self::assertNull($result['data']['relationships']['seminar']['data']);
    }

    /**
     * @test
     */
    public function canShowOptions()
    {
        $this->setUpTestWebsite();

        /** @var DispatcherInterface $dispatcher */
        $dispatcher = $this->getObjectManager()->get(DispatcherInterface::class);
        $response = $dispatcher->dispatch(
            new ServerRequest('OPTIONS', new Uri('/rest3/seminar/')),
            new Response()
        );
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('HEAD,GET,PUT,DELETE,OPTIONS', $response->getHeader('Allow')[0]);
        self::assertEquals('null', $response->getBody()->__toString());
    }

}
