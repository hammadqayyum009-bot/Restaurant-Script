<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\Billing\DocumentIssuer;
use Database\Factories\OrderItemFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Payments used to read a separate, .env-only payments.currency setting
 * with no admin field and no relationship to config('site.currency') — the
 * value Billing's DocumentIssuer has always read, and the value the
 * storefront actually charges the customer in. Changing the storefront
 * currency (an ordinary admin action) never touched the old Payments-only
 * value, so a transaction could end up tagged with a currency the customer
 * was never shown. This proves Payments now reads the same source Billing
 * does, enforcing by test the cross-module check the full-project audit did
 * by hand.
 */
class CurrencyConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function makeOrder(): Order
    {
        $order = Order::create([
            'order_number' => 'ORD-'.strtoupper(Str::random(8)),
            'customer_name' => 'Test Customer', 'phone' => '0500000000', 'address' => 'x',
            'order_type' => 'delivery', 'subtotal' => 100, 'delivery_fee' => 0, 'tax' => 0,
            'total' => 100, 'payment_method' => 'cash', 'status' => 'pending',
        ]);

        OrderItemFactory::new()->create([
            'order_id' => $order->id, 'price' => 100, 'quantity' => 1, 'line_total' => 100,
        ]);

        return $order;
    }

    public function test_a_new_transaction_picks_up_a_changed_site_currency_with_no_other_change(): void
    {
        config(['site.currency' => 'AED']);
        $order = $this->makeOrder();
        $method = PaymentMethod::factory()->create(['driver' => 'cod', 'enabled' => true]);

        $url = URL::temporarySignedRoute('checkout.payment.show', now()->addHours(6), ['order' => $order->id]);
        $this->post($url, ['payment_method_id' => $method->id]);

        $this->assertSame('AED', $order->paymentTransactions()->first()->currency);
    }

    public function test_billing_and_payments_resolve_the_identical_currency_for_the_same_order(): void
    {
        config(['site.currency' => 'KWD']);
        $order = $this->makeOrder();
        $method = PaymentMethod::factory()->create(['driver' => 'cod', 'enabled' => true]);

        $url = URL::temporarySignedRoute('checkout.payment.show', now()->addHours(6), ['order' => $order->id]);
        $this->post($url, ['payment_method_id' => $method->id]);

        $paymentsCurrency = $order->paymentTransactions()->first()->currency;
        $billingCurrency = app(DocumentIssuer::class)->createDraftFromOrder($order, 'simplified_tax_invoice')->currency;

        $this->assertSame('KWD', $paymentsCurrency);
        $this->assertSame('KWD', $billingCurrency);
        $this->assertSame($billingCurrency, $paymentsCurrency, 'Billing and Payments must always resolve the same currency for the same order.');
    }
}
