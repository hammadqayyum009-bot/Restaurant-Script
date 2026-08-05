<?php

namespace Tests\Feature\Payments\Tap;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class TapCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function makeOrder(): Order
    {
        return Order::create([
            'order_number' => 'ORD-'.strtoupper(Str::random(8)),
            'customer_name' => 'Test Customer',
            'phone' => '0500000000',
            'address' => 'Test address',
            'order_type' => 'delivery',
            'subtotal' => 50, 'delivery_fee' => 0, 'tax' => 0, 'total' => 50,
            'payment_method' => 'cash', 'status' => 'pending',
        ]);
    }

    protected function tapTransaction(string $tapStatus): PaymentTransaction
    {
        $order = $this->makeOrder();
        $method = PaymentMethod::factory()->create([
            'driver' => 'tap', 'enabled' => true, 'test_mode' => true,
            'credentials' => ['secret_key_test' => 'sk_test_fake'],
        ]);

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id, 'payment_method_id' => $method->id, 'driver' => 'tap',
            'amount_minor' => 5000, 'currency' => 'SAR', 'status' => 'pending',
            'provider_reference' => 'chg_callback_test',
        ]);

        Http::fake([
            'api.tap.company/v2/charges/chg_callback_test' => Http::response([
                'id' => 'chg_callback_test', 'status' => $tapStatus, 'amount' => '50.00', 'currency' => 'SAR',
            ], 200),
        ]);

        return $transaction;
    }

    public function test_the_callback_alone_does_not_mark_a_transaction_paid_it_only_triggers_verification(): void
    {
        $transaction = $this->tapTransaction('FAILED');

        $response = $this->get('/payments/tap/callback?transaction='.$transaction->id.'&tap_id=chg_callback_test');

        $response->assertRedirect(route('checkout.success', $transaction->order->order_number));
        $this->assertSame('failed', $transaction->fresh()->status);
    }

    public function test_a_successful_provider_verification_via_callback_marks_the_transaction_paid(): void
    {
        $transaction = $this->tapTransaction('CAPTURED');

        $this->get('/payments/tap/callback?transaction='.$transaction->id.'&tap_id=chg_callback_test');

        $this->assertSame('paid', $transaction->fresh()->status);
    }

    public function test_a_callback_with_a_transaction_id_that_does_not_exist_shows_a_generic_message(): void
    {
        $response = $this->get('/payments/tap/callback?transaction=999999&tap_id=chg_x');

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error');
        $this->assertStringNotContainsString('SQL', session('error'));
    }

    public function test_the_confirmed_order_moves_to_confirmed_status_once_paid(): void
    {
        $transaction = $this->tapTransaction('CAPTURED');
        $order = $transaction->order;
        $this->assertSame('pending', $order->status);

        $this->get('/payments/tap/callback?transaction='.$transaction->id.'&tap_id=chg_callback_test');

        $this->assertSame('confirmed', $order->fresh()->status);
    }
}
