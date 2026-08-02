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
];
