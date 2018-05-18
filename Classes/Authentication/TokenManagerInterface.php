<?php

namespace RozbehSharahi\Rest3\Authentication;

interface TokenManagerInterface
{

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
    public function getUserByToken(string $token): string;

}
