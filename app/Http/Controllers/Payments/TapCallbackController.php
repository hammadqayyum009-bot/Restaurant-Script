<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Payments\PaymentDriverRegistry;
use App\Payments\PaymentTransactionStatusService;
use App\Payments\PaymentVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * The customer's browser landing here after Tap's hosted page proves
 * nothing by itself — it can be reached by typing the URL. This controller
 * only resolves which transaction to check and hands off to
 * PaymentVerificationService; it never sets a transaction to paid directly.
 */
class TapCallbackController extends Controller
{
    public function handle(Request $request, PaymentDriverRegistry $registry, PaymentVerificationService $verifier)
    {
        $transactionId = $request->query('transaction');
        $transaction = $transactionId ? PaymentTransaction::find($transactionId) : null;

        if (! $transaction) {
            // Case: callback with a charge ID that does not exist.
            return redirect()->route('home')->with('error', 'We could not find that payment. If you were charged, please contact us.');
        }

        $method = $transaction->paymentMethod;

        if ($method && $registry->has($transaction->driver)) {
            // Never trusted for status — only ever used to resolve the
            // reference. The actual state change, if any, comes from
            // verify() below, which asks Tap directly.
            $registry->get($transaction->driver)->handleCallback($method, $request);
        }

        $verifier->verify($transaction, PaymentTransactionStatusService::SOURCE_CALLBACK);

        // The customer's browser landing back here after Tap's hosted page
        // proves nothing by itself (see class docblock) — verify() above is
        // what actually determined the real status; this redirect only
        // sends them to the screen that reports it.
        return redirect()->to(
            URL::temporarySignedRoute('checkout.payment.result', now()->addHours(6), ['order' => $transaction->order_id])
        );
    }
}
