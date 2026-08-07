<?php

namespace App\Payments;

use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Closes out payment_transactions rows that never got a final answer —
 * usually because the webhook signature gap (see
 * documentation/payments-known-limitations.md) left nothing to trigger a
 * status change, and the customer never came back to trigger the
 * verify-on-page-load fallback either. Runnable three ways with identical
 * behaviour: the Artisan command, the scheduler, and an admin "Check now"
 * button — all three call run(), so there is exactly one implementation to
 * reason about.
 */
class PaymentReconciliationService
{
    public function __construct(protected PaymentVerificationService $verifier) {}

    /**
     * A single named Cache::lock() around the whole batch — deliberately
     * separate from the per-transaction row lock PaymentTransactionStatusService
     * already takes inside verify(). That row lock stops two requests
     * racing on the *same* transaction; it does nothing to stop two
     * reconciliation runs (a cron tick and an admin button click landing at
     * the same moment) from both reading the same "stuck" batch and
     * re-verifying every transaction in it twice. This lock is what stops
     * that: only one run() executes at a time, anywhere.
     *
     * @return array{ran: bool, checked: int, resolved: int}
     */
    public function run(): array
    {
        $lock = Cache::lock('payments-reconciliation-run', 300);

        if (! $lock->get()) {
            return ['ran' => false, 'checked' => 0, 'resolved' => 0];
        }

        try {
            $checked = 0;
            $resolved = 0;

            $this->stuckTransactionsQuery()
                ->limit((int) config('payments.reconciliation.batch_size'))
                ->get()
                ->each(function (PaymentTransaction $transaction) use (&$checked, &$resolved) {
                    $checked++;

                    $result = $this->verifier->verify($transaction, PaymentTransactionStatusService::SOURCE_VERIFICATION);

                    if ($result->applied) {
                        $resolved++;
                    }
                });

            return ['ran' => true, 'checked' => $checked, 'resolved' => $resolved];
        } finally {
            $lock->release();
        }
    }

    /**
     * Cash on Delivery is excluded — it has no provider to ask, and a COD
     * transaction is expected to sit "pending" until staff mark it paid by
     * hand (see CashOnDeliveryDriver::verify()'s docblock).
     */
    public function stuckTransactionsQuery(): Builder
    {
        $threshold = now()->subMinutes((int) config('payments.reconciliation.stuck_after_minutes'));

        return PaymentTransaction::whereIn('status', ['pending', 'processing'])
            ->where('driver', '!=', 'cod')
            ->where('created_at', '<=', $threshold)
            ->oldest();
    }

    public function stuckCount(): int
    {
        return $this->stuckTransactionsQuery()->count();
    }
}
