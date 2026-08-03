<?php

use App\Payments\Drivers\CashOnDeliveryDriver;

return [
    'currency' => env('PAYMENTS_CURRENCY', 'SAR'),

    /**
     * Minor-unit exponent per currency code — never assume 2. KWD, BHD and
     * OMR use 3 decimal places.
     */
    'currency_exponents' => [
        'SAR' => 2,
        'AED' => 2,
        'QAR' => 2,
        'USD' => 2,
        'EUR' => 2,
        'GBP' => 2,
        'EGP' => 2,
        'PKR' => 2,
        'KWD' => 3,
        'BHD' => 3,
        'OMR' => 3,
        'JPY' => 0,
    ],

    /**
     * Driver classes registered into App\Payments\PaymentDriverRegistry on
     * boot. Adding a driver means adding its class here — nothing else in
     * the registry or its callers changes.
     */
    'drivers' => [
        CashOnDeliveryDriver::class,
    ],
];
