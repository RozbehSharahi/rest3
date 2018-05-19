<?php

namespace RozbehSharahi\Rest3\Tests\Functional;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use RozbehSharahi\Rest3\DispatcherInterface;

class NormalizerTest extends FunctionalTestBase
{

    /**
     * @test
     */
    public function canRegisterNormalizer()
    {
        $this->setUpTestWebsite('
            plugin.tx_rest3.settings.domainObjectNormalizers {
                30 = RozbehSharahi\Rexample\SeminarNormalizer
            }
        ');
        $this->setUpDatabaseData('tx_rexample_domain_model_seminar', [
            [
                'title' => 'Title',
                'description' => 'Description'
            ]
        ]);
        /** @var DispatcherInterface $dispatcher */
        $dispatcher = $this->getObjectManager()->get(DispatcherInterface::class);
        $response = $dispatcher->dispatch(
            new ServerRequest('GET', new Uri('/rest3/seminar/1')),
            new Response()
        );
        $result = json_decode($response->getBody(), true);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('Title', $result['data']['attributes']['title']);
        self::assertEmpty($result['data']['attributes']['description']);
    }

}

