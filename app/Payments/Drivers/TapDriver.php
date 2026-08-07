<?php

namespace App\Payments\Drivers;

use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Payments\Contracts\PaymentDriver;
use App\Payments\Contracts\SupportsConnectionTest;
use App\Payments\Money;
use App\Payments\PaymentStatus;
use App\Payments\Tap\TapApiException;
use App\Payments\Tap\TapClient;
use App\Payments\ValueObjects\DriverDisplayInfo;
use App\Payments\ValueObjects\PaymentCallbackResult;
use App\Payments\ValueObjects\PaymentInitiationResult;
use App\Payments\ValueObjects\PaymentVerificationResult;
use App\Payments\ValueObjects\PaymentWebhookResult;
use App\Payments\ValueObjects\RefundResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Card data never reaches this class or anything it calls — same guarantee
 * as MoyasarDriver, via Tap's hosted charge/redirect flow.
 *
 * The one real behavioral difference from Moyasar: Tap can complete a charge
 * synchronously (no 3-D Secure step), in which case the charge response has
 * no transaction.url at all. initiate() handles both outcomes using the
 * same PaymentInitiationResult factories Phase 1 already defined —
 * ::redirect() when a URL is present, ::noRedirectRequired() when the
 * charge resolved immediately. This is the same factory
 * CashOnDeliveryDriver uses for an unrelated reason, which is itself
 * evidence the Phase 1 value object was not designed narrowly around
 * Moyasar's shape.
 */
class TapDriver implements PaymentDriver, SupportsConnectionTest
{
    public function identifier(): string
    {
        return 'tap';
    }

    public function displayInfo(): DriverDisplayInfo
    {
        return new DriverDisplayInfo(
            __('payments.tap_label'),
            __('payments.tap_description'),
        );
    }

    /** @return string[] */
    public function supportedCurrencies(): array
    {
        return ['SAR', 'AED', 'QAR', 'USD', 'KWD', 'BHD', 'OMR'];
    }

    public function isConfigured(PaymentMethod $method): bool
    {
        return $this->secretKeyFor($method) !== null;
    }

    public function initiate(PaymentTransaction $transaction, PaymentMethod $method): PaymentInitiationResult
    {
        $client = $this->clientFor($method);

        $callbackUrl = route('payments.tap.callback', ['transaction' => $transaction->id]);
        $order = $transaction->order;

        $charge = $client->createCharge([
            // Money::toDecimal() already returns a formatted string
            // ("5.500" for 3-decimal KWD) — never cast to a float anywhere
            // in this call chain, or Tap silently receives the wrong
            // decimal precision.
            'amount' => Money::toDecimal($transaction->amount_minor, $transaction->currency),
            'currency' => $transaction->currency,
            'customer' => [
                'first_name' => $order->customer_name,
                'phone' => ['number' => $order->phone],
            ],
            'source' => ['id' => $this->sourceIdFor($method)],
            'redirect' => ['url' => $callbackUrl],
            'post' => ['url' => route('payments.tap.webhook')],
        ], $transaction);

        $chargeId = $charge['id'] ?? null;

        if (! $chargeId) {
            throw new TapApiException('Tap charge response did not include an id.');
        }

        $redirectUrl = $charge['transaction']['url'] ?? null;

        return $redirectUrl !== null
            ? PaymentInitiationResult::redirect($redirectUrl, $chargeId)
            : PaymentInitiationResult::noRedirectRequired($chargeId);
    }

    public function handleCallback(PaymentMethod $method, Request $request): PaymentCallbackResult
    {
        $chargeId = (string) $request->query('tap_id', $request->query('id', ''));

        return new PaymentCallbackResult(
            PaymentStatus::Pending,
            $chargeId !== '' ? $chargeId : null,
            'Verification pending.',
        );
    }

    /**
     * Signature verification is not implemented. Tap's own docs refer to a
     * "hashstring" scheme distinct from Moyasar's HMAC-style header, and
     * the exact computation was not found in any reachable source — see
     * documentation/payments-known-limitations.md. Fails closed, same
     * treatment as Moyasar's webhook gap.
     */
    public function handleWebhook(PaymentMethod $method, Request $request): PaymentWebhookResult
    {
        $payload = $request->json()->all();
        $eventId = $payload['id'] ?? ($payload['data']['id'] ?? null);

        return new PaymentWebhookResult(
            processed: false,
            statusApplied: null,
            providerEventId: $eventId !== null ? (string) $eventId : null,
            errorMessage: 'Tap webhook hashstring verification is not yet implemented; rejecting to fail closed.',
        );
    }

    /**
     * provider_reference is the Tap charge id created in initiate(). Amount
     * and currency come back as Tap's own decimal string, converted back to
     * our integer minor units via Money::toMinor() for the mismatch check —
     * the round-trip this phase's tests exist to prove.
     */
    public function verify(PaymentTransaction $transaction, PaymentMethod $method): PaymentVerificationResult
    {
        $client = $this->clientFor($method);

        $charge = $client->fetchCharge($transaction->provider_reference, $transaction);

        $amountMinor = isset($charge['amount'], $charge['currency'])
            ? Money::toMinor((string) $charge['amount'], (string) $charge['currency'])
            : null;

        return new PaymentVerificationResult(
            $this->mapStatus($charge['status'] ?? null),
            $charge['id'] ?? $transaction->provider_reference,
            now(),
            $amountMinor,
            $charge['currency'] ?? null,
        );
    }

    public function supportsRefund(): bool
    {
        return true;
    }

    public function refund(PaymentTransaction $transaction, PaymentMethod $method, int $amountMinor, string $reason): RefundResult
    {
        $client = $this->clientFor($method);

        try {
            $result = $client->refund(
                $transaction->provider_reference,
                Money::toDecimal($amountMinor, $transaction->currency),
                $transaction->currency,
                $reason,
                $transaction,
            );
        } catch (TapApiException $e) {
            return new RefundResult(false, null, $amountMinor, $e->getMessage());
        }

        return new RefundResult(true, $result['id'] ?? $transaction->provider_reference, $amountMinor);
    }

    public function testConnection(PaymentMethod $method): bool
    {
        if (! $this->isConfigured($method)) {
            return false;
        }

        return $this->clientFor($method)->testConnection();
    }

    protected function clientFor(PaymentMethod $method): TapClient
    {
        $key = $this->secretKeyFor($method);

        if ($key === null) {
            throw new TapApiException('This Tap payment method has no secret key configured for its current mode.');
        }

        return new TapClient($key, $this->identifier());
    }

    protected function secretKeyFor(PaymentMethod $method): ?string
    {
        $field = $method->test_mode ? 'secret_key_test' : 'secret_key_live';

        return $method->credentials[$field] ?? null;
    }

    /**
     * The only method with real confidence is the universal src_card.
     * local_source_id is a power-user override for a country-specific
     * method (KNET, Benefit, ...) whose exact source.id strings were not
     * confirmed against primary documentation — see
     * documentation/payments-known-limitations.md. Never guessed into code.
     */
    protected function sourceIdFor(PaymentMethod $method): string
    {
        return $method->credentials['local_source_id'] ?? 'src_card';
    }

    protected function mapStatus(?string $tapStatus): PaymentStatus
    {
        return match ($tapStatus) {
            'CAPTURED' => PaymentStatus::Paid,
            'AUTHORIZED', 'INITIATED' => PaymentStatus::Processing,
            'CANCELLED', 'VOID' => PaymentStatus::Cancelled,
            'FAILED', 'DECLINED', 'RESTRICTED', 'TIMEDOUT', 'ABANDONED' => PaymentStatus::Failed,
            default => $this->unrecognizedStatus($tapStatus),
        };
    }

    protected function unrecognizedStatus(?string $tapStatus): PaymentStatus
    {
        Log::warning('Unrecognized Tap charge status, treating as pending.', ['status' => $tapStatus]);

        return PaymentStatus::Pending;
    }
}
