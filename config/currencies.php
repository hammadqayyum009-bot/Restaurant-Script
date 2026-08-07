<?php

/**
 * The single, shared source of truth for minor-unit exponents per currency
 * code — never assume 2. KWD, BHD and OMR use 3; JPY uses 0.
 *
 * Owned by neither the Billing nor the Payments module: both read this file
 * (Billing via config/billing.php's 'currencies' key, Payments directly) so
 * the two can never drift apart and round the same order differently.
 */
return [
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
];
