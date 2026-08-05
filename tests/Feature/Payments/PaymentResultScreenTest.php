<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The result screen classifies a transaction into one of five outcomes and,
 * for a still-pending online-gateway transaction, is also where the
 * verify-on-page-load fallback lives (see
 * documentation/payments-known-limitations.md items 1/1b — the webhook gap
 * this exists to work around).
 */
class PaymentResultScreenTest extends TestCase
{
    use RefreshDatabase;

    protected function makeOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'ORD-'.strtoupper(Str::random(8)),
            'customer_name' => 'Test Customer', 'phone' => '0500000000', 'address' => 'x',
            'order_type' => 'delivery', 'subtotal' => 50, 'delivery_fee' => 0, 'tax' => 0,
            'total' => 50, 'payment_method' => 'moyasar', 'status' => 'pending',
        ], $overrides));
    }

    protected function transactionFor(Order $order, array $overrides = []): PaymentTransaction
    {
        $method = PaymentMethod::factory()->create([
            'driver' => 'moyasar', 'enabled' => true, 'test_mode' => true,
            'credentials' => ['secret_key_test' => 'sk_test_fake'],
        ]);

        // created_at/last_verified_at are not mass-assignable — set them
        // via forceFill after create() so backdating a transaction for the
        // grace-period/cooldown tests below actually takes effect.
        $forced = array_intersect_key($overrides, array_flip(['created_at', 'last_verified_at']));
        $fillable = array_diff_key($overrides, $forced);

        $transaction = PaymentTransaction::create(array_merge([
            'order_id' => $order->id, 'payment_method_id' => $method->id, 'driver' => 'moyasar',
            'amount_minor' => 5000, 'currency' => 'SAR', 'status' => 'pending',
            'provider_reference' => 'inv_result_test',
        ], $fillable));

        if ($forced) {
            $transaction->forceFill($forced)->save();
        }

        return $transaction->fresh();
    }

    protected function resultUrl(Order $order): string
    {
        return URL::temporarySignedRoute('checkout.payment.result', now()->addHours(6), ['order' => $order->id]);
    }

    public function test_a_paid_transaction_shows_success(): void
    {
        $order = $this->makeOrder();
        $this->transactionFor($order, ['status' => 'paid']);

        $this->get($this->resultUrl($order))->assertOk()->assertSee('Payment successful');
    }

    public function test_a_cash_on_delivery_transaction_always_shows_success(): void
    {
        $order = $this->makeOrder(['payment_method' => 'cod', 'status' => 'confirmed']);
        $codMethod = PaymentMethod::factory()->create(['driver' => 'cod', 'enabled' => true]);
        PaymentTransaction::create([
            'order_id' => $order->id, 'payment_method_id' => $codMethod->id, 'driver' => 'cod',
            'amount_minor' => 5000, 'currency' => 'SAR', 'status' => 'pending',
        ]);

        $this->get($this->resultUrl($order))->assertOk()->assertSee('Payment successful');
    }

    public function test_a_failed_transaction_shows_declined_with_a_retry_link(): void
    {
        $order = $this->makeOrder();
        $this->transactionFor($order, ['status' => 'failed']);

        $response = $this->get($this->resultUrl($order));

        $response->assertOk()->assertSee('Payment declined');
        $response->assertSee(route('checkout.payment.show', ['order' => $order->id]), false);
    }

    public function test_a_failed_transaction_shows_no_retry_link_once_the_cap_is_reached(): void
    {
        config(['payments.max_attempts_per_order' => 1]);
        $order = $this->makeOrder();
        $this->transactionFor($order, ['status' => 'failed']);

        $response = $this->get($this->resultUrl($order));

        $response->assertOk()->assertSee('Payment declined');
        $response->assertDontSee(__('payments.try_again'));
    }

    public function test_a_cancelled_transaction_shows_cancelled(): void
    {
        $order = $this->makeOrder();
        $this->transactionFor($order, ['status' => 'cancelled']);

        $this->get($this->resultUrl($order))->assertOk()->assertSee('Payment cancelled');
    }

    public function test_a_freshly_pending_transaction_does_not_trigger_verification_yet(): void
    {
        Http::fake(['api.moyasar.com/*' => Http::response(['status' => 'paid'], 200)]);

        $order = $this->makeOrder();
        $transaction = $this->transactionFor($order, ['status' => 'pending', 'created_at' => now()]);

        $this->get($this->resultUrl($order))->assertOk()->assertSee('Payment processing');

        Http::assertNothingSent();
        $this->assertSame('pending', $transaction->fresh()->status);
    }

    public function test_a_pending_transaction_past_the_grace_period_is_verified_and_resolves_to_success(): void
    {
        Http::fake([
            'api.moyasar.com/v1/invoices/inv_result_test' => Http::response(
                ['id' => 'inv_result_test', 'status' => 'paid', 'amount' => 5000, 'currency' => 'SAR'],
                200,
            ),
        ]);

        $order = $this->makeOrder();
        $transaction = $this->transactionFor($order, ['status' => 'pending', 'created_at' => now()->subSeconds(200)]);

        $response = $this->get($this->resultUrl($order));

        $response->assertOk()->assertSee('Payment successful');
        Http::assertSent(fn ($request) => str_contains($request->url(), 'inv_result_test'));
        $this->assertSame('paid', $transaction->fresh()->status);
    }

    public function test_a_pending_transaction_within_the_cooldown_of_its_last_check_is_not_rechecked(): void
    {
        Http::fake(['api.moyasar.com/*' => Http::response(['status' => 'paid'], 200)]);

        $order = $this->makeOrder();
        $transaction = $this->transactionFor($order, [
            'status' => 'pending',
            'created_at' => now()->subSeconds(200),
            'last_verified_at' => now()->subSeconds(5),
        ]);

        $this->get($this->resultUrl($order))->assertOk()->assertSee('Payment processing');

        Http::assertNothingSent();
        $this->assertSame('pending', $transaction->fresh()->status);
    }

    public function test_a_pending_transaction_shows_unreachable_when_the_provider_cannot_be_reached(): void
    {
        Http::fake(function () {
            throw new ConnectionException('down');
        });

        $order = $this->makeOrder();
        $transaction = $this->transactionFor($order, ['status' => 'pending', 'created_at' => now()->subSeconds(200)]);

        $response = $this->get($this->resultUrl($order));

        $response->assertOk()->assertSee('Payment status unavailable');
        $this->assertSame('pending', $transaction->fresh()->status, 'A provider-unreachable check must never change the stored status.');
    }
}
