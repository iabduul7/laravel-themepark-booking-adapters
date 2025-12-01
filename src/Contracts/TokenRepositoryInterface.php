<?php

namespace Iabduul7\ThemeParkAdapters\Contracts;

interface TokenRepositoryInterface
{
    /**
     * Get a valid token if one exists.
     */
    public function getValidToken(): ?string;

    /**
     * Store a new token with expiration.
     *
     * @param  int  $expiresIn  Seconds until expiration
     */
    public function storeToken(string $token, int $expiresIn): void;
}
