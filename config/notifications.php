<?php

return [
    // Where admin copies of customer activity are delivered.
    'admin_email' => env('NOTIFY_ADMIN_EMAIL', null),

    'on_register' => true,
    'on_order' => true,
    'on_order_status' => true,
    'on_reservation' => true,

    'copy_admin_on_order' => true,
    'copy_admin_on_reservation' => true,

    'bulk_email' => [
        // Deliberately config-file-only, not admin-configurable — same
        // reasoning as payments.reconciliation.batch_size: an operational
        // constant chosen for shared-hosting timeout limits, not a business
        // setting.
        'batch_size' => 50,
    ],
];
