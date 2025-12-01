<?php

return [
    'ticket_discounts_step8' => [
        '0OR' => 0.1111,
        '1OR' => 0.1111,
        '2OR' => 0.1111,
        '3OR' => 0.1111,
        'JOR' => 0.1111,
        'KOR' => 0.12,
        'LOR' => 0.12,
        'MOR' => 0.12,
        'JOP' => 0.1111,
        'KOP' => 0.12,
        'LOP' => 0.12,
        'MOP' => 0.12,
    ],

    'fl_discount_percentage' => 0.05,

    'accepts_option_codes' => [
        'one-park-per-day_std_gst_dest_sales_annual_v',
        'one-park-per-day_water-sport_std_gst_dest_sales_annual_v',
        'park-hopper_std_gst_dest_sales_annual_v',
        'park-hopper-plus-v2_std_gst_dest_sales_annual_v',

        'one-park-per-day_water-sport_fl_resident_dest_sales_annualfl_v',
        'park-hopper_fl_resident_dest_sales_annualfl_v',
        'park-hopper-plus-v2_fl_resident_dest_sales_annualfl_v',
        'one-park-per-day_fl_resident_dest_sales_annualfl_v',
    ],

    'special-events' => [
        'commission-percentage' => 2.00,
        'adjustment-percentage' => 0.1111,
        'DAHDHS' => [
            'title' => "DISNEY AFTER HOURS AT DISNEY'S HOLLYWOOD STUDIOS",
            'description' => "Disney after hours at Disney's Hollywood Studios is a special event that requires a separate admission. This ticket is valid only for admission to the Disney after hours at Disney's Hollywood Studios Park from 7:00pm to 12:30am on the date specified. The event will be held from 9.30pm to 12.30am on the date specified. A park reservation is not required however, reservation requirements are subject to change. Parking is not included.",
        ],
        'DAHMK' => [
            'title' => 'DISNEY AFTER HOURS AT MAGIC KINGDOM',
            'description' => 'Disney after hours at Magic Kingdom Park is a special event that requires a separate admission. This ticket is valid only for admission to the Disney after hours at Magic Kingdom Park from 7:00pm to 01:00am on the date specified. The event will be held from 10.00pm to 1.00am on the date specified. A park reservation is not required however, reservation requirements are subject to change. Parking is not included.',
        ],
        'MKCHRISTMAS' => [
            'title' => "MICKEY'S VERY MERRY CHRISTMAS PARTY",
            'description' => "Mickey's Very Merry Christmas Party at Magic Kingdom Park is a special event that requires a separate admission. This ticket is valid only for admission to the Mickey's Very Merry Christmas Party at Magic Kingdom Park from 7:00pm to 11:59pm on the date specified. A park reservation is not required however, reservation requirements are subject to change. Parking is not included.",
        ],
    ],

    'water-park' => [
        'commission-percentage' => 2.00,
        'adjustment-percentage' => 0.1111,
        'gate-prices' => [
            'without-blockout' => [
                'adult' => 6900,
                'child' => 6300,
            ],
            'with-blockout' => [
                'adult' => 6400,
                'child' => 5800,
            ],
        ],
    ],

    'theme-park' => [
        'base' => 'Visit one theme park each day of your ticket.',
        'park-hopper' => 'Visit multiple theme parks each day of your ticket.',
        'park-hopper-plus' => 'Visit multiple theme parks each day of your ticket. Plus, enjoy a certain number of visits to a water park or other Walt Disney World fun.',
        'water-sport' => 'Visit one theme park each day of your ticket. Plus, enjoy a certain number of visits to a water park or other Walt Disney World fun.',
    ],
];
