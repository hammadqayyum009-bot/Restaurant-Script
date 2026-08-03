<?php

namespace App\Payments\ValueObjects;

use App\Models\PaymentTransaction;

final class PaymentStatusTransitionResult
{
    public function __construct(
        public readonly bool $applied,
        public readonly PaymentTransaction $transaction,
    ) {}
}
