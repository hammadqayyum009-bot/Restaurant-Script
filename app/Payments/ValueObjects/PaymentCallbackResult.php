<?php

namespace App\Payments\ValueObjects;

use App\Payments\PaymentStatus;

/**
 * The outcome of a customer's browser landing back on our site after a
 * provider-hosted redirect. This is never treated as authoritative on its
 * own — a callback can be skipped, replayed, or forged by the customer's
 * browser; only verify() or a signed webhook actually moves money-truth.
 */
final class PaymentCallbackResult
{
    public function __construct(
        public readonly PaymentStatus $status,
        public readonly ?string $providerReference,
        public readonly string $message,
    ) {}
}
