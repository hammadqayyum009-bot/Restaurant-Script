<?php

namespace Tests\Feature\Payments\Tap;

use App\Models\ActivityLog;
use App\Models\EmailLog;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Payments\PaymentTransactionStatusService;
use App\Payments\PaymentVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class TapVerificationMismatchTest extends TestCase
{
    use RefreshDatabase;

    protected function transaction(): PaymentTransaction
    {
        $order = Order::create([
            'order_number' => 'ORD-'.strtoupper(Str::random(8)), 'customer_name' => 'Test', 'phone' => '0500000000',
            'address' => 'x', 'order_type' => 'delivery', 'subtotal' => 50, 'delivery_fee' => 0, 'tax' => 0,
            'total' => 50, 'payment_method' => 'cash', 'status' => 'pending',
        ]);
        $method = PaymentMethod::factory()->create([
            'driver' => 'tap', 'enabled' => true, 'test_mode' => true,
            'credentials' => ['secret_key_test' => 'sk_test_fake'],
        ]);

        return PaymentTransaction::create([
            'order_id' => $order->id, 'payment_method_id' => $method->id, 'driver' => 'tap',
            'amount_minor' => 5000, 'currency' => 'SAR', 'status' => 'pending',
            'provider_reference' => 'chg_mismatch',
        ]);
    }

    public function test_an_amount_mismatch_never_marks_the_transaction_paid(): void
    {
        config(['notifications.admin_email' => 'admin@example.com']);

        Http::fake([
            'api.tap.company/v2/charges/chg_mismatch' => Http::response([
                'id' => 'chg_mismatch', 'status' => 'CAPTURED', 'amount' => '99.99', 'currency' => 'SAR',
            ], 200),
        ]);

        $transaction = $this->transaction();
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $this->actingAs($admin, 'web')->put(route('admin.payment-transactions.reverify', $transaction));

        $fresh = $transaction->fresh();
        $this->assertSame('pending', $fresh->status);
        $this->assertStringContainsString('amount mismatch', $fresh->failure_reason);

        $this->assertSame(1, ActivityLog::where('action', 'payment_amount_mismatch')->count());
        $this->assertSame(1, EmailLog::where('type', 'payment_security_alert')->count());
    }

    public function test_a_currency_mismatch_never_marks_the_transaction_paid(): void
    {
        Http::fake([
            'api.tap.company/v2/charges/chg_mismatch' => Http::response([
                'id' => 'chg_mismatch', 'status' => 'CAPTURED', 'amount' => '50.00', 'currency' => 'USD',
            ], 200),
        ]);

        $transaction = $this->transaction();

        app(PaymentVerificationService::class)->verify($transaction, PaymentTransactionStatusService::SOURCE_VERIFICATION);

        $this->assertSame('pending', $transaction->fresh()->status);
    }

    /**
     * A 3-decimal currency specifically — the currency exponent must be
     * applied on both sides (our conversion out, Tap's conversion back) or
     * a false mismatch (or worse, a false match) would result.
     */
    public function test_a_matching_three_decimal_currency_amount_is_verified_correctly(): void
    {
        Http::fake([
            'api.tap.company/v2/charges/chg_mismatch' => Http::response([
                'id' => 'chg_mismatch', 'status' => 'CAPTURED', 'amount' => '50.000', 'currency' => 'KWD',
            ], 200),
        ]);

        $transaction = $this->transaction();
        $transaction->update(['amount_minor' => 50000, 'currency' => 'KWD']);

        app(PaymentVerificationService::class)->verify($transaction, PaymentTransactionStatusService::SOURCE_VERIFICATION);

        $this->assertSame('paid', $transaction->fresh()->status);
    }

    public function test_a_late_failed_result_after_already_paid_does_not_change_the_status(): void
    {
        $transaction = $this->transaction();
        $transaction->update(['status' => 'paid']);

        Http::fake([
            'api.tap.company/v2/charges/chg_mismatch' => Http::response([
                'id' => 'chg_mismatch', 'status' => 'FAILED', 'amount' => '50.00', 'currency' => 'SAR',
            ], 200),
        ]);

        app(PaymentVerificationService::class)->verify($transaction, PaymentTransactionStatusService::SOURCE_VERIFICATION);

        $this->assertSame('paid', $transaction->fresh()->status);
    }

    public function test_a_provider_timeout_leaves_the_transaction_pending_not_paid(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out.');
        });

        $transaction = $this->transaction();

        $result = app(PaymentVerificationService::class)->verify($transaction, PaymentTransactionStatusService::SOURCE_VERIFICATION);

        $this->assertFalse($result->applied);
        $this->assertSame('pending', $transaction->fresh()->status);
        $this->assertNotNull($transaction->fresh()->last_verified_at);
    }
}
