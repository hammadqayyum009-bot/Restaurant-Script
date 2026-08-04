<?php

namespace App\Http\Controllers\Payments;

use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhookEvent;
use App\Payments\PaymentDriverRegistry;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Every delivery is stored before anything else, and every delivery is
 * currently rejected: signature verification is not implemented (the
 * algorithm was not confirmed against real documentation or a sandbox
 * payload — see documentation/payments-known-limitations.md), so this fails
 * closed rather than trusting an unverified payload. A non-2xx response
 * means Moyasar's own retry policy keeps redelivering until this is closed
 * out; nothing here ever marks a transaction paid.
 *
 * Idempotency is the database unique constraint on (driver,
 * provider_event_id), not an application-level "have I seen this?" check —
 * a race between two simultaneous deliveries is resolved by whichever INSERT
 * wins; the other fails the unique constraint and is recognized as a
 * duplicate, not silently double-processed.
 */
class MoyasarWebhookController
{
    public function handle(Request $request, PaymentDriverRegistry $registry): Response
    {
        $rawBody = $request->getContent();
        $payload = json_decode($rawBody, true) ?? [];

        $method = PaymentMethod::where('driver', 'moyasar')->first();
        $eventId = null;
        $errorMessage = 'No Moyasar payment method is configured.';

        if ($method && $registry->has('moyasar')) {
            $result = $registry->get('moyasar')->handleWebhook($method, $request);
            $eventId = $result->providerEventId;
            $errorMessage = $result->errorMessage;
        }

        // No id could be extracted from the payload — fall back to a hash of
        // the raw body so the (driver, provider_event_id) column is always
        // populated and an identical redelivery still collides correctly.
        $eventId ??= hash('sha256', $rawBody);

        try {
            $event = PaymentWebhookEvent::create([
                'driver' => 'moyasar',
                'provider_event_id' => $eventId,
                'signature_valid' => false,
                'payload' => $rawBody,
                'processed' => false,
                'error_message' => $errorMessage,
            ]);
        } catch (QueryException $e) {
            if (! str_contains(strtolower($e->getMessage()), 'unique')) {
                throw $e;
            }

            Log::info('Duplicate Moyasar webhook delivery received.', ['provider_event_id' => $eventId]);
            $event = PaymentWebhookEvent::where('driver', 'moyasar')->where('provider_event_id', $eventId)->first();
        }

        // Case: webhook arrives before our transaction row is committed, or
        // for a reference that never resolves — correlate now if possible,
        // leave payment_transaction_id null otherwise. Never crashes, never
        // drops the event either way.
        if ($event && $event->payment_transaction_id === null) {
            $providerReference = $payload['id'] ?? ($payload['data']['id'] ?? null);

            if ($providerReference) {
                $transaction = PaymentTransaction::where('driver', 'moyasar')
                    ->where('provider_reference', $providerReference)
                    ->first();

                if ($transaction) {
                    $event->update(['payment_transaction_id' => $transaction->id]);
                }
            }
        }

        return response()->json(['error' => 'signature_verification_not_implemented'], 503);
    }
}
