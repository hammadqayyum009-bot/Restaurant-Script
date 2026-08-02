<?php

return [
    'delivery_fee' => env('SHOP_DELIVERY_FEE', 10),
    'min_order' => env('SHOP_MIN_ORDER', 0),
    'tax_percent' => env('SHOP_TAX_PERCENT', 0),

    'enable_delivery' => true,
    'enable_pickup' => true,
    'enable_cash' => true,
    'enable_card' => true,

    'reservations_enabled' => true,
    'reservation_open' => '11:00',
    'reservation_close' => '23:00',
    'reservation_max_guests' => 20,

    'reviews_enabled' => true,
    'reviews_auto_approve' => false,
];
