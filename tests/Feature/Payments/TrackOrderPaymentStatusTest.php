<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class TrackOrderPaymentStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function trackOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'ORD-'.strtoupper(Str::random(8)),
            'customer_name' => 'Test Customer', 'phone' => '0500000000', 'email' => 'track@example.com',
            'address' => 'x', 'order_type' => 'delivery', 'subtotal' => 50, 'delivery_fee' => 0,
            'tax' => 0, 'total' => 50, 'payment_method' => 'moyasar', 'status' => 'pending',
        ], $overrides));
    }

    protected function find(Order $order): TestResponse
    {
        return $this->post(route('track.find'), [
            'order_number' => $order->order_number,
            'contact' => $order->email,
        ]);
    }

    public function test_the_payment_status_of_the_latest_transaction_is_shown(): void
    {
        $order = $this->trackOrder();
        $method = PaymentMethod::factory()->create(['driver' => 'moyasar', 'enabled' => true]);
        PaymentTransaction::create([
            'order_id' => $order->id, 'payment_method_id' => $method->id, 'driver' => 'moyasar',
            'amount_minor' => 5000, 'currency' => 'SAR', 'status' => 'failed',
        ]);

        $this->find($order)->assertOk()->assertSee(__('payments.status_failed'));
    }

    public function test_a_retry_link_is_shown_for_a_failed_transaction_with_attempts_remaining(): void
    {
        config(['payments.max_attempts_per_order' => 5]);
        $order = $this->trackOrder();
        $method = PaymentMethod::factory()->create(['driver' => 'moyasar', 'enabled' => true]);
        PaymentTransaction::create([
            'order_id' => $order->id, 'payment_method_id' => $method->id, 'driver' => 'moyasar',
            'amount_minor' => 5000, 'currency' => 'SAR', 'status' => 'failed',
        ]);

        $response = $this->find($order);

        $response->assertOk();
        $response->assertSee(route('checkout.payment.show', ['order' => $order->id]), false);
    }

    public function test_no_retry_link_is_shown_once_the_attempt_cap_is_reached(): void
    {
        config(['payments.max_attempts_per_order' => 1]);
        $order = $this->trackOrder();
        $method = PaymentMethod::factory()->create(['driver' => 'moyasar', 'enabled' => true]);
        PaymentTransaction::create([
            'order_id' => $order->id, 'payment_method_id' => $method->id, 'driver' => 'moyasar',
            'amount_minor' => 5000, 'currency' => 'SAR', 'status' => 'failed',
        ]);

        $response = $this->find($order);

        $response->assertOk();
        $response->assertDontSee(__('payments.retry_link'));
    }

    public function test_no_retry_link_is_shown_for_a_paid_transaction(): void
    {
        $order = $this->trackOrder();
        $method = PaymentMethod::factory()->create(['driver' => 'moyasar', 'enabled' => true]);
        PaymentTransaction::create([
            'order_id' => $order->id, 'payment_method_id' => $method->id, 'driver' => 'moyasar',
            'amount_minor' => 5000, 'currency' => 'SAR', 'status' => 'paid',
        ]);

        $response = $this->find($order);

        $response->assertOk();
        $response->assertDontSee(__('payments.retry_link'));
    }

    public function test_an_order_with_no_transaction_yet_shows_no_payment_status(): void
    {
        $order = $this->trackOrder();

        $this->find($order)->assertOk();
    }
}
