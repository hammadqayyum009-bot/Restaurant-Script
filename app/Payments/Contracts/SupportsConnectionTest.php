<?php

namespace App\Payments\Contracts;

use App\Models\PaymentMethod;

/**
 * Optional, additive to PaymentDriver — not part of the frozen interface.
 * A driver implements this only if it has something to test; the admin
 * "Test connection" button is only shown when the resolved driver is an
 * instance of this. Cash on Delivery does not implement it.
 */
interface SupportsConnectionTest
{
    /** A harmless, read-only authenticated call — never creates real data. */
    public function testConnection(PaymentMethod $method): bool;
}
