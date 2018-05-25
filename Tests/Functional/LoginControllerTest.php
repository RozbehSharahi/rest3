<?php

namespace TYPO3\CMS\Core\Tests\Functional;

use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\stream_for;
use RozbehSharahi\Rest3\Authentication\TokenManagerInterface;
use RozbehSharahi\Rest3\DispatcherInterface;
use RozbehSharahi\Rest3\Exception;
use RozbehSharahi\Rest3\Tests\Functional\FunctionalTestBase;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Frontend\Utility\EidUtility;
use TYPO3\CMS\Saltedpasswords\Salt\SaltFactory;

class LoginControllerTest extends FunctionalTestBase
{

    /**
     * @test
     */
    public function canSignIn()
    {
        $GLOBALS['TSFE']->fe_user = EidUtility::initFeUser();
        $this->setUpTestWebsite();
        $this->setUpDatabaseData('fe_users', [
            [
                'pid' => 1,
                'username' => 'test-user',
                'password' => SaltFactory::getSaltingInstance()->getHashedPassword('test-password')
            ]
        ]);

        /** @var DispatcherInterface $dispatcher */
        $dispatcher = $this->getObjectManager()->get(DispatcherInterface::class);
        $response = $dispatcher->dispatch(
            new ServerRequest(new Uri('/rest3/login/sign-in'), 'POST', stream_for(json_encode([
                'username' => 'test-user',
                'password' => 'test-password'
            ]))),
            new Response()
        );
        $result = json_decode($response->getBody(), true);
        self::assertNotEquals('Wrong credentials', $result['errors'][0]['detail']);
        self::assertEquals('Logged in successfully', $result['message']);
        self::assertEquals(1, $GLOBALS['TSFE']->fe_user->user['uid']);
    }

    /**
     * @test
     */
    public function canGetAuthenticationToken()
    {
        $GLOBALS['TSFE']->fe_user = EidUtility::initFeUser();
        $this->setUpTestWebsite();
        $this->setUpDatabaseData('fe_users', [
            [
                'pid' => 1,
                'username' => 'test-user',
                'password' => SaltFactory::getSaltingInstance()->getHashedPassword('test-password')
            ]
        ]);

        /** @var DispatcherInterface $dispatcher */
        $dispatcher = $this->getObjectManager()->get(DispatcherInterface::class);
        /** @var TokenManagerInterface $tokenManager */
        $tokenManager = $this->getObjectManager()->get(TokenManagerInterface::class);

        $response = $dispatcher->dispatch(
            new ServerRequest(new Uri('/rest3/login/user'), 'POST', stream_for(json_encode([
                'username' => 'test-user',
                'password' => 'test-password'
            ]))),
            new Response()
        );
        $result = json_decode($response->getBody(), true);
        self::assertEquals(400, $result['errors'][0]['status']);

        $response = $dispatcher->dispatch(
            new ServerRequest(new Uri('/rest3/login/authentication-token'), 'POST', stream_for(json_encode([
                'username' => 'test-user',
                'password' => 'test-password'
            ]))),
            new Response()
        );
        $result = json_decode($response->getBody(), true);
        self::assertEquals(200, $response->getStatusCode());

        $token = $result['authentication']['token'];
        self::assertTrue($tokenManager->validate($token));
        self::assertEquals(1, $tokenManager->getUserIdByToken($token));

        Exception::setDebugMode(true);

        $response = $dispatcher->dispatch(
            new ServerRequest(new Uri('/rest3/login/user'), 'POST', stream_for(json_encode([
                TokenManagerInterface::TOKEN_NAME => $token
            ]))),
            new Response()
        );
        $result = json_decode($response->getBody(), true);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(1, $result['data']['id']);
    }

}
