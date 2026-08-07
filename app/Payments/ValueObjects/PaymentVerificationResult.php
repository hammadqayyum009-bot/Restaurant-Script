<?php

namespace App\Payments\ValueObjects;

use App\Payments\PaymentStatus;
use Illuminate\Support\Carbon;

/**
 * The authoritative status, as read directly from the provider (or, for a
 * driver with no provider, from our own stored state) rather than inferred
 * from a callback or webhook.
 *
 * $amountMinor and $currency are optional and additive (Phase 2) — a driver
 * that cannot or does not echo them back (Cash on Delivery has nothing to
 * ask) simply leaves them null. When present, the caller compares them
 * against the stored transaction and must never apply a status update on a
 * mismatch.
 */
final class PaymentVerificationResult
{
    public function __construct(
        public readonly PaymentStatus $status,
        public readonly ?string $providerReference,
        public readonly Carbon $checkedAt,
        public readonly ?int $amountMinor = null,
        public readonly ?string $currency = null,
    ) {}
}
