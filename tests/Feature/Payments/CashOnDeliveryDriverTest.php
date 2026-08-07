<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Payments\Drivers\CashOnDeliveryDriver;
use App\Payments\Exceptions\RefundNotSupportedException;
use App\Payments\Money;
use App\Payments\PaymentDriverRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tests\TestCase;

class CashOnDeliveryDriverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_is_always_configured_since_it_needs_no_credentials(): void
    {
        $method = PaymentMethod::factory()->create(['driver' => 'cod']);

        $this->assertTrue((new CashOnDeliveryDriver)->isConfigured($method));
    }

    public function test_initiate_requires_no_redirect_and_confirms_the_order(): void
    {
        $order = $this->makeOrder(['status' => 'pending', 'total' => 80]);
        $method = PaymentMethod::factory()->create(['driver' => 'cod']);
        $transaction = PaymentTransaction::factory()->create([
            'order_id' => $order->id,
            'payment_method_id' => $method->id,
            'status' => 'pending',
        ]);

        $result = (new CashOnDeliveryDriver)->initiate($transaction, $method);

        $this->assertFalse($result->requiresRedirect);
        $this->assertNull($result->redirectUrl);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame('pending', $transaction->fresh()->status, 'Cash has not been collected yet — the transaction itself stays pending.');
    }

    public function test_refund_and_webhook_are_declared_unsupported(): void
    {
        $driver = new CashOnDeliveryDriver;
        $method = PaymentMethod::factory()->create(['driver' => 'cod']);
        $transaction = PaymentTransaction::factory()->create(['payment_method_id' => $method->id]);

        $this->assertFalse($driver->supportsRefund());

        $this->expectException(RefundNotSupportedException::class);
        $driver->refund($transaction, $method, 1000, 'test');
    }

    public function test_handling_a_webhook_throws_since_cod_never_receives_one(): void
    {
        $driver = new CashOnDeliveryDriver;
        $method = PaymentMethod::factory()->create(['driver' => 'cod']);

        $this->expectException(\LogicException::class);
        $driver->handleWebhook($method, Request::create('/'));
    }

    public function test_the_configured_max_order_amount_excludes_orders_over_the_limit(): void
    {
        PaymentMethod::factory()->create([
            'driver' => 'cod',
            'enabled' => true,
            'max_order_amount_minor' => Money::toMinor('200.00', 'SAR'),
        ]);

        $withinLimit = $this->makeOrder(['total' => 150, 'order_type' => 'delivery']);
        $overLimit = $this->makeOrder(['total' => 250, 'order_type' => 'delivery']);

        $registry = app(PaymentDriverRegistry::class);

        $this->assertTrue($registry->availableFor($withinLimit)->isNotEmpty());
        $this->assertTrue($registry->availableFor($overLimit)->isEmpty());
    }

    public function test_restricting_to_delivery_excludes_pickup_orders(): void
    {
        PaymentMethod::factory()->create([
            'driver' => 'cod',
            'enabled' => true,
            'allowed_order_types' => ['delivery'],
        ]);

        $delivery = $this->makeOrder(['total' => 50, 'order_type' => 'delivery']);
        $pickup = $this->makeOrder(['total' => 50, 'order_type' => 'pickup']);

        $registry = app(PaymentDriverRegistry::class);

        $this->assertTrue($registry->availableFor($delivery)->isNotEmpty());
        $this->assertTrue($registry->availableFor($pickup)->isEmpty());
    }

    /**
     * App\Models\Order has no HasFactory trait — every test in this app
     * builds one with a direct Order::create() call instead.
     */
    protected function makeOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'ORD-'.strtoupper(Str::random(8)),
            'customer_name' => 'Test Customer',
            'phone' => '0500000000',
            'address' => 'Test address',
            'order_type' => 'delivery',
            'subtotal' => 50, 'delivery_fee' => 0, 'tax' => 0, 'total' => 50,
            'payment_method' => 'cash', 'status' => 'pending',
        ], $overrides));
    }
}
