<?php

use Iabduul7\ThemeParkAdapters\Exceptions\ThemeParkApiException;
use Iabduul7\ThemeParkAdapters\Providers\Disney\DisneyRedeamAdapter;
use Iabduul7\ThemeParkAdapters\Providers\SeaWorld\SeaWorldRedeamAdapter;
use Iabduul7\ThemeParkAdapters\Providers\Universal\UniversalSmartOrder2Adapter;

it('can instantiate disney adapter with valid config', function () {
    $adapter = new DisneyRedeamAdapter([
        'api_key' => 'test_key',
        'api_secret' => 'test_secret',
        'base_url' => 'https://api.test.com',
    ]);

    expect($adapter)->toBeInstanceOf(DisneyRedeamAdapter::class);
    expect($adapter->getProviderName())->toBe('Disney (Redeam)');
});

it('throws exception for disney adapter with invalid config', function () {
    new DisneyRedeamAdapter([
        'api_key' => 'test_key',
        // missing api_secret and base_url
    ]);
})->throws(ThemeParkApiException::class);

it('can instantiate seaworld adapter with valid config', function () {
    $adapter = new SeaWorldRedeamAdapter([
        'api_key' => 'test_key',
        'api_secret' => 'test_secret',
        'base_url' => 'https://api.test.com',
    ]);

    expect($adapter)->toBeInstanceOf(SeaWorldRedeamAdapter::class);
    expect($adapter->getProviderName())->toBe('SeaWorld (Redeam)');
});

it('can instantiate universal adapter with valid config', function () {
    $adapter = new UniversalSmartOrder2Adapter([
        'username' => 'test_user',
        'password' => 'test_pass',
        'base_url' => 'https://api.test.com',
    ]);

    expect($adapter)->toBeInstanceOf(UniversalSmartOrder2Adapter::class);
    expect($adapter->getProviderName())->toBe('Universal (SmartOrder2)');
});

it('throws exception for universal adapter with invalid config', function () {
    new UniversalSmartOrder2Adapter([
        'username' => 'test_user',
        // missing password and base_url
    ]);
})->throws(ThemeParkApiException::class);
