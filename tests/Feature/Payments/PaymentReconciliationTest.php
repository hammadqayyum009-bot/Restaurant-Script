<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Payments\PaymentReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected function makeTransaction(array $overrides = []): PaymentTransaction
    {
        $order = Order::create([
            'order_number' => 'ORD-'.strtoupper(Str::random(8)),
            'customer_name' => 'Test', 'phone' => '0500000000', 'address' => 'x',
            'order_type' => 'delivery', 'subtotal' => 50, 'delivery_fee' => 0, 'tax' => 0,
            'total' => 50, 'payment_method' => 'moyasar', 'status' => 'pending',
        ]);
        $method = PaymentMethod::factory()->create([
            'driver' => $overrides['driver'] ?? 'moyasar', 'enabled' => true, 'test_mode' => true,
            'credentials' => ['secret_key_test' => 'sk_test_fake'],
        ]);

        $forced = array_intersect_key($overrides, array_flip(['created_at']));
        $fillable = array_diff_key($overrides, $forced);

        $transaction = PaymentTransaction::create(array_merge([
            'order_id' => $order->id, 'payment_method_id' => $method->id, 'driver' => 'moyasar',
            'amount_minor' => 5000, 'currency' => 'SAR', 'status' => 'pending',
            'provider_reference' => 'inv_'.Str::random(8),
        ], $fillable));

        if ($forced) {
            $transaction->forceFill($forced)->save();
        }

        return $transaction->fresh();
    }

    public function test_a_transaction_younger_than_the_threshold_is_not_stuck(): void
    {
        config(['payments.reconciliation.stuck_after_minutes' => 30]);
        $this->makeTransaction(['created_at' => now()->subMinutes(10)]);

        $this->assertSame(0, app(PaymentReconciliationService::class)->stuckCount());
    }

    public function test_a_transaction_older_than_the_threshold_is_stuck(): void
    {
        config(['payments.reconciliation.stuck_after_minutes' => 30]);
        $this->makeTransaction(['created_at' => now()->subMinutes(45)]);

        $this->assertSame(1, app(PaymentReconciliationService::class)->stuckCount());
    }

    public function test_a_paid_transaction_is_never_stuck_regardless_of_age(): void
    {
        config(['payments.reconciliation.stuck_after_minutes' => 30]);
        $this->makeTransaction(['status' => 'paid', 'created_at' => now()->subDays(2)]);

        $this->assertSame(0, app(PaymentReconciliationService::class)->stuckCount());
    }

    public function test_cash_on_delivery_is_never_stuck_since_it_has_no_provider_to_ask(): void
    {
        config(['payments.reconciliation.stuck_after_minutes' => 30]);
        $this->makeTransaction(['driver' => 'cod', 'created_at' => now()->subDays(2)]);

        $this->assertSame(0, app(PaymentReconciliationService::class)->stuckCount());
    }

    public function test_run_verifies_every_stuck_transaction_and_reports_counts(): void
    {
        config(['payments.reconciliation.stuck_after_minutes' => 30]);

        Http::fake([
            'api.moyasar.com/*' => Http::response(['status' => 'paid', 'amount' => 5000, 'currency' => 'SAR'], 200),
        ]);

        $transaction = $this->makeTransaction(['created_at' => now()->subMinutes(45)]);

        $result = app(PaymentReconciliationService::class)->run();

        $this->assertTrue($result['ran']);
        $this->assertSame(1, $result['checked']);
        $this->assertSame(1, $result['resolved']);
        $this->assertSame('paid', $transaction->fresh()->status);
    }

    /**
     * The run-level lock is separate from the per-transaction row lock
     * PaymentTransactionStatusService already takes — this proves the
     * run-level lock specifically, by holding it before calling run() and
     * confirming the whole batch is skipped, not just individually raced.
     */
    public function test_a_second_run_is_skipped_while_the_first_still_holds_the_lock(): void
    {
        config(['payments.reconciliation.stuck_after_minutes' => 30]);
        $this->makeTransaction(['created_at' => now()->subMinutes(45)]);

        $lock = Cache::lock('payments-reconciliation-run', 300);
        $this->assertTrue($lock->get());

        $result = app(PaymentReconciliationService::class)->run();

        $this->assertFalse($result['ran']);
        $this->assertSame(0, $result['checked']);

        $lock->release();
    }

    public function test_the_artisan_command_reports_what_it_did(): void
    {
        config(['payments.reconciliation.stuck_after_minutes' => 30]);
        Http::fake(['api.moyasar.com/*' => Http::response(['status' => 'failed'], 200)]);
        $this->makeTransaction(['created_at' => now()->subMinutes(45)]);

        $this->artisan('payments:reconcile')
            ->expectsOutputToContain('Checked 1 stuck transaction(s), resolved 1.')
            ->assertExitCode(0);
    }
}
