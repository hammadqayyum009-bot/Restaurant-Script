<?php

namespace App\Payments\ValueObjects;

use App\Payments\PaymentStatus;

final class PaymentWebhookResult
{
    public function __construct(
        public readonly bool $processed,
        public readonly ?PaymentStatus $statusApplied,
        public readonly ?string $providerEventId,
        public readonly ?string $errorMessage = null,
    ) {}
}
