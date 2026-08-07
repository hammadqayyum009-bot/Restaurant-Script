<?php

namespace App\Http\Controllers\Payments;

use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhookEvent;
use App\Payments\PaymentDriverRegistry;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Every delivery is stored before anything else, and every delivery is
 * currently rejected: hashstring verification is not implemented (the
 * algorithm was not confirmed against real documentation — see
 * documentation/payments-known-limitations.md), so this fails closed
 * rather than trusting an unverified payload. A non-2xx response means
 * Tap's own retry policy keeps redelivering until this is closed out;
 * nothing here ever marks a transaction paid.
 *
 * Idempotency is the database unique constraint on (driver,
 * provider_event_id), not an application-level "have I seen this?" check —
 * a race between two simultaneous deliveries is resolved by whichever INSERT
 * wins; the other fails the unique constraint and is recognized as a
 * duplicate, not silently double-processed.
 */
class TapWebhookController
{
    public function handle(Request $request, PaymentDriverRegistry $registry): JsonResponse
    {
        $rawBody = $request->getContent();
        $payload = json_decode($rawBody, true) ?? [];

        $method = PaymentMethod::where('driver', 'tap')->first();
        $eventId = null;
        $errorMessage = 'No Tap payment method is configured.';

        if ($method && $registry->has('tap')) {
            $result = $registry->get('tap')->handleWebhook($method, $request);
            $eventId = $result->providerEventId;
            $errorMessage = $result->errorMessage;
        }

        // No id could be extracted from the payload — fall back to a hash of
        // the raw body so the (driver, provider_event_id) column is always
        // populated and an identical redelivery still collides correctly.
        $eventId ??= hash('sha256', $rawBody);

        try {
            $event = PaymentWebhookEvent::create([
                'driver' => 'tap',
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

            Log::info('Duplicate Tap webhook delivery received.', ['provider_event_id' => $eventId]);
            $event = PaymentWebhookEvent::where('driver', 'tap')->where('provider_event_id', $eventId)->first();
        }

        // Case: webhook arrives before our transaction row is committed, or
        // for a reference that never resolves — correlate now if possible,
        // leave payment_transaction_id null otherwise. Never crashes, never
        // drops the event either way.
        if ($event && $event->payment_transaction_id === null) {
            $providerReference = $payload['data']['id'] ?? $payload['id'] ?? null;

            if ($providerReference) {
                $transaction = PaymentTransaction::where('driver', 'tap')
                    ->where('provider_reference', $providerReference)
                    ->first();

                if ($transaction) {
                    $event->update(['payment_transaction_id' => $transaction->id]);
                }
            }
        }

        return response()->json(['error' => 'hashstring_verification_not_implemented'], 503);
    }
}
