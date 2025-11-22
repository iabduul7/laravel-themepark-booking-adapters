<?php

namespace Iabduul7\ThemeParkAdapters\Contracts;

interface TokenRepositoryInterface
{
    /**
     * Get a valid token if one exists
     *
     * @return string|null
     */
    public function getValidToken(): ?string;

    /**
     * Store a new token with expiration
     *
     * @param string $token
     * @param int $expiresIn Seconds until expiration
     * @return void
     */
    public function storeToken(string $token, int $expiresIn): void;
}
