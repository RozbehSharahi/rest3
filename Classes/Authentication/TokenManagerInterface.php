<?php

namespace RozbehSharahi\Rest3\Authentication;

interface TokenManagerInterface
{

    const TOKEN_NAME = 'rest3_token';

    /**
     * @param string $token
     * @return bool
     */
    public function validate(string $token): bool;

    /**
     * @param string $userIdentification
     * @return string
     */
    public function createToken(string $userIdentification): string;

    /**
     * @param string $token
     * @return string
     */
    public function getUserIdByToken(string $token): string;

}
