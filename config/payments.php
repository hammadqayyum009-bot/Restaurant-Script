<?php

use App\Payments\Drivers\CashOnDeliveryDriver;
use App\Payments\Drivers\MoyasarDriver;

return [
    'currency' => env('PAYMENTS_CURRENCY', 'SAR'),

    /**
     * Driver classes registered into App\Payments\PaymentDriverRegistry on
     * boot. Adding a driver means adding its class here — nothing else in
     * the registry or its callers changes.
     */
    'drivers' => [
        CashOnDeliveryDriver::class,
        MoyasarDriver::class,
    ],
];
