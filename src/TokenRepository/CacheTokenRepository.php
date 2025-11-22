<?php

namespace Iabduul7\ThemeParkAdapters\TokenRepository;

use Iabduul7\ThemeParkAdapters\Contracts\TokenRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class CacheTokenRepository implements TokenRepositoryInterface
{
    protected string $cacheKey;

    public function __construct(string $provider = 'smartorder')
    {
        $this->cacheKey = "themepark_{$provider}_token";
    }

    public function getValidToken(): ?string
    {
        return Cache::get($this->cacheKey);
    }

    public function storeToken(string $token, int $expiresIn): void
    {
        // Store with a 60 second buffer to account for request time
        Cache::put($this->cacheKey, $token, $expiresIn - 60);
    }
}
