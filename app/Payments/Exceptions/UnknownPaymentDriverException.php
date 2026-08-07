<?php

namespace App\Payments\Exceptions;

use RuntimeException;

class UnknownPaymentDriverException extends RuntimeException
{
    public function __construct(string $identifier)
    {
        parent::__construct("No payment driver is registered with identifier \"{$identifier}\".");
    }
}
