<?php

namespace App\Payments\ValueObjects;

use App\Models\PaymentTransaction;

final class PaymentStatusTransitionResult
{
    /**
     * $providerUnreachable is optional and additive (Phase 4) — true only
     * when PaymentVerificationService::verify() could not reach the
     * provider at all, distinct from every other "not applied" case
     * (already in that state, an illegal transition, or an amount/currency
     * mismatch), which leave it false. PaymentTransactionStatusService
     * itself never sets it true — only a verify() attempt can.
     */
    public function __construct(
        public readonly bool $applied,
        public readonly PaymentTransaction $transaction,
        public readonly bool $providerUnreachable = false,
    ) {}
}
