<?php

namespace Iabduul7\ThemeParkAdapters\Tests\Adapters;

use Iabduul7\ThemeParkAdapters\Tests\AdapterTestCase;
use Iabduul7\ThemeParkAdapters\TokenRepository\CacheTokenRepository;

class CacheTokenRepositoryTest extends AdapterTestCase
{
    public function test_a_ttl_within_the_safety_buffer_is_never_cached(): void
    {
        $repository = new CacheTokenRepository('test');

        $repository->storeToken('t', 30);

        $this->assertNull($repository->getValidToken());
    }

    public function test_a_ttl_beyond_the_safety_buffer_is_cached(): void
    {
        $repository = new CacheTokenRepository('test');

        $repository->storeToken('t', 3600);

        $this->assertSame('t', $repository->getValidToken());
    }
}
