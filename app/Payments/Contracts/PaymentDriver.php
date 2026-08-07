<?php

namespace App\Payments\Contracts;

use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Payments\ValueObjects\DriverDisplayInfo;
use App\Payments\ValueObjects\PaymentCallbackResult;
use App\Payments\ValueObjects\PaymentInitiationResult;
use App\Payments\ValueObjects\PaymentVerificationResult;
use App\Payments\ValueObjects\PaymentWebhookResult;
use App\Payments\ValueObjects\RefundResult;
use Illuminate\Http\Request;

/**
 * Every payment method — Cash on Delivery today, a real gateway later —
 * implements this and only this. The registry resolves drivers by
 * identifier(); nothing else in the app is allowed to know a driver's
 * concrete class. Every method returns an explicit value object, never a
 * raw array or provider payload, so a caller never depends on a specific
 * provider's response shape.
 */
interface PaymentDriver
{
    /**
     * Stable machine identifier, e.g. "cod", "moyasar". Stored on
     * payment_methods.driver and payment_transactions.driver — never
     * changes once anything has been persisted against it.
     */
    public function identifier(): string;

    public function displayInfo(): DriverDisplayInfo;

    /** @return string[] ISO 4217 currency codes this driver can settle in */
    public function supportedCurrencies(): array;

    /**
     * Whether this specific configured instance (its credentials, if any)
     * is usable right now. A disabled or unconfigured method must never be
     * returned as available, regardless of what this method says.
     */
    public function isConfigured(PaymentMethod $method): bool;

    public function initiate(PaymentTransaction $transaction, PaymentMethod $method): PaymentInitiationResult;

    public function handleCallback(PaymentMethod $method, Request $request): PaymentCallbackResult;

    public function handleWebhook(PaymentMethod $method, Request $request): PaymentWebhookResult;

    /** The authoritative status, read fresh rather than inferred. */
    public function verify(PaymentTransaction $transaction, PaymentMethod $method): PaymentVerificationResult;

    /**
     * Declaring refunds unsupported must never throw — only an actual call
     * to refund() on a driver that returns false here may throw.
     */
    public function supportsRefund(): bool;

    public function refund(PaymentTransaction $transaction, PaymentMethod $method, int $amountMinor, string $reason): RefundResult;
}
