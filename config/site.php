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

    // 'classic' (default, maroon & gold) or 'minimal' (Quiet Minimal —
    // see public/assets/css/theme-quiet-minimal.css). Storefront only.
    'theme' => env('SITE_THEME', 'classic'),

    // Optional per-site colour overrides layered on top of whichever theme
    // above is active (see resources/views/partials/theme-overrides.blade.php).
    // Null means "use the active theme's own default" — nothing is emitted
    // for an unset slot, so leaving all four null renders byte-identically
    // to not having this feature at all.
    'theme_primary' => env('SITE_THEME_PRIMARY', null),
    'theme_accent' => env('SITE_THEME_ACCENT', null),
    'theme_background' => env('SITE_THEME_BACKGROUND', null),
    'theme_text' => env('SITE_THEME_TEXT', null),

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
