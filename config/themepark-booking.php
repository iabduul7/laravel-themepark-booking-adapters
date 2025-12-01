<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Order Model Configuration
    |--------------------------------------------------------------------------
    |
    | Specify the Order model that the theme park booking details should
    | be associated with. This allows the package to work with your
    | application's existing Order structure.
    |
    */
    'order_model' => env('THEMEPARK_BOOKING_ORDER_MODEL', 'App\\Models\\Order'),

    /*
    |--------------------------------------------------------------------------
    | Default Adapter
    |--------------------------------------------------------------------------
    |
    | This is the default booking adapter that will be used when no specific
    | adapter is specified.
    |
    */
    'default' => env('THEMEPARK_BOOKING_DEFAULT_ADAPTER', 'redeam.disney'),

    /*
    |--------------------------------------------------------------------------
    | Redeam Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Redeam API (Disney World, United Parks, etc.)
    |
    */
    'redeam' => [
        'base_url' => env('REDEAM_BASE_URL', 'https://booking.redeam.io/v1.2'),
        'api_key' => env('REDEAM_API_KEY'),
        'api_secret' => env('REDEAM_API_SECRET'),
        'timeout' => env('REDEAM_TIMEOUT', 30),
        'disney' => [
            'supplier_id' => env('REDEAM_DISNEY_SUPPLIER_ID', '20'),
            'enabled' => env('REDEAM_DISNEY_ENABLED', true),
        ],
        'united_parks' => [
            'supplier_id' => env('REDEAM_UNITED_PARKS_SUPPLIER_ID'),
            'enabled' => env('REDEAM_UNITED_PARKS_ENABLED', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SmartOrder Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SmartOrder API (Universal Studios, etc.)
    |
    */
    'smartorder' => [
        'base_url' => env('SMARTORDER_BASE_URL', 'https://QACorpAPI.ucdp.net'),
        'customer_id' => env('SMARTORDER_CUSTOMER_ID', '134853'),
        'client_username' => env('SMARTORDER_CLIENT_USERNAME'),
        'client_secret' => env('SMARTORDER_CLIENT_SECRET'),
        'approved_suffix' => env('SMARTORDER_APPROVED_SUFFIX', '-2KNOW'),
        'timeout' => env('SMARTORDER_TIMEOUT', 30),
        'enabled' => env('SMARTORDER_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Booking Adapters Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for each booking adapter. Each adapter can be enabled
    | or disabled and has its own specific configuration.
    |
    */
    'adapters' => [
        'redeam.disney' => [
            'enabled' => env('REDEAM_DISNEY_ENABLED', true),
            'api_key' => env('REDEAM_API_KEY'),
            'api_secret' => env('REDEAM_API_SECRET'),
            'supplier_id' => env('REDEAM_DISNEY_SUPPLIER_ID', '20'),
            'base_url' => env('REDEAM_BASE_URL', 'https://booking.redeam.io/v1.2'),
            'timeout' => env('REDEAM_TIMEOUT', 30),
            'connect_timeout' => 5,
        ],

        'redeam.united_parks' => [
            'enabled' => env('REDEAM_UNITED_PARKS_ENABLED', true),
            'api_key' => env('REDEAM_API_KEY'),
            'api_secret' => env('REDEAM_API_SECRET'),
            'supplier_id' => env('REDEAM_UNITED_PARKS_SUPPLIER_ID'),
            'base_url' => env('REDEAM_BASE_URL', 'https://booking.redeam.io/v1.2'),
            'timeout' => env('REDEAM_TIMEOUT', 30),
            'connect_timeout' => 5,
        ],

        'smartorder' => [
            'enabled' => env('SMARTORDER_ENABLED', true),
            'base_url' => env('SMARTORDER_BASE_URL', 'https://QACorpAPI.ucdp.net'),
            'customer_id' => env('SMARTORDER_CUSTOMER_ID', '134853'),
            'client_username' => env('SMARTORDER_CLIENT_USERNAME'),
            'client_secret' => env('SMARTORDER_CLIENT_SECRET'),
            'approved_suffix' => env('SMARTORDER_APPROVED_SUFFIX', '-2KNOW'),
            'timeout' => env('SMARTORDER_TIMEOUT', 30),
            'connect_timeout' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Appends Configuration
    |--------------------------------------------------------------------------
    |
    | These are the attributes that will be appended to your Order model
    | when using the HasThemeParkBookingAttributes trait.
    |
    */
    'model_appends' => [
        // SmartOrder/Universal attributes
        'has_smartorder_items',
        'smartorder_items',
        'smartorder_external_order_id',
        'smartorder_galaxy_order_id',
        'smartorder_booking_data',

        // Disney attributes
        'has_disney_items',
        'disney_items',
        'disney_hold_id',
        'disney_booking_id',
        'disney_booking_data',
        'disney_reservation_number',
        'disney_voucher',
        'disney_reference_number',

        // United Parks attributes
        'has_united_parks_items',
        'united_parks_items',
        'united_parks_hold_id',
        'united_parks_booking_id',
        'united_parks_booking_data',
        'united_parks_reservation_number',
        'united_parks_voucher',
        'united_parks_reference_number',

        // General attributes
        'is_cancelled_by_provider',
        'all_booking_references',
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching settings for adapters.
    |
    */
    'cache' => [
        'default_ttl' => 300, // 5 minutes
        'availability_ttl' => 60, // 1 minute for availability checks
        'products_ttl' => 3600, // 1 hour for product data
        'pricing_ttl' => 300, // 5 minutes for pricing
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for product synchronization.
    |
    */
    'sync' => [
        'batch_size' => 100,
        'max_execution_time' => 300, // 5 minutes
        'retry_attempts' => 3,
        'retry_delay' => 5, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging for booking operations.
    |
    */
    'logging' => [
        'enabled' => env('THEMEPARK_BOOKING_LOGGING', true),
        'level' => env('THEMEPARK_BOOKING_LOG_LEVEL', 'info'),
        'channel' => env('THEMEPARK_BOOKING_LOG_CHANNEL', 'stack'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Configure error handling and circuit breaker settings.
    |
    */
    'error_handling' => [
        'max_retries' => 3,
        'retry_delay' => 1000, // milliseconds
        'circuit_breaker' => [
            'failure_threshold' => 5,
            'timeout' => 60, // seconds
            'success_threshold' => 2,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Voucher Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for voucher generation and storage.
    |
    */
    'voucher' => [
        'storage_disk' => env('THEMEPARK_VOUCHER_DISK', 'local'),
        'templates_path' => 'voucher-templates',
        'default_format' => 'pdf',
        'include_qr_code' => true,
        'include_barcode' => true,
        'expiry_days' => 365,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for API calls to prevent hitting provider limits.
    |
    */
    'rate_limiting' => [
        'enabled' => true,
        'requests_per_minute' => 60,
        'burst_limit' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring and Metrics
    |--------------------------------------------------------------------------
    |
    | Enable monitoring and metrics collection.
    |
    */
    'monitoring' => [
        'enabled' => env('THEMEPARK_BOOKING_MONITORING', false),
        'metrics' => [
            'track_response_times' => true,
            'track_success_rates' => true,
            'track_error_rates' => true,
        ],
        'alerts' => [
            'error_threshold' => 0.1, // 10% error rate
            'response_time_threshold' => 5000, // 5 seconds
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Table Names
    |--------------------------------------------------------------------------
    |
    | Customize the table names used by the package models.
    |
    */
    'tables' => [
        'order_details_redeam' => 'order_details_redeam',
        'order_details_universal' => 'order_details_universal',
    ],
];