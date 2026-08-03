<?php

namespace Tests\Feature\Payments;

use App\Models\PaymentStatusLog;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Payments\PaymentStatus;
use App\Payments\PaymentTransactionStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_legal_transition_is_applied_and_logged(): void
    {
        $transaction = PaymentTransaction::factory()->create(['status' => 'pending']);
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $result = app(PaymentTransactionStatusService::class)->transitionTo(
            $transaction, PaymentStatus::Paid, PaymentTransactionStatusService::SOURCE_MANUAL, $admin,
        );

        $this->assertTrue($result->applied);
        $this->assertSame('paid', $transaction->fresh()->status);
        $this->assertNotNull($transaction->fresh()->paid_at);

        $log = PaymentStatusLog::where('payment_transaction_id', $transaction->id)->latest('id')->first();
        $this->assertSame('pending', $log->old_status);
        $this->assertSame('paid', $log->new_status);
        $this->assertSame('manual', $log->source);
        $this->assertSame($admin->id, $log->actor_id);
    }

    /**
     * @dataProvider illegalTransitions
     */
    public function test_illegal_transitions_are_rejected_and_do_not_change_the_row(string $from, string $to): void
    {
        $transaction = PaymentTransaction::factory()->create(['status' => $from]);

        $result = app(PaymentTransactionStatusService::class)->transitionTo(
            $transaction, PaymentStatus::from($to), PaymentTransactionStatusService::SOURCE_WEBHOOK,
        );

        $this->assertFalse($result->applied);
        $this->assertSame($from, $transaction->fresh()->status);

        $log = PaymentStatusLog::where('payment_transaction_id', $transaction->id)->latest('id')->first();
        $this->assertSame($to, $log->new_status, 'The rejection is still logged, for audit visibility.');
    }

    public static function illegalTransitions(): array
    {
        return [
            'paid cannot go back to pending' => ['paid', 'pending'],
            'paid cannot go back to processing' => ['paid', 'processing'],
            'paid cannot go to failed (a late failure webhook must be dropped)' => ['paid', 'failed'],
            'paid cannot go to cancelled' => ['paid', 'cancelled'],
            'failed is terminal' => ['failed', 'pending'],
            'cancelled is terminal' => ['cancelled', 'pending'],
            'refunded is terminal' => ['refunded', 'paid'],
        ];
    }

    public function test_marking_paid_twice_is_idempotent_with_no_duplicate_side_effects(): void
    {
        $transaction = PaymentTransaction::factory()->create(['status' => 'pending']);
        $service = app(PaymentTransactionStatusService::class);

        $sideEffectRuns = 0;
        $sideEffect = function () use (&$sideEffectRuns) {
            $sideEffectRuns++;
        };

        $first = $service->transitionTo($transaction, PaymentStatus::Paid, PaymentTransactionStatusService::SOURCE_MANUAL, null, null, $sideEffect);
        $second = $service->transitionTo($transaction->fresh(), PaymentStatus::Paid, PaymentTransactionStatusService::SOURCE_MANUAL, null, null, $sideEffect);

        $this->assertTrue($first->applied);
        $this->assertFalse($second->applied, 'The second call is a no-op — same target status as current.');
        $this->assertSame(1, $sideEffectRuns, 'The order-update side effect must run exactly once, not twice.');

        $paidAtAfterFirst = $transaction->fresh()->paid_at;
        $this->assertEquals($paidAtAfterFirst, $transaction->fresh()->paid_at, 'paid_at must not be overwritten by the no-op.');
    }

    public function test_an_unknown_transition_source_is_rejected(): void
    {
        $transaction = PaymentTransaction::factory()->create(['status' => 'pending']);

        $this->expectException(\InvalidArgumentException::class);

        app(PaymentTransactionStatusService::class)->transitionTo($transaction, PaymentStatus::Paid, 'not-a-real-source');
    }
}
