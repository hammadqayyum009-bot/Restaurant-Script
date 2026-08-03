<?php

namespace App\Payments\ValueObjects;

final class RefundResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $providerReference,
        public readonly int $amountMinor,
        public readonly ?string $failureReason = null,
    ) {}
}
