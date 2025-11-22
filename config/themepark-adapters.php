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
            'supplier_id' => env('REDEAM_DISNEY_SUPPLIER_ID'),
            'host' => env('REDEAM_API_HOST', 'booking.redeam.io'),
            'version' => env('REDEAM_API_VERSION', 'v1.2'),
            'api_key' => env('REDEAM_DISNEY_API_KEY'),
            'api_secret' => env('REDEAM_DISNEY_API_SECRET'),
            'timeout' => env('REDEAM_TIMEOUT', 600),
            'verify_ssl' => env('REDEAM_VERIFY_SSL', true),
        ],

        'seaworld' => [
            'driver' => 'redeam',
            'enabled' => env('SEAWORLD_ENABLED', false),
            'supplier_id' => env('REDEAM_UNITED_PARKS_SUPPLIER_ID'), // May be null for United Parks
            'host' => env('REDEAM_API_HOST', 'booking.redeam.io'),
            'version' => env('REDEAM_API_VERSION', 'v1.2'),
            'api_key' => env('REDEAM_UNITED_PARKS_API_KEY'),
            'api_secret' => env('REDEAM_UNITED_PARKS_API_SECRET'),
            'timeout' => env('REDEAM_TIMEOUT', 600),
            'verify_ssl' => env('REDEAM_VERIFY_SSL', true),
        ],

        'universal' => [
            'driver' => 'smartorder2',
            'enabled' => env('UNIVERSAL_ENABLED', false),
            'customer_id' => env('SMARTORDER_CUSTOMER_ID'),
            'approved_suffix' => env('SMARTORDER_APPROVED_SUFFIX', ''),
            'host' => env('SMARTORDER_API_HOST', 'QACorpAPI.ucdp.net'),
            'client_username' => env('SMARTORDER_CLIENT_USERNAME'),
            'client_secret' => env('SMARTORDER_CLIENT_SECRET'),
            'timeout' => env('SMARTORDER_TIMEOUT', 600),
            'verify_ssl' => env('SMARTORDER_VERIFY_SSL', true),
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
