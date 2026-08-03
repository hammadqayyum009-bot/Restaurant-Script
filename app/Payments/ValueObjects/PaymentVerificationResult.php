<?php

namespace App\Payments\ValueObjects;

use App\Payments\PaymentStatus;
use Illuminate\Support\Carbon;

/**
 * The authoritative status, as read directly from the provider (or, for a
 * driver with no provider, from our own stored state) rather than inferred
 * from a callback or webhook.
 */
final class PaymentVerificationResult
{
    public function __construct(
        public readonly PaymentStatus $status,
        public readonly ?string $providerReference,
        public readonly Carbon $checkedAt,
    ) {}
}
