<?php

namespace App\Payments;

use App\Models\PaymentTransaction;
use App\Models\User;
use App\Payments\ValueObjects\PaymentStatusTransitionResult;
use App\Services\ActivityLogger;
use App\Services\Mailer;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The single place any code path (callback, webhook, page-load trigger,
 * admin re-verify) asks "what is this transaction's real status" and acts on
 * it. Every caller goes through here rather than calling
 * PaymentDriver::verify() directly, so the amount/currency mismatch check
 * and the provider-unreachable handling exist in exactly one place.
 */
class PaymentVerificationService
{
    public function __construct(
        protected PaymentDriverRegistry $registry,
        protected PaymentTransactionStatusService $statusService,
        protected ActivityLogger $activity,
        protected Mailer $mailer,
    ) {}

    public function verify(PaymentTransaction $transaction, string $source, ?User $actor = null): PaymentStatusTransitionResult
    {
        $method = $transaction->paymentMethod;
        $driver = $this->registry->get($transaction->driver);

        try {
            $result = $driver->verify($transaction, $method);
        } catch (Throwable $e) {
            // Case: provider unreachable when verifying. The transaction is
            // left exactly as it was — never paid on an unreachable
            // response — and this is recorded as a checked-but-inconclusive
            // attempt so the page-load cooldown still applies.
            Log::warning('Payment verification could not reach the provider.', [
                'transaction_id' => $transaction->id,
                'driver' => $transaction->driver,
                'message' => $e->getMessage(),
            ]);

            $transaction->forceFill(['last_verified_at' => now()])->save();

            return new PaymentStatusTransitionResult(false, $transaction->fresh());
        }

        $transaction->forceFill([
            'last_provider_status' => $result->status->value,
            'last_verified_at' => now(),
        ])->save();

        if ($result->amountMinor !== null && $result->amountMinor !== $transaction->amount_minor) {
            $this->flagMismatch($transaction, 'amount', (string) $result->amountMinor, (string) $transaction->amount_minor);

            return new PaymentStatusTransitionResult(false, $transaction->fresh());
        }

        if ($result->currency !== null && strtoupper($result->currency) !== strtoupper($transaction->currency)) {
            $this->flagMismatch($transaction, 'currency', $result->currency, $transaction->currency);

            return new PaymentStatusTransitionResult(false, $transaction->fresh());
        }

        return $this->statusService->transitionTo(
            $transaction,
            $result->status,
            $source,
            $actor,
            withinTransaction: $result->status === PaymentStatus::Paid ? function (PaymentTransaction $t) {
                $order = $t->order()->lockForUpdate()->first();

                if ($order && $order->status === 'pending') {
                    $order->update(['status' => 'confirmed']);
                }
            } : null,
        );
    }

    /**
     * Case 9/10: never mark paid on a mismatch. Records a distinct activity
     * log entry, flags the transaction so the admin screen surfaces it
     * prominently, and emails the configured admin address — the same
     * escalation the app already uses for other admin-notification events,
     * not a new channel.
     */
    protected function flagMismatch(PaymentTransaction $transaction, string $field, string $providerValue, string $ourValue): void
    {
        $message = "Payment {$field} mismatch: provider reported \"{$providerValue}\", expected \"{$ourValue}\".";

        $transaction->forceFill(['failure_reason' => $message])->save();

        $this->activity->log('payment_amount_mismatch', $message, $transaction);

        if (config('notifications.admin_email')) {
            $this->mailer->dispatchTemplate('payment_security_alert', config('notifications.admin_email'), null, [
                'transaction_id' => (string) $transaction->id,
                'order_id' => (string) $transaction->order_id,
                'detail' => $message,
            ]);
        }
    }
}
