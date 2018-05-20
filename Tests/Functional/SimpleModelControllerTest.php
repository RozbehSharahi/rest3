<?php

namespace RozbehSharahi\Rest3\Tests\Functional;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use function GuzzleHttp\Psr7\stream_for;
use GuzzleHttp\Psr7\Uri;
use RozbehSharahi\Rest3\DispatcherInterface;
use RozbehSharahi\Rest3\Exception;
use RozbehSharahi\Rexample\Domain\Model\Event;
use RozbehSharahi\Rexample\Domain\Model\Location;
use RozbehSharahi\Rexample\Domain\Model\Seminar;
use RozbehSharahi\Rexample\Domain\Repository\EventRepository;
use RozbehSharahi\Rexample\Domain\Repository\SeminarRepository;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;

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

        self::assertEquals(400, $response->getStatusCode());
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
        $result = json_decode($response->getBody(), true);
        self::assertEquals(200, $response->getStatusCode());
        self::assertCount(2, $result['data']);
        self::assertEquals('First seminar', $result['data'][0]['attributes']['title']);
        self::assertEquals('Second seminar', $result['data'][1]['attributes']['title']);
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
        $result = json_decode($response->getBody(), true);
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('Not found', $result['errors']['0']['detail']);
        self::assertEquals('400', $result['errors']['0']['status']);
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
                'seminar' => 1,
            ],
            [
                'title' => 'Second event',
                'seminar' => 1
            ]
        ]);
        $this->setUpDatabaseData('tx_rexample_domain_model_location', [
            [
                'uid' => 1,
                'title' => 'A Location'
            ],
            [
                'uid' => 2,
                'title' => 'A second Location'
            ]
        ]);
        $this->setUpDatabaseData('tx_rexample_location_event_mm', [
            [
                'uid_local' => 1,
                'uid_foreign' => 1,
            ],
            [
                'uid_local' => 2,
                'uid_foreign' => 1,
            ]
        ]);

        /** @var DispatcherInterface $dispatcher */
        $dispatcher = $this->getObjectManager()->get(DispatcherInterface::class);
        $response = $dispatcher->dispatch(
            (new ServerRequest('GET', new Uri('/rest3/seminar/1/')))
                ->withQueryParams([
                    'include' => 'events,events.locations'
                ]),
            new Response()
        );
        $result = json_decode($response->getBody(), true);
        self::assertEquals(200, $response->getStatusCode());
        self::assertNotEmpty($result);
        self::assertEquals('A Seminar', $result['data']['attributes']['title']);
        self::assertCount(2, $result['data']['relationships']['events']['data']);
        self::assertCount(2, array_filter($result['included'], function ($item) {
            return $item['type'] === Location::class;
        }));
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
    public function canUpdate()
    {
        $this->setUpTestWebsite();
        $this->setUpDatabaseData('tx_rexample_domain_model_seminar', [
            [
                'title' => 'First Seminar',
            ]
        ]);
        $this->setUpDatabaseData('tx_rexample_domain_model_event', [
            [
                'title' => 'First event',
                'seminar' => 1
            ]
        ]);
        /** @var DispatcherInterface $dispatcher */
        $dispatcher = $this->getObjectManager()->get(DispatcherInterface::class);
        $response = $dispatcher->dispatch(
            (new ServerRequest('PATCH', new Uri('/rest3/event/1/')))
                ->withBody(stream_for(json_encode([
                    'data' => [
                        'id' => 1,
                        'type' => Event::class,
                        'attributes' => [
                            'title' => 'First event (updated)'
                        ]
                    ]
                ]))),
            new Response()
        );
        $result = json_decode($response->getBody(), true);
        self::assertNotEmpty($result);
        self::assertEquals(Event::class, $result['data']['type']);
        self::assertEquals(1, $result['data']['id']);
        self::assertEquals('First event (updated)', $result['data']['attributes']['title']);

        /** @var RepositoryInterface $repository */
        $repository = $this->getObjectManager()->get(EventRepository::class);
        $repository->setDefaultQuerySettings((new Typo3QuerySettings())->setRespectStoragePage(false));

        /** @var Event $event */
        $event = $repository->findByUid(1);
        self::assertEquals('First event (updated)', $event->getTitle());
    }

    /**
     * @test
     */
    public function canSeeErrorOnNonExistingAttribute()
    {
        $this->setUpTestWebsite();
        $this->setUpDatabaseData('tx_rexample_domain_model_seminar', [
            [
                'title' => 'First Seminar',
            ]
        ]);
        /** @var DispatcherInterface $dispatcher */
        $dispatcher = $this->getObjectManager()->get(DispatcherInterface::class);
        $response = $dispatcher->dispatch(
            (new ServerRequest('PATCH', new Uri('/rest3/seminar/1/')))
                ->withBody(stream_for(json_encode([
                    'data' => [
                        'id' => 1,
                        'type' => Seminar::class,
                        'attributes' => [
                            'bla-attribute' => 'bla-value'
                        ]
                    ]
                ]))),
            new Response()
        );
        self::assertContains('Property `bla-attribute` can not', $response->getBody()->__toString());
    }

    /**
     * @test
     */
    public function canSeeErrorOnSettingReadOnlyAttribute()
    {
        $this->setUpTestWebsite('
            plugin.tx_rest3.settings.routes.seminar.readOnlyProperties {
                1 = title
            }
        ');
        $this->setUpDatabaseData('tx_rexample_domain_model_seminar', [
            [
                'title' => 'First Seminar',
            ]
        ]);
        /** @var DispatcherInterface $dispatcher */
        $dispatcher = $this->getObjectManager()->get(DispatcherInterface::class);
        $response = $dispatcher->dispatch(
            (new ServerRequest('PATCH', new Uri('/rest3/seminar/1/')))
                ->withBody(stream_for(json_encode([
                    'data' => [
                        'id' => 1,
                        'type' => Seminar::class,
                        'attributes' => [
                            'title' => 'bla-value'
                        ]
                    ]
                ]))),
            new Response()
        );
        self::assertContains('Property `title` can not', $response->getBody()->__toString());
    }

    /**
     * @test
     */
    public function canCreate()
    {
        $this->setUpTestWebsite();

        /** @var RepositoryInterface $repository */
        $repository = $this->getObjectManager()->get(SeminarRepository::class);
        self::assertCount(0, $repository->findAll()->toArray());

        /** @var DispatcherInterface $dispatcher */
        $dispatcher = $this->getObjectManager()->get(DispatcherInterface::class);
        $response = $dispatcher->dispatch(
            (new ServerRequest('POST', new Uri('/rest3/seminar/')))
                ->withBody(stream_for(json_encode([
                    'data' => [
                        'id' => 1,
                        'type' => Seminar::class,
                        'attributes' => [
                            'title' => 'First seminar'
                        ]
                    ]
                ]))),
            new Response()
        );
        $result = json_decode($response->getBody(), true);

        self::assertEquals('First seminar', $result['data']['attributes']['title']);
        self::assertCount(1, $repository->findAll());

        /** @var Seminar $model */
        $model = $repository->findByUid(1);
        self::assertEquals('First seminar', $model->getTitle());
    }

    /**
     * @test
     */
    public function canSetHasOneRelation()
    {
        $this->setUpTestWebsite();
        $this->setUpDatabaseData('tx_rexample_domain_model_seminar', [
            [
                'title' => 'A seminar'
            ]
        ]);
        /** @var RepositoryInterface $repository */
        $repository = $this->getObjectManager()->get(EventRepository::class);
        /** @var PersistenceManager $persistenceManager */
        $persistenceManager = $this->getObjectManager()->get(PersistenceManager::class);

        /** @var DispatcherInterface $dispatcher */
        $dispatcher = $this->getObjectManager()->get(DispatcherInterface::class);
        $dispatcher->dispatch(
            (new ServerRequest('POST', new Uri('/rest3/event/')))
                ->withBody(stream_for(json_encode([
                    'data' => [
                        'type' => Event::class,
                        'attributes' => [
                            'title' => 'Created event'
                        ],
                        'relationships' => [
                            'seminar' => 1
                        ]
                    ]
                ]))),
            new Response()
        );

        /** @var Event $event */
        $event = $repository->findByUid(1);
        self::assertInstanceOf(Event::class, $event);
        self::assertEquals('Created event', $event->getTitle());
        self::assertEquals('A seminar', $event->getSeminar()->getTitle());

        $dispatcher->dispatch(
            (new ServerRequest('PATCH', new Uri('/rest3/event/1')))
                ->withBody(stream_for(json_encode([
                    'data' => [
                        'type' => Event::class,
                        'attributes' => [
                            'title' => 'Created event (updated)'
                        ],
                        'relationships' => [
                            'seminar' => 0
                        ]
                    ]
                ]))),
            new Response()
        );

        $persistenceManager->clearState();
        $event = $repository->findByUid(1);
        self::assertEquals('Created event (updated)', $event->getTitle());
        self::assertNull($event->getSeminar());
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

    /**
     * @test
     */
    public function canSetManyToManyRelation()
    {
        $this->setUpTestWebsite();
        $this->setUpDatabaseData('tx_rexample_domain_model_location', [
            [
                'pid' => 1,
                'title' => 'A location'
            ],
            [
                'pid' => 1,
                'title' => 'Another location'
            ]
        ]);

        Exception::setDebugMode(true);

        /** @var RepositoryInterface $repository */
        $repository = $this->getObjectManager()->get(EventRepository::class);
        /** @var PersistenceManager $persistenceManager */
        $persistenceManager = $this->getObjectManager()->get(PersistenceManager::class);

        /** @var DispatcherInterface $dispatcher */
        $dispatcher = $this->getObjectManager()->get(DispatcherInterface::class);
        $response = $dispatcher->dispatch(
            (new ServerRequest('POST', new Uri('/rest3/event/')))
                ->withBody(stream_for(json_encode([
                    'data' => [
                        'type' => Event::class,
                        'attributes' => [
                            'title' => 'Created event'
                        ],
                        'relationships' => [
                            'locations' => [2, 1]
                        ]
                    ]
                ]))),
            new Response()
        );

        self::assertNotEquals(400, $response->getStatusCode());

        $persistenceManager->clearState();
        /** @var Event $event */
        $event = $repository->findByUid(1);
        self::assertInstanceOf(Event::class, $event);
        self::assertEquals('Created event', $event->getTitle());
        self::assertCount(2, $event->getLocations());
        /** @var Location[] $locations */
        $locations = $event->getLocations()->toArray();
        self::assertEquals('Another location', $locations[0]->getTitle());

        $dispatcher->dispatch(
            (new ServerRequest('PATCH', new Uri('/rest3/event/1')))
                ->withBody(stream_for(json_encode([
                    'data' => [
                        'type' => Event::class,
                        'attributes' => [
                            'title' => 'An event (updated)'
                        ],
                        'relationships' => [
                            'locations' => []
                        ]
                    ]
                ]))),
            new Response()
        );

        $persistenceManager->clearState();
        $event = $repository->findByUid(1);
        self::assertEquals('An event (updated)', $event->getTitle());
        self::assertCount(0, $event->getLocations());
    }

}
