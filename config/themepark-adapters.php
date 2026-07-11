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
            'supplier_id' => env('REDEAM_DISNEY_SUPPLIER_ID'),
            'host' => env('REDEAM_API_HOST', 'booking.redeam.io'),
            'version' => env('REDEAM_API_VERSION', 'v1.2'),
            'api_key' => env('REDEAM_DISNEY_API_KEY'),
            'api_secret' => env('REDEAM_DISNEY_API_SECRET'),
            'timeout' => env('REDEAM_TIMEOUT', 600),
            'verify_ssl' => env('REDEAM_VERIFY_SSL', true),
            // Idempotent reads are retried on connection drops / 5xx; writes never are.
            'retry_attempts' => env('REDEAM_RETRY_ATTEMPTS', 3),
            'retry_sleep_ms' => env('REDEAM_RETRY_SLEEP_MS', 1000),
            // Disney public park-availability (observability) endpoint.
            'park_availability_url' => env('REDEAM_DISNEY_PARK_AVAILABILITY_URL', 'https://dis-obs.redeam.io/disney/park/availability'),
        ],

        'seaworld' => [
            'driver' => 'redeam',
            'supplier_id' => env('REDEAM_UNITED_PARKS_SUPPLIER_ID'), // May be null for United Parks
            'host' => env('REDEAM_API_HOST', 'booking.redeam.io'),
            'version' => env('REDEAM_API_VERSION', 'v1.2'),
            'api_key' => env('REDEAM_UNITED_PARKS_API_KEY'),
            'api_secret' => env('REDEAM_UNITED_PARKS_API_SECRET'),
            'timeout' => env('REDEAM_TIMEOUT', 600),
            'verify_ssl' => env('REDEAM_VERIFY_SSL', true),
            'retry_attempts' => env('REDEAM_RETRY_ATTEMPTS', 3),
            'retry_sleep_ms' => env('REDEAM_RETRY_SLEEP_MS', 1000),
        ],

        'universal' => [
            'driver' => 'smartorder2',
            'customer_id' => env('SMARTORDER_CUSTOMER_ID'),
            'approved_suffix' => env('SMARTORDER_APPROVED_SUFFIX', ''),
            'host' => env('SMARTORDER_API_HOST', 'QACorpAPI.ucdp.net'),
            'client_username' => env('SMARTORDER_CLIENT_USERNAME'),
            'client_secret' => env('SMARTORDER_CLIENT_SECRET'),
            'timeout' => env('SMARTORDER_TIMEOUT', 600),
            'verify_ssl' => env('SMARTORDER_VERIFY_SSL', true),
            'retry_attempts' => env('SMARTORDER_RETRY_ATTEMPTS', 3),
            'retry_sleep_ms' => env('SMARTORDER_RETRY_SLEEP_MS', 1000),
            // OAuth token caching. Upstream disables this (always refresh) because the
            // SmartOrder server can invalidate tokens before local expiry; set false to match.
            'token_cache' => env('SMARTORDER_TOKEN_CACHE', true),
        ],
    ],
];
