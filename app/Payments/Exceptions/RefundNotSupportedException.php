<?php

namespace App\Payments\Exceptions;

use RuntimeException;

/**
 * Thrown only if refund() is called despite the driver's own
 * supportsRefund() having already returned false — that check is the
 * caller's responsibility. Declaring "unsupported" itself never throws.
 */
class RefundNotSupportedException extends RuntimeException
{
    public function __construct(string $driver)
    {
        parent::__construct("The \"{$driver}\" payment driver does not support refunds.");
    }
}
