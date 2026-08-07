<?php

return [
    // Branding for the admin panel itself, kept separate from the public site
    // so the two can look and be named differently.
    'name' => env('PANEL_NAME', 'Al Waha Admin'),
    'short_name' => env('PANEL_SHORT_NAME', 'Admin'),
    'logo' => env('PANEL_LOGO', null),
    'favicon' => env('PANEL_FAVICON', null),
    'footer_note' => env('PANEL_FOOTER', 'Al Waha Restaurant Management'),
];
