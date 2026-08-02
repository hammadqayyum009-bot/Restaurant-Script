<?php

return [
    'name' => env('SITE_NAME', 'Al Waha Restaurant'),
    'tagline' => env('SITE_TAGLINE', 'Authentic Gulf Cuisine, Served with Soul'),
    'phone' => env('SITE_PHONE', '+971 4 123 4567'),
    'whatsapp' => env('SITE_WHATSAPP', '971501234567'),
    'email' => env('SITE_EMAIL', 'hello@alwaharestaurant.com'),
    'address' => env('SITE_ADDRESS', 'Sheikh Zayed Road, Dubai, United Arab Emirates'),
    'currency' => env('SITE_CURRENCY', 'AED'),
    'opening_hours' => env('SITE_HOURS', 'Daily: 11:00 AM - 12:00 AM'),

    // Uploaded from the admin panel; null falls back to the built-in vector mark.
    'logo' => env('SITE_LOGO', null),
    'favicon' => env('SITE_FAVICON', null),
    'meta_description' => env('SITE_META_DESCRIPTION', null),
    'social' => [
        'facebook' => env('SITE_FACEBOOK', '#'),
        'instagram' => env('SITE_INSTAGRAM', '#'),
        'twitter' => env('SITE_TWITTER', '#'),
        'tiktok' => env('SITE_TIKTOK', '#'),
    ],
];
