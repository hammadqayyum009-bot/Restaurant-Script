<?php

namespace App\Http\Controllers\Admin\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\MarkPaymentPaidRequest;
use App\Http\Requests\Payments\RefundPaymentRequest;
use App\Models\PaymentTransaction;
use App\Payments\Money;
use App\Payments\PaymentDriverRegistry;
use App\Payments\PaymentReconciliationService;
use App\Payments\PaymentStatus;
use App\Payments\PaymentTransactionStatusService;
use App\Payments\PaymentVerificationService;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class PaymentTransactionController extends Controller
{
    public function __construct(
        protected PaymentTransactionStatusService $statusService,
        protected PaymentVerificationService $verifier,
        protected PaymentDriverRegistry $registry,
        protected ActivityLogger $activity,
    ) {}

    public function show(PaymentTransaction $paymentTransaction)
    {
        Gate::authorize('view', $paymentTransaction);

        $paymentTransaction->load(['order', 'paymentMethod', 'statusLogs', 'webhookEvents', 'gatewayLogs']);

        return view('admin.payments.transaction', [
            'transaction' => $paymentTransaction,
            'currencyDecimal' => Money::toDecimal($paymentTransaction->amount_minor, $paymentTransaction->currency),
            'refundedDecimal' => Money::toDecimal($paymentTransaction->refunded_amount_minor, $paymentTransaction->currency),
            'remainingDecimal' => Money::toDecimal(
                $paymentTransaction->amount_minor - $paymentTransaction->refunded_amount_minor,
                $paymentTransaction->currency,
            ),
        ]);
    }

    public function markPaid(MarkPaymentPaidRequest $request, PaymentTransaction $paymentTransaction)
    {
        $result = $this->statusService->transitionTo(
            $paymentTransaction,
            PaymentStatus::Paid,
            PaymentTransactionStatusService::SOURCE_MANUAL,
            $request->user(),
            $request->validated('note'),
        );

        if (! $result->applied) {
            return back()->with('error', __('payments.already_paid'));
        }

        return back()->with('success', __('payments.marked_paid_success'));
    }

    public function stuck(PaymentReconciliationService $reconciliation)
    {
        Gate::authorize('manage-payments');

        $transactions = $reconciliation->stuckTransactionsQuery()
            ->with(['order', 'paymentMethod'])
            ->paginate(25);

        return view('admin.payments.stuck', [
            'transactions' => $transactions,
            'stuckAfterMinutes' => (int) config('payments.reconciliation.stuck_after_minutes'),
        ]);
    }

    public function reconcile(PaymentReconciliationService $reconciliation)
    {
        Gate::authorize('manage-payments');

        $result = $reconciliation->run();

        if (! $result['ran']) {
            return back()->with('error', __('payments.reconciliation_already_running'));
        }

        $this->activity->log(
            'status',
            "Ran payment reconciliation: {$result['checked']} checked, {$result['resolved']} resolved.",
        );

        return back()->with('success', __('payments.reconciliation_ran', [
            'checked' => $result['checked'],
            'resolved' => $result['resolved'],
        ]));
    }

    public function reverify(PaymentTransaction $paymentTransaction)
    {
        Gate::authorize('reverify', $paymentTransaction);

        $this->verifier->verify($paymentTransaction, PaymentTransactionStatusService::SOURCE_VERIFICATION, auth()->user());

        $this->activity->log('status', 'Manually re-verified payment transaction #'.$paymentTransaction->id, $paymentTransaction);

        return back()->with('success', __('payments.reverify_success'));
    }

    /**
     * Two phases, deliberately not one locked block: the amount is reserved
     * against refunded_amount_minor atomically first (so two concurrent
     * partial refunds can never together exceed amount_minor — the second
     * request's lockForUpdate() blocks until the first commits, then sees
     * the already-incremented total), and only then is the external API
     * called, outside any database lock — holding a row lock across a
     * network call would block every other reader of this transaction
     * (including a webhook or the page-load verify check) for as long as
     * Moyasar takes to respond. If the API call fails or is unreachable,
     * the reservation is released in its own atomic step, so a failed
     * refund never permanently consumes capacity that was never actually
     * refunded.
     */
    public function refund(RefundPaymentRequest $request, PaymentTransaction $paymentTransaction)
    {
        Gate::authorize('refund', $paymentTransaction);

        $currency = $paymentTransaction->currency;
        $amountMinor = Money::toMinor((string) $request->validated('amount'), $currency);
        $reason = $request->validated('reason');

        if (! $this->reserveRefundAmount($paymentTransaction, $amountMinor)) {
            return back()
                ->with('error', 'Refund amount exceeds what remains on this transaction.')
                ->withInput();
        }

        $driver = $this->registry->get($paymentTransaction->driver);
        $method = $paymentTransaction->paymentMethod;

        try {
            $result = $driver->refund($paymentTransaction, $method, $amountMinor, $reason);
        } catch (Throwable $e) {
            $this->releaseRefundAmount($paymentTransaction, $amountMinor);

            return back()->with('error', __('payments.refund_failed').': '.$e->getMessage())->withInput();
        }

        if (! $result->success) {
            $this->releaseRefundAmount($paymentTransaction, $amountMinor);

            return back()->with('error', __('payments.refund_failed').': '.($result->failureReason ?? 'unknown error'))->withInput();
        }

        $paymentTransaction->forceFill([
            'refund_reason' => $reason,
            'refunded_at' => now(),
        ])->save();

        $fresh = $paymentTransaction->fresh();
        $targetStatus = $fresh->refunded_amount_minor >= $fresh->amount_minor
            ? PaymentStatus::Refunded
            : PaymentStatus::PartiallyRefunded;

        $this->statusService->transitionTo(
            $fresh,
            $targetStatus,
            PaymentTransactionStatusService::SOURCE_MANUAL,
            $request->user(),
            "Refunded {$request->validated('amount')} {$currency}: {$reason}",
        );

        $this->activity->log('updated', 'Refunded payment transaction #'.$paymentTransaction->id, $paymentTransaction);

        return back()->with('success', __('payments.refund_success'));
    }

    protected function reserveRefundAmount(PaymentTransaction $transaction, int $amountMinor): bool
    {
        return DB::transaction(function () use ($transaction, $amountMinor) {
            $locked = PaymentTransaction::whereKey($transaction->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->refunded_amount_minor + $amountMinor > $locked->amount_minor) {
                return false;
            }

            $locked->increment('refunded_amount_minor', $amountMinor);

            return true;
        });
    }

    protected function releaseRefundAmount(PaymentTransaction $transaction, int $amountMinor): void
    {
        DB::transaction(function () use ($transaction, $amountMinor) {
            PaymentTransaction::whereKey($transaction->getKey())->lockForUpdate()->decrement('refunded_amount_minor', $amountMinor);
        });
    }
}
