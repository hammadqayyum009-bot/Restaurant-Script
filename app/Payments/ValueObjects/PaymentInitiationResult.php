<?php

namespace App\Payments\ValueObjects;

/**
 * What a driver's initiate() call produced: either the customer must be sent
 * to a provider-hosted redirect URL, or nothing further is needed right now
 * (Cash on Delivery, or a provider that confirmed synchronously).
 */
final class PaymentInitiationResult
{
    private function __construct(
        public readonly bool $requiresRedirect,
        public readonly ?string $redirectUrl,
        public readonly ?string $providerReference,
    ) {}

    public static function redirect(string $url, ?string $providerReference = null): self
    {
        return new self(true, $url, $providerReference);
    }

    public static function noRedirectRequired(?string $providerReference = null): self
    {
        return new self(false, null, $providerReference);
    }
}
