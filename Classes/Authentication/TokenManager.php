<?php

namespace RozbehSharahi\Rest3\Authentication;

use ReallySimpleJWT\Token;
use RozbehSharahi\Rest3\Service\ConfigurationService;

class TokenManager implements TokenManagerInterface
{

    /**
     * @var ConfigurationService
     */
    protected $configurationService;

    /**
     * @param ConfigurationService $configurationService
     */
    public function injectConfigurationService(ConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;
    }

    /**
     * @param string $token
     * @return bool
     */
    public function validate(string $token): bool
    {
        try {
            return Token::validate($token, $this->getSecret());
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * @param string $userIdentification
     * @return string
     */
    public function createToken(string $userIdentification): string
    {
        return Token::getToken($userIdentification, $this->getSecret(), $this->getExpiration(), $this->getIssuer());
    }

    /**
     * @param string $token
     * @return string
     */
    public function getUserIdByToken(string $token): string
    {
        return json_decode(Token::getPayload($token), true)['user_id'] ?: null;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getSecret(): string
    {
        $secret = $this->configurationService->getSetting('authentication.secret');

        if (empty($secret)) {
            throw new \Exception('You have to set a secret! `plugin.tx_rest3.settings.authentication.secret`');
        }

        return $secret;
    }

    /**
     * @return string
     */
    protected function getExpiration(): string
    {
        return $this->configurationService->getSetting('authentication.expiration') ?: 'NOW + 1 days';
    }

    /**
     * @return string
     */
    protected function getIssuer(): string
    {
        return $this->configurationService->getSetting('authentication.issuer') ?: 'REST3';
    }

}
