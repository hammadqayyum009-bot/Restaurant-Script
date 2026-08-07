<?php

namespace Tests\Feature\Payments\Moyasar;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Payments\Drivers\MoyasarDriver;
use App\Payments\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoiceCreationTest extends TestCase
{
    use RefreshDatabase;

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

    protected function moyasarMethod(): PaymentMethod
    {
        return PaymentMethod::factory()->create([
            'driver' => 'moyasar',
            'enabled' => true,
            'test_mode' => true,
            'credentials' => ['secret_key_test' => 'sk_test_fake'],
        ]);
    }

    public function test_the_amount_sent_to_moyasar_always_comes_from_the_stored_transaction_never_the_request(): void
    {
        Http::fake([
            'api.moyasar.com/v1/invoices' => Http::response(['id' => 'inv_123', 'url' => 'https://checkout.moyasar.com/invoices/inv_123'], 200),
        ]);

        $order = $this->makeOrder(['total' => 50]);
        $method = $this->moyasarMethod();

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'payment_method_id' => $method->id,
            'driver' => 'moyasar',
            'amount_minor' => Money::toMinor('50.00', 'SAR'),
            'currency' => 'SAR',
            'status' => 'pending',
        ]);

        // Simulates a tampered request carrying a completely different
        // amount — the driver never reads request input at all, so this
        // can have no effect on what is sent.
        $this->get('/?amount=1');

        (new MoyasarDriver)->initiate($transaction, $method);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.moyasar.com/v1/invoices'
                && $request['amount'] === 5000;
        });
    }

    public function test_a_three_decimal_currency_is_sent_in_correct_minor_units(): void
    {
        Http::fake([
            'api.moyasar.com/v1/invoices' => Http::response(['id' => 'inv_456', 'url' => 'https://checkout.moyasar.com/invoices/inv_456'], 200),
        ]);

        $order = $this->makeOrder(['total' => 12.5]);
        $method = $this->moyasarMethod();

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'payment_method_id' => $method->id,
            'driver' => 'moyasar',
            'amount_minor' => Money::toMinor('12.500', 'KWD'),
            'currency' => 'KWD',
            'status' => 'pending',
        ]);

        (new MoyasarDriver)->initiate($transaction, $method);

        Http::assertSent(function ($request) {
            return $request['amount'] === 12500 && $request['currency'] === 'KWD';
        });
    }

    public function test_initiate_returns_a_redirect_result_with_the_hosted_checkout_url(): void
    {
        Http::fake([
            'api.moyasar.com/v1/invoices' => Http::response(['id' => 'inv_789', 'url' => 'https://checkout.moyasar.com/invoices/inv_789'], 200),
        ]);

        $order = $this->makeOrder();
        $method = $this->moyasarMethod();
        $transaction = PaymentTransaction::create([
            'order_id' => $order->id, 'payment_method_id' => $method->id, 'driver' => 'moyasar',
            'amount_minor' => 5000, 'currency' => 'SAR', 'status' => 'pending',
        ]);

        $result = (new MoyasarDriver)->initiate($transaction, $method);

        $this->assertTrue($result->requiresRedirect);
        $this->assertSame('https://checkout.moyasar.com/invoices/inv_789', $result->redirectUrl);
    }
}
