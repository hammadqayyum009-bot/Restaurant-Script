<?php

namespace Tests\Feature\Payments\Moyasar;

use App\Models\ActivityLog;
use App\Models\EmailLog;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Payments\PaymentTransactionStatusService;
use App\Payments\PaymentVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class VerificationMismatchTest extends TestCase
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
            'driver' => 'moyasar', 'enabled' => true, 'test_mode' => true,
            'credentials' => ['secret_key_test' => 'sk_test_fake'],
        ]);

        return PaymentTransaction::create([
            'order_id' => $order->id, 'payment_method_id' => $method->id, 'driver' => 'moyasar',
            'amount_minor' => 5000, 'currency' => 'SAR', 'status' => 'pending',
            'provider_reference' => 'inv_mismatch',
        ]);
    }

    public function test_an_amount_mismatch_never_marks_the_transaction_paid(): void
    {
        config(['notifications.admin_email' => 'admin@example.com']);

        Http::fake([
            'api.moyasar.com/v1/invoices/inv_mismatch' => Http::response([
                'id' => 'inv_mismatch', 'status' => 'paid', 'amount' => 9999, 'currency' => 'SAR',
            ], 200),
        ]);

        $transaction = $this->transaction();

        app(PaymentVerificationService::class)->verify($transaction, PaymentTransactionStatusService::SOURCE_VERIFICATION);

        $fresh = $transaction->fresh();
        $this->assertSame('pending', $fresh->status);
        $this->assertStringContainsString('amount mismatch', $fresh->failure_reason);

        $this->assertSame(1, ActivityLog::where('action', 'payment_amount_mismatch')->count());
        $this->assertSame(1, EmailLog::where('type', 'payment_security_alert')->count());
    }

    public function test_a_currency_mismatch_never_marks_the_transaction_paid(): void
    {
        Http::fake([
            'api.moyasar.com/v1/invoices/inv_mismatch' => Http::response([
                'id' => 'inv_mismatch', 'status' => 'paid', 'amount' => 5000, 'currency' => 'USD',
            ], 200),
        ]);

        $transaction = $this->transaction();

        app(PaymentVerificationService::class)->verify($transaction, PaymentTransactionStatusService::SOURCE_VERIFICATION);

        $this->assertSame('pending', $transaction->fresh()->status);
    }

    public function test_a_late_failed_result_after_already_paid_does_not_change_the_status(): void
    {
        $transaction = $this->transaction();
        $transaction->update(['status' => 'paid']);

        Http::fake([
            'api.moyasar.com/v1/invoices/inv_mismatch' => Http::response([
                'id' => 'inv_mismatch', 'status' => 'failed', 'amount' => 5000, 'currency' => 'SAR',
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
