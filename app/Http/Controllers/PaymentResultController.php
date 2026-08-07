<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Payments\PaymentTransactionStatusService;
use App\Payments\PaymentVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * The one screen every payment attempt ends up on: success, declined,
 * cancelled, still pending, or the provider being unreachable. For an
 * online gateway still sitting "pending" past a short grace period, this is
 * also where the verify-on-page-load fallback lives — the transaction may
 * already be paid at the provider and we just have not heard back yet
 * (webhook signature verification is not implemented; see
 * documentation/payments-known-limitations.md), so a customer reloading
 * this page is what actually resolves it in the common case.
 */
class PaymentResultController extends Controller
{
    public function show(Request $request, Order $order, PaymentVerificationService $verifier)
    {
        $transaction = $order->paymentTransactions()->latest()->first();

        if (! $transaction) {
            return view('checkout.result', [
                'order' => $order,
                'transaction' => null,
                'outcome' => 'pending',
                'retryUrl' => null,
            ]);
        }

        $outcome = $this->classify($transaction);

        if ($outcome === 'pending' && $this->shouldVerifyOnLoad($transaction)) {
            $result = $verifier->verify($transaction, PaymentTransactionStatusService::SOURCE_VERIFICATION);
            $transaction = $result->transaction;
            $outcome = $result->providerUnreachable ? 'unreachable' : $this->classify($transaction);
        }

        $canRetry = in_array($outcome, ['declined', 'cancelled'], true) && $this->attemptsRemaining($order) > 0;

        return view('checkout.result', [
            'order' => $order,
            'transaction' => $transaction,
            'outcome' => $outcome,
            'retryUrl' => $canRetry
                ? URL::temporarySignedRoute('checkout.payment.show', now()->addHours(6), ['order' => $order->id])
                : null,
        ]);
    }

    protected function classify(PaymentTransaction $transaction): string
    {
        // Cash on Delivery never reaches "paid" through this screen (staff
        // mark it paid by hand once the cash is collected) — the order
        // having been confirmed by CashOnDeliveryDriver::initiate() is
        // itself the success condition for the customer's purposes.
        if ($transaction->driver === 'cod') {
            return 'success';
        }

        return match ($transaction->status) {
            'paid', 'refunded', 'partially_refunded' => 'success',
            'failed' => 'declined',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }

    protected function shouldVerifyOnLoad(PaymentTransaction $transaction): bool
    {
        if (! in_array($transaction->status, ['pending', 'processing'], true)) {
            return false;
        }

        if ($transaction->driver === 'cod') {
            return false;
        }

        // created_at/last_verified_at are always in the past here, so the
        // elapsed time is $timestamp->diffInSeconds(now()) — not the
        // reverse. now()->diffInSeconds($pastTimestamp) returns a negative
        // number (Carbon's diff is signed, not absolute, when the receiver
        // is chronologically after the argument), which made every "has
        // enough time passed" check below true immediately.
        if ($transaction->created_at->diffInSeconds(now()) < (int) config('payments.verify_on_load.after_seconds')) {
            return false;
        }

        if ($transaction->last_verified_at
            && $transaction->last_verified_at->diffInSeconds(now()) < (int) config('payments.verify_on_load.cooldown_seconds')) {
            return false;
        }

        return true;
    }

    protected function attemptsRemaining(Order $order): int
    {
        $max = (int) config('payments.max_attempts_per_order');

        return max(0, $max - $order->paymentTransactions()->count());
    }
}
