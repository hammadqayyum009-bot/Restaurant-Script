<?php

namespace Tests\Feature\Payments\Moyasar;

use App\Models\Order;
use App\Models\PaymentGatewayLog;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Payments\Drivers\MoyasarDriver;
use App\Payments\Moyasar\MoyasarClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class GatewayLogRedactionTest extends TestCase
{
    use RefreshDatabase;

    protected const SECRET = 'sk_test_super_secret_do_not_leak_7f3a';

    public function test_the_secret_key_never_appears_in_any_gateway_log_row(): void
    {
        Http::fake([
            'api.moyasar.com/v1/invoices' => Http::response(['id' => 'inv_1', 'url' => 'https://checkout.moyasar.com/invoices/inv_1'], 200),
        ]);

        $order = Order::create([
            'order_number' => 'ORD-'.strtoupper(Str::random(8)), 'customer_name' => 'Test', 'phone' => '0500000000',
            'address' => 'x', 'order_type' => 'delivery', 'subtotal' => 50, 'delivery_fee' => 0, 'tax' => 0,
            'total' => 50, 'payment_method' => 'cash', 'status' => 'pending',
        ]);
        $method = PaymentMethod::factory()->create([
            'driver' => 'moyasar', 'enabled' => true, 'test_mode' => true,
            'credentials' => ['secret_key_test' => self::SECRET],
        ]);
        $transaction = PaymentTransaction::create([
            'order_id' => $order->id, 'payment_method_id' => $method->id, 'driver' => 'moyasar',
            'amount_minor' => 5000, 'currency' => 'SAR', 'status' => 'pending',
        ]);

        (new MoyasarDriver)->initiate($transaction, $method);

        $this->assertGreaterThan(0, PaymentGatewayLog::count());

        foreach (PaymentGatewayLog::all() as $log) {
            $this->assertStringNotContainsString(self::SECRET, (string) $log->request_summary);
            $this->assertStringNotContainsString(self::SECRET, (string) $log->response_summary);
        }

        // The secret is a Basic Auth header value, never part of the logged
        // request/response body — assert the raw table columns too, not
        // just the model accessors.
        $rows = DB::table('payment_gateway_logs')->get();
        foreach ($rows as $row) {
            $this->assertStringNotContainsString(self::SECRET, (string) $row->request_summary);
            $this->assertStringNotContainsString(self::SECRET, (string) $row->response_summary);
        }
    }

    public function test_redact_masks_sensitive_keys_and_card_like_numbers(): void
    {
        $redacted = MoyasarClient::redact([
            'api_key' => 'sk_test_abc',
            'password' => 'hunter2',
            'cvc' => '123',
            'number' => '4111111111111111',
            'amount' => 5000,
            'nested' => ['secret' => 'xyz', 'ok' => 'fine'],
        ]);

        $this->assertSame('[redacted]', $redacted['api_key']);
        $this->assertSame('[redacted]', $redacted['password']);
        $this->assertSame('[redacted]', $redacted['cvc']);
        $this->assertSame('[redacted-card-like-number]', $redacted['number']);
        $this->assertSame(5000, $redacted['amount']);
        $this->assertSame('[redacted]', $redacted['nested']['secret']);
        $this->assertSame('fine', $redacted['nested']['ok']);
    }
}
