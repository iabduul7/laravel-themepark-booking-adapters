<?php

// config for CodeCreatives/LaravelRedeam
return [
    'disney' => [
        'supplier_id' => env('REDEAM_DISNEY_SUPPLIER_ID'),
        'host' => env('REDEAM_API_HOST', 'booking.redeam.io'),
        'version' => env('REDEAM_API_VERSION', 'v1.2'),
        'api_key' => env('REDEAM_DISNEY_API_KEY'),
        'api_secret' => env('REDEAM_DISNEY_API_SECRET'),
    ],

    'united_parks' => [
        'supplier_id' => null,
        'host' => env('REDEAM_API_HOST', 'booking.redeam.io'),
        'version' => env('REDEAM_API_VERSION', 'v1.2'),
        'api_key' => env('REDEAM_UNITED_PARKS_API_KEY'),
        'api_secret' => env('REDEAM_UNITED_PARKS_API_SECRET'),
    ],
];
