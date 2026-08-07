<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Payments\Money;
use App\Payments\PaymentDriverRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Throwable;

/**
 * Step two of checkout: pick a payment method for an already-created order,
 * and initiate it. Reached only via the signed URL CheckoutController::store()
 * hands out — the order id in the route is otherwise a walkable
 * auto-increment integer, so every action here is behind the 'signed'
 * middleware, same guarantee as the reservation confirmation screen.
 */
class CheckoutPaymentController extends Controller
{
    public function __construct(protected PaymentDriverRegistry $registry) {}

    public function show(Request $request, Order $order)
    {
        $latest = $order->paymentTransactions()->latest()->first();

        if ($latest && in_array($latest->status, ['paid', 'refunded', 'partially_refunded'], true)) {
            return redirect()->to($this->signedResultUrl($order));
        }

        $methods = $this->registry->availableFor($order);
        $attemptsRemaining = $this->attemptsRemaining($order);

        if ($methods->isEmpty() && $attemptsRemaining > 0) {
            // No method is eligible at all (every gateway disabled or the
            // order amount falls outside every method's configured
            // limits) — nothing to select, so send the customer to
            // tracking rather than an empty form.
            return redirect()->route('track.show')
                ->with('error', 'No payment method is currently available for this order. Please contact us.');
        }

        return view('checkout.payment-method', [
            'order' => $order,
            'methods' => $methods,
            'registry' => $this->registry,
            'attemptsRemaining' => $attemptsRemaining,
        ]);
    }

    public function store(Request $request, Order $order)
    {
        $data = $request->validate([
            'payment_method_id' => ['required', 'integer'],
        ]);

        $eligible = $this->registry->availableFor($order);
        $method = $eligible->firstWhere('id', (int) $data['payment_method_id']);

        if (! $method) {
            return back()->with('error', __('payments.method_unavailable'));
        }

        if ($this->attemptsRemaining($order) <= 0) {
            return back()->with('error', __('payments.retry_cap_reached'));
        }

        // Guards against a double-click or two browser tabs both submitting
        // this form for the same order at once — not a substitute for the
        // row-level locking PaymentTransactionStatusService already does
        // once a transaction exists, but there is no transaction row yet
        // for this attempt to lock.
        $lock = Cache::lock('checkout-payment-init:'.$order->id, 15);

        if (! $lock->get()) {
            return back()->with('error', __('payments.please_wait'));
        }

        try {
            $transaction = PaymentTransaction::create([
                'order_id' => $order->id,
                'payment_method_id' => $method->id,
                'driver' => $method->driver,
                // config('site.currency') — see PaymentDriverRegistry::availableFor()'s
                // comment; this must stay the same source that computed
                // $eligible above, or a transaction could be created in a
                // currency different from the one it was just filtered by.
                'amount_minor' => Money::toMinor((string) $order->total, config('site.currency')),
                'currency' => config('site.currency'),
                'status' => 'pending',
            ]);

            $order->update(['payment_method' => $method->driver]);

            $driver = $this->registry->get($method->driver);

            try {
                $result = $driver->initiate($transaction, $method);
            } catch (Throwable $e) {
                // The transaction row is left exactly as it is (pending) —
                // same contract MoyasarDriver/TapDriver already document for
                // an initiate() failure. It either gets retried by the
                // customer (a fresh attempt, this one left stranded for
                // reconciliation to eventually close out) or reconciliation
                // marks it failed once it has sat long enough.
                Log::warning('Payment initiation failed.', [
                    'transaction_id' => $transaction->id,
                    'driver' => $method->driver,
                    'message' => $e->getMessage(),
                ]);

                return back()->with('error', __('payments.initiation_failed'));
            }
        } finally {
            $lock->release();
        }

        if ($result->requiresRedirect) {
            // Rendered directly rather than redirected to, because the
            // provider's hosted-page URL is not persisted anywhere — it is
            // only ever available right here, in this response.
            return view('checkout.redirecting', [
                'order' => $order,
                'redirectUrl' => $result->redirectUrl,
            ]);
        }

        return redirect()->to($this->signedResultUrl($order));
    }

    protected function signedResultUrl(Order $order): string
    {
        return URL::temporarySignedRoute('checkout.payment.result', now()->addHours(6), ['order' => $order->id]);
    }

    protected function attemptsRemaining(Order $order): int
    {
        $max = (int) config('payments.max_attempts_per_order');

        return max(0, $max - $order->paymentTransactions()->count());
    }
}
