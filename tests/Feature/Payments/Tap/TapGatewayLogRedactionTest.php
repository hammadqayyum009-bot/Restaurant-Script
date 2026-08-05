<?php

namespace Tests\Feature\Payments\Tap;

use App\Models\Order;
use App\Models\PaymentGatewayLog;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Payments\Drivers\TapDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Redaction and the gateway-log write are shared with MoyasarClient via
 * LogsGatewayRequestsSafely — this proves the shared trait behaves
 * identically for Tap, not just that Moyasar's own test still passes.
 */
class TapGatewayLogRedactionTest extends TestCase
{
    use RefreshDatabase;

    protected const SECRET = 'sk_test_tap_super_secret_do_not_leak_9c1b';

    public function test_the_secret_key_never_appears_in_any_gateway_log_row(): void
    {
        Http::fake([
            'api.tap.company/v2/charges' => Http::response(['id' => 'chg_1', 'transaction' => ['url' => 'https://tap.company/pay/chg_1']], 200),
        ]);

        $order = Order::create([
            'order_number' => 'ORD-'.strtoupper(Str::random(8)), 'customer_name' => 'Test', 'phone' => '0500000000',
            'address' => 'x', 'order_type' => 'delivery', 'subtotal' => 50, 'delivery_fee' => 0, 'tax' => 0,
            'total' => 50, 'payment_method' => 'cash', 'status' => 'pending',
        ]);
        $method = PaymentMethod::factory()->create([
            'driver' => 'tap', 'enabled' => true, 'test_mode' => true,
            'credentials' => ['secret_key_test' => self::SECRET],
        ]);
        $transaction = PaymentTransaction::create([
            'order_id' => $order->id, 'payment_method_id' => $method->id, 'driver' => 'tap',
            'amount_minor' => 5000, 'currency' => 'SAR', 'status' => 'pending',
        ]);

        (new TapDriver)->initiate($transaction, $method);

        $this->assertGreaterThan(0, PaymentGatewayLog::count());

        foreach (PaymentGatewayLog::all() as $log) {
            $this->assertStringNotContainsString(self::SECRET, (string) $log->request_summary);
            $this->assertStringNotContainsString(self::SECRET, (string) $log->response_summary);
        }

        $rows = DB::table('payment_gateway_logs')->get();
        foreach ($rows as $row) {
            $this->assertStringNotContainsString(self::SECRET, (string) $row->request_summary);
            $this->assertStringNotContainsString(self::SECRET, (string) $row->response_summary);
        }
    }

    public function test_the_gateway_log_row_correctly_records_the_tap_driver_name(): void
    {
        Http::fake([
            'api.tap.company/v2/charges' => Http::response(['id' => 'chg_2', 'transaction' => ['url' => 'https://tap.company/pay/chg_2']], 200),
        ]);

        $order = Order::create([
            'order_number' => 'ORD-'.strtoupper(Str::random(8)), 'customer_name' => 'Test', 'phone' => '0500000000',
            'address' => 'x', 'order_type' => 'delivery', 'subtotal' => 50, 'delivery_fee' => 0, 'tax' => 0,
            'total' => 50, 'payment_method' => 'cash', 'status' => 'pending',
        ]);
        $method = PaymentMethod::factory()->create([
            'driver' => 'tap', 'enabled' => true, 'test_mode' => true,
            'credentials' => ['secret_key_test' => 'sk_test_fake'],
        ]);
        $transaction = PaymentTransaction::create([
            'order_id' => $order->id, 'payment_method_id' => $method->id, 'driver' => 'tap',
            'amount_minor' => 5000, 'currency' => 'SAR', 'status' => 'pending',
        ]);

        (new TapDriver)->initiate($transaction, $method);

        $log = PaymentGatewayLog::first();
        $this->assertSame('tap', $log->driver);
    }
}
