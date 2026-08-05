<?php

namespace Tests\Feature\Payments;

use App\Models\EmailLog;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Payments\Drivers\CashOnDeliveryDriver;
use App\Payments\PaymentTransactionStatusService;
use App\Payments\PaymentVerificationService;
use App\Services\OrderNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The "order placed" email now dispatches from two different Payments-module
 * call sites (CashOnDeliveryDriver::initiate() and
 * PaymentVerificationService's Paid closure) instead of CheckoutController.
 * Both are idempotent by construction — they only fire on the one request
 * that actually flips the order from pending to confirmed — and this proves
 * that holds under a retry/re-verify, not just on the first call.
 *
 * dispatchTemplate() uses dispatch(...)->afterResponse(), so every
 * assertion here goes through a real HTTP request rather than calling a
 * service method directly — a direct call never reaches the termination
 * phase that actually sends the mail (see the Phase 2 VerificationMismatchTest
 * fix for the same reasoning).
 */
class OrderNotifierEmailTimingTest extends TestCase
{
    use RefreshDatabase;

    protected function orderWithEmail(): Order
    {
        return Order::create([
            'order_number' => 'ORD-'.strtoupper(Str::random(8)),
            'customer_name' => 'Test Customer', 'phone' => '0500000000', 'email' => 'customer@example.com',
            'address' => 'x', 'order_type' => 'delivery', 'subtotal' => 50, 'delivery_fee' => 0,
            'tax' => 0, 'total' => 50, 'payment_method' => 'cash', 'status' => 'pending',
        ]);
    }

    public function test_cash_on_delivery_sends_exactly_one_order_placed_email(): void
    {
        $order = $this->orderWithEmail();
        $method = PaymentMethod::factory()->create(['driver' => 'cod', 'enabled' => true]);
        $url = URL::temporarySignedRoute('checkout.payment.show', now()->addHours(6), ['order' => $order->id]);

        $this->post($url, ['payment_method_id' => $method->id]);

        $this->assertSame(
            1,
            EmailLog::where('to_email', 'customer@example.com')->where('type', 'order_placed')->count(),
        );
    }

    /**
     * Verified against the notifier call count directly, not EmailLog rows
     * across two HTTP round-trips: Illuminate\Foundation\Application::terminate()
     * never clears $terminatingCallbacks, so a second $this->post() in the
     * same test method would re-run the FIRST request's already-fired
     * dispatch(...)->afterResponse() callback too — a test-harness artifact
     * of reusing one $app across multiple calls, not something that can
     * happen in production (every real request boots a fresh $app). Calling
     * the driver directly, twice, in-process — the same technique
     * PaymentStatusTransitionTest already uses for this exact class of
     * "no duplicate side effects" guarantee — sidesteps that artifact.
     */
    public function test_a_second_cash_on_delivery_attempt_on_an_already_confirmed_order_does_not_resend(): void
    {
        $order = $this->orderWithEmail();
        $method = PaymentMethod::factory()->create(['driver' => 'cod', 'enabled' => true]);
        $transaction = PaymentTransaction::create([
            'order_id' => $order->id, 'payment_method_id' => $method->id, 'driver' => 'cod',
            'amount_minor' => 5000, 'currency' => 'SAR', 'status' => 'pending',
        ]);

        $this->partialMock(OrderNotifier::class, function ($mock) {
            $mock->shouldReceive('notifyPlaced')->once();
        });

        $driver = new CashOnDeliveryDriver;
        $driver->initiate($transaction, $method);
        $driver->initiate($transaction, $method);

        $this->assertSame('confirmed', $order->fresh()->status);
    }

    public function test_a_successful_online_payment_sends_exactly_one_order_placed_email(): void
    {
        $order = $this->orderWithEmail();
        $method = PaymentMethod::factory()->create([
            'driver' => 'moyasar', 'enabled' => true, 'test_mode' => true,
            'credentials' => ['secret_key_test' => 'sk_test_fake'],
        ]);
        $transaction = PaymentTransaction::create([
            'order_id' => $order->id, 'payment_method_id' => $method->id, 'driver' => 'moyasar',
            'amount_minor' => 5000, 'currency' => 'SAR', 'status' => 'pending',
            'provider_reference' => 'inv_email_test',
        ]);

        Http::fake([
            'api.moyasar.com/v1/invoices/inv_email_test' => Http::response(
                ['id' => 'inv_email_test', 'status' => 'paid', 'amount' => 5000, 'currency' => 'SAR'],
                200,
            ),
        ]);

        $this->get('/payments/moyasar/callback?transaction='.$transaction->id.'&id=inv_email_test');

        $this->assertSame(
            1,
            EmailLog::where('to_email', 'customer@example.com')->where('type', 'order_placed')->count(),
        );
    }

    public function test_re_verifying_an_already_paid_transaction_does_not_resend_the_email(): void
    {
        $order = $this->orderWithEmail();
        $method = PaymentMethod::factory()->create([
            'driver' => 'moyasar', 'enabled' => true, 'test_mode' => true,
            'credentials' => ['secret_key_test' => 'sk_test_fake'],
        ]);
        $transaction = PaymentTransaction::create([
            'order_id' => $order->id, 'payment_method_id' => $method->id, 'driver' => 'moyasar',
            'amount_minor' => 5000, 'currency' => 'SAR', 'status' => 'pending',
            'provider_reference' => 'inv_email_test2',
        ]);

        Http::fake([
            'api.moyasar.com/v1/invoices/inv_email_test2' => Http::response(
                ['id' => 'inv_email_test2', 'status' => 'paid', 'amount' => 5000, 'currency' => 'SAR'],
                200,
            ),
        ]);

        $this->partialMock(OrderNotifier::class, function ($mock) {
            $mock->shouldReceive('notifyPlaced')->once();
        });

        $verifier = app(PaymentVerificationService::class);
        // A second, independent verify() call (e.g. the customer's browser
        // retrying, or a webhook landing after the callback already
        // resolved it) must not fire a second email — the transaction is
        // already "paid", so transitionTo() is a no-op and the Paid
        // closure never runs a second time.
        $verifier->verify($transaction, PaymentTransactionStatusService::SOURCE_CALLBACK);
        $verifier->verify($transaction->fresh(), PaymentTransactionStatusService::SOURCE_CALLBACK);

        $this->assertSame('paid', $transaction->fresh()->status);
    }
}
