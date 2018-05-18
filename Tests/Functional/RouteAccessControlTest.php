<?php

namespace TYPO3\CMS\Core\Tests\Functional;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use RozbehSharahi\Rest3\DispatcherInterface;
use RozbehSharahi\Rest3\Tests\Functional\FunctionalTestBase;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Saltedpasswords\Salt\SaltFactory;

class RouteAccessControlTest extends FunctionalTestBase
{

    /**
     * @test
     */
    public function canSeePermissionDeniedOnNoAccessToShow()
    {
        $this->setUpTestWebsite('
            plugin.tx_rest3.settings.routes.seminar.restrictive = 1
            plugin.tx_rest3.settings.routes.seminar.permissions.show = 1
        ');
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
        self::assertNotEquals('Permission denied', $result['errors'][0]['detail']);

        $response = $dispatcher->dispatch(
            (new ServerRequest('GET', new Uri('/rest3/seminar/'))),
            new Response()
        );
        $result = json_decode($response->getBody(), true);
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('Permission denied', $result['errors'][0]['detail']);
    }

    /**
     * @test
     */
    public function canOverridePermissionsOnUserGroup()
    {
        $this->setUpTestWebsite('
            plugin.tx_rest3.settings.routes.seminar.restrictive = 1
        ');
        $this->setUpDatabaseData('tx_rexample_domain_model_seminar', [
            [
                'title' => 'A Seminar',
            ]
        ]);
        $this->setUpDatabaseData('fe_users', [
            [
                'username' => 'test-user',
                'password' => SaltFactory::getSaltingInstance()->getHashedPassword('test-password'),
                'usergroup' => 1,
            ]
        ]);
        $this->setUpDatabaseData('fe_groups', [
            [
                'title' => 'Some group',
                'tx_rest3_settings' => 'routes.seminar.permissions.show = 1'
            ]
        ]);

        /** @var DispatcherInterface $dispatcher */
        $dispatcher = $this->getObjectManager()->get(DispatcherInterface::class);

        $response = $dispatcher->dispatch(
            (new ServerRequest('GET', new Uri('/rest3/seminar/1/'))),
            new Response()
        );
        self::assertEquals(400, $response->getStatusCode());

        // Login user
        $this->setUpLoggedInUser(1);

        $response = $dispatcher->dispatch(
            (new ServerRequest('GET', new Uri('/rest3/seminar/1/'))),
            new Response()
        );
        self::assertEquals(200, $response->getStatusCode());
    }

}
