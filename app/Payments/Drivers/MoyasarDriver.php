<?php

namespace App\Payments\Drivers;

use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Payments\Contracts\PaymentDriver;
use App\Payments\Contracts\SupportsConnectionTest;
use App\Payments\Moyasar\MoyasarApiException;
use App\Payments\Moyasar\MoyasarClient;
use App\Payments\PaymentStatus;
use App\Payments\ValueObjects\DriverDisplayInfo;
use App\Payments\ValueObjects\PaymentCallbackResult;
use App\Payments\ValueObjects\PaymentInitiationResult;
use App\Payments\ValueObjects\PaymentVerificationResult;
use App\Payments\ValueObjects\PaymentWebhookResult;
use App\Payments\ValueObjects\RefundResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Card data never reaches this class or anything it calls: initiate() only
 * ever sends amount/currency/description/urls to Moyasar's Invoices API and
 * gets back a hosted checkout URL. There is no code path here that could
 * ever see a card field.
 *
 * verify() is the only method that can result in a "paid" status change,
 * and even then only indirectly — it returns what Moyasar says, and it is
 * PaymentVerificationService/PaymentTransactionStatusService that decide
 * whether to apply it. handleCallback() and handleWebhook() never return a
 * trusted "paid" status themselves.
 */
class MoyasarDriver implements PaymentDriver, SupportsConnectionTest
{
    public function identifier(): string
    {
        return 'moyasar';
    }

    public function displayInfo(): DriverDisplayInfo
    {
        return new DriverDisplayInfo(
            __('payments.moyasar_label'),
            __('payments.moyasar_description'),
        );
    }

    /** @return string[] */
    public function supportedCurrencies(): array
    {
        return ['SAR', 'USD', 'AED', 'EUR', 'KWD', 'BHD', 'OMR'];
    }

    public function isConfigured(PaymentMethod $method): bool
    {
        return $this->secretKeyFor($method) !== null;
    }

    /**
     * Creates a Moyasar Invoice for the transaction's already-recorded
     * amount/currency (never anything from the current request) and returns
     * its hosted checkout URL. Throws MoyasarApiException if Moyasar is
     * unreachable or returns an error — the transaction row (already created
     * as "pending" by the caller before this method runs) is left untouched
     * either way, so a failed initiate() never leaves partial state.
     */
    public function initiate(PaymentTransaction $transaction, PaymentMethod $method): PaymentInitiationResult
    {
        $client = $this->clientFor($method);

        $callbackUrl = route('payments.moyasar.callback', ['transaction' => $transaction->id]);

        $invoice = $client->createInvoice([
            'amount' => $transaction->amount_minor,
            'currency' => $transaction->currency,
            'description' => 'Order payment #'.$transaction->order_id,
            'success_url' => $callbackUrl,
            'back_url' => $callbackUrl,
            'callback_url' => route('payments.moyasar.webhook'),
            'metadata' => [
                'payment_transaction_id' => (string) $transaction->id,
                'order_id' => (string) $transaction->order_id,
            ],
        ], $transaction);

        $invoiceId = $invoice['id'] ?? null;
        $url = $invoice['url'] ?? null;

        if (! $invoiceId || ! $url) {
            throw new MoyasarApiException('Moyasar invoice response did not include both an id and a url.');
        }

        return PaymentInitiationResult::redirect($url, $invoiceId);
    }

    /**
     * The callback only identifies which transaction to re-check — it never
     * asserts a trusted status. The caller (MoyasarCallbackController) is
     * responsible for calling verify() regardless of what is returned here.
     */
    public function handleCallback(PaymentMethod $method, Request $request): PaymentCallbackResult
    {
        $invoiceId = (string) $request->query('id', '');

        return new PaymentCallbackResult(
            PaymentStatus::Pending,
            $invoiceId !== '' ? $invoiceId : null,
            'Verification pending.',
        );
    }

    /**
     * Signature verification is not implemented. The header name
     * (x-moyasar-signature) is confirmed; the exact hashing algorithm is
     * not, and was not guessed at — see
     * documentation/payments-known-limitations.md. Every webhook is
     * rejected (processed: false) until this is closed out against a real
     * sandbox payload or confirmation from Moyasar. This fails closed: no
     * webhook is ever treated as verified while this stands.
     */
    public function handleWebhook(PaymentMethod $method, Request $request): PaymentWebhookResult
    {
        $payload = $request->json()->all();
        $eventId = $payload['id'] ?? ($payload['data']['id'] ?? null);

        return new PaymentWebhookResult(
            processed: false,
            statusApplied: null,
            providerEventId: $eventId !== null ? (string) $eventId : null,
            errorMessage: 'Moyasar webhook signature verification is not yet implemented; rejecting to fail closed.',
        );
    }

    /**
     * The authoritative check. provider_reference is the invoice id created
     * in initiate(); this assumes GET /v1/invoices/{id} directly returns a
     * usable status without a further payment-id lookup — unconfirmed
     * against real docs/sandbox, see documentation/payments-known-limitations.md.
     */
    public function verify(PaymentTransaction $transaction, PaymentMethod $method): PaymentVerificationResult
    {
        $client = $this->clientFor($method);

        $invoice = $client->fetchInvoice($transaction->provider_reference, $transaction);

        return new PaymentVerificationResult(
            $this->mapStatus($invoice['status'] ?? null),
            $invoice['id'] ?? $transaction->provider_reference,
            now(),
            isset($invoice['amount']) ? (int) $invoice['amount'] : null,
            $invoice['currency'] ?? null,
        );
    }

    public function supportsRefund(): bool
    {
        return true;
    }

    /**
     * Operates on provider_reference directly, since the API's refund
     * endpoint is documented as POST /v1/payments/{id}/refund and the same
     * Q1 uncertainty about invoice-id vs payment-id applies here too.
     */
    public function refund(PaymentTransaction $transaction, PaymentMethod $method, int $amountMinor, string $reason): RefundResult
    {
        $client = $this->clientFor($method);

        try {
            $result = $client->refund($transaction->provider_reference, $amountMinor, $transaction);
        } catch (MoyasarApiException $e) {
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

    protected function clientFor(PaymentMethod $method): MoyasarClient
    {
        $key = $this->secretKeyFor($method);

        if ($key === null) {
            throw new MoyasarApiException('This Moyasar payment method has no secret key configured for its current mode.');
        }

        return new MoyasarClient($key, $this->identifier());
    }

    protected function secretKeyFor(PaymentMethod $method): ?string
    {
        $field = $method->test_mode ? 'secret_key_test' : 'secret_key_live';

        return $method->credentials[$field] ?? null;
    }

    protected function mapStatus(?string $moyasarStatus): PaymentStatus
    {
        return match ($moyasarStatus) {
            'paid' => PaymentStatus::Paid,
            'failed', 'voided', 'expired' => PaymentStatus::Failed,
            'refunded' => PaymentStatus::Refunded,
            'initiated', 'authorized' => PaymentStatus::Processing,
            default => $this->unrecognizedStatus($moyasarStatus),
        };
    }

    protected function unrecognizedStatus(?string $moyasarStatus): PaymentStatus
    {
        Log::warning('Unrecognized Moyasar invoice status, treating as pending.', ['status' => $moyasarStatus]);

        return PaymentStatus::Pending;
    }
}
