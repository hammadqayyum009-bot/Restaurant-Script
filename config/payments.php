<?php

use App\Payments\Drivers\CashOnDeliveryDriver;
use App\Payments\Drivers\MoyasarDriver;
use App\Payments\Drivers\TapDriver;

return [
    // No 'currency' key here on purpose: Payments reads config('site.currency'),
    // the same source Billing's DocumentIssuer reads, rather than a second,
    // independent setting that could silently drift from it — see the
    // full-project audit ("currency code divergence"). Changing the
    // storefront currency under Settings → Website is enough; nothing
    // Payments-specific needs to be touched to match.

    /**
     * Driver classes registered into App\Payments\PaymentDriverRegistry on
     * boot. Adding a driver means adding its class here — nothing else in
     * the registry or its callers changes.
     */
    'drivers' => [
        CashOnDeliveryDriver::class,
        MoyasarDriver::class,
        TapDriver::class,
    ],

    /**
     * How many payment_transactions rows a single order may accumulate
     * across every retry before checkout refuses further attempts.
     * Admin-configurable (Settings → Payment methods); this is only the
     * fallback used before a setting is ever saved.
     */
    'max_attempts_per_order' => env('PAYMENTS_MAX_ATTEMPTS_PER_ORDER', 5),

    /**
     * The verify-on-page-load fallback (Order Tracking / the payment result
     * screen) only re-asks the provider once a transaction has sat pending
     * this long, and never more often than the cooldown — so a customer
     * refreshing the result page repeatedly cannot hammer the provider.
     */
    'verify_on_load' => [
        'after_seconds' => 90,
        'cooldown_seconds' => 45,
    ],

    'reconciliation' => [
        // Admin-configurable (Settings → Payment methods), minimum 5
        // minutes enforced by SavePaymentSettingsRequest. This is only the
        // fallback used before a setting is ever saved.
        'stuck_after_minutes' => env('PAYMENTS_RECONCILE_STUCK_AFTER_MINUTES', 30),
        // Deliberately config-file-only, not admin-configurable: a batch
        // size chosen for shared-hosting memory limits is an operational
        // constant, not a business setting.
        'batch_size' => 100,
    ],
];
