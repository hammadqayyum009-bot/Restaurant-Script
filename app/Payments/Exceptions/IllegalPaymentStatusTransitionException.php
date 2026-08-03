<?php

namespace App\Payments\Exceptions;

use App\Payments\PaymentStatus;
use RuntimeException;

class IllegalPaymentStatusTransitionException extends RuntimeException
{
    public function __construct(public readonly PaymentStatus $from, public readonly PaymentStatus $to)
    {
        parent::__construct("Cannot transition a payment from \"{$from->value}\" to \"{$to->value}\".");
    }
}
