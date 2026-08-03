<?php

namespace App\Payments\Drivers;

use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Payments\Contracts\PaymentDriver;
use App\Payments\Exceptions\RefundNotSupportedException;
use App\Payments\PaymentStatus;
use App\Payments\ValueObjects\DriverDisplayInfo;
use App\Payments\ValueObjects\PaymentCallbackResult;
use App\Payments\ValueObjects\PaymentInitiationResult;
use App\Payments\ValueObjects\PaymentVerificationResult;
use App\Payments\ValueObjects\PaymentWebhookResult;
use App\Payments\ValueObjects\RefundResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * The only driver in Phase 1. No external API, no redirect, no webhook.
 * Staff mark a transaction paid by hand from the admin panel once the rider
 * returns with cash — that action goes through
 * PaymentTransactionStatusService::transitionTo(), not through this class.
 */
class CashOnDeliveryDriver implements PaymentDriver
{
    public function identifier(): string
    {
        return 'cod';
    }

    public function displayInfo(): DriverDisplayInfo
    {
        return new DriverDisplayInfo(
            __('payments.cod_label'),
            __('payments.cod_description'),
        );
    }

    /** @return string[] */
    public function supportedCurrencies(): array
    {
        return array_keys(config('currencies'));
    }

    public function isConfigured(PaymentMethod $method): bool
    {
        // No credentials of any kind are required to accept cash.
        return true;
    }

    /**
     * The transaction stays "pending" — collecting the cash is a manual,
     * later step. What COD does immediately is confirm the order, since
     * there is nothing to wait on before the kitchen can proceed.
     */
    public function initiate(PaymentTransaction $transaction, PaymentMethod $method): PaymentInitiationResult
    {
        DB::transaction(function () use ($transaction) {
            $order = $transaction->order()->lockForUpdate()->firstOrFail();

            if ($order->status === 'pending') {
                $order->update(['status' => 'confirmed']);
            }
        });

        return PaymentInitiationResult::noRedirectRequired();
    }

    public function handleCallback(PaymentMethod $method, Request $request): PaymentCallbackResult
    {
        throw new LogicException('Cash on Delivery has no online payment step; there is no callback to handle.');
    }

    public function handleWebhook(PaymentMethod $method, Request $request): PaymentWebhookResult
    {
        throw new LogicException('Cash on Delivery does not support webhooks.');
    }

    /**
     * There is no provider to ask — the stored status is already the
     * authoritative one, since only staff can ever change it.
     */
    public function verify(PaymentTransaction $transaction, PaymentMethod $method): PaymentVerificationResult
    {
        return new PaymentVerificationResult(
            PaymentStatus::from($transaction->status),
            $transaction->provider_reference,
            now(),
        );
    }

    public function supportsRefund(): bool
    {
        return false;
    }

    public function refund(PaymentTransaction $transaction, PaymentMethod $method, int $amountMinor, string $reason): RefundResult
    {
        throw new RefundNotSupportedException($this->identifier());
    }
}
