<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Theme Park Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default theme park provider that will be used
    | when you don't specify a provider explicitly.
    |
    | Supported: "disney", "seaworld", "universal"
    |
    */
    'default' => env('THEMEPARK_DEFAULT_PROVIDER', 'disney'),

    /*
    |--------------------------------------------------------------------------
    | Theme Park Provider Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure each theme park provider. Each provider has its
    | own configuration requirements based on their API system.
    |
    */
    'providers' => [
        'disney' => [
            'driver' => 'redeam',
            'enabled' => env('DISNEY_ENABLED', false),
            'base_url' => env('DISNEY_API_BASE_URL', 'https://api.redeam.com/disney'),
            'api_key' => env('DISNEY_API_KEY'),
            'api_secret' => env('DISNEY_API_SECRET'),
            'timeout' => env('DISNEY_API_TIMEOUT', 30),
            'verify_ssl' => env('DISNEY_VERIFY_SSL', true),
        ],

        'seaworld' => [
            'driver' => 'redeam',
            'enabled' => env('SEAWORLD_ENABLED', false),
            'base_url' => env('SEAWORLD_API_BASE_URL', 'https://api.redeam.com/seaworld'),
            'api_key' => env('SEAWORLD_API_KEY'),
            'api_secret' => env('SEAWORLD_API_SECRET'),
            'timeout' => env('SEAWORLD_API_TIMEOUT', 30),
            'verify_ssl' => env('SEAWORLD_VERIFY_SSL', true),
        ],

        'universal' => [
            'driver' => 'smartorder2',
            'enabled' => env('UNIVERSAL_ENABLED', false),
            'base_url' => env('UNIVERSAL_API_BASE_URL', 'https://api.universalstudios.com/smartorder2'),
            'username' => env('UNIVERSAL_API_USERNAME'),
            'password' => env('UNIVERSAL_API_PASSWORD'),
            'timeout' => env('UNIVERSAL_API_TIMEOUT', 30),
            'verify_ssl' => env('UNIVERSAL_VERIFY_SSL', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for API responses to improve performance
    | and reduce API calls.
    |
    */
    'cache' => [
        'enabled' => env('THEMEPARK_CACHE_ENABLED', true),
        'ttl' => env('THEMEPARK_CACHE_TTL', 3600), // in seconds
        'prefix' => 'themepark_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Enable logging of API requests and responses for debugging purposes.
    |
    */
    'logging' => [
        'enabled' => env('THEMEPARK_LOGGING_ENABLED', false),
        'channel' => env('THEMEPARK_LOG_CHANNEL', 'stack'),
    ],
];
