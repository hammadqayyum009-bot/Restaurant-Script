<?php

namespace Tests\Feature\Payments;

use App\Models\PaymentStatusLog;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkPaymentPaidTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_active' => true]);
    }

    public function test_an_admin_can_mark_a_pending_transaction_paid(): void
    {
        $admin = $this->admin();
        $transaction = PaymentTransaction::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($admin, 'web')->put(route('admin.payment-transactions.mark-paid', $transaction));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame('paid', $transaction->fresh()->status);

        $log = PaymentStatusLog::where('payment_transaction_id', $transaction->id)->latest('id')->first();
        $this->assertSame($admin->id, $log->actor_id, 'The acting user must be recorded.');
        $this->assertSame('manual', $log->source);
    }

    public function test_marking_an_already_paid_transaction_paid_again_is_a_no_op(): void
    {
        $admin = $this->admin();
        $transaction = PaymentTransaction::factory()->create(['status' => 'pending']);

        $this->actingAs($admin, 'web')->put(route('admin.payment-transactions.mark-paid', $transaction));
        $paidAt = $transaction->fresh()->paid_at;
        $logCountAfterFirst = PaymentStatusLog::where('payment_transaction_id', $transaction->id)->count();

        // The policy itself blocks a second mark-paid attempt through the UI
        // (status is no longer "pending"), which is the first line of
        // defence; this proves the underlying service is also idempotent if
        // ever reached a second time (e.g. a retried request).
        $response = $this->actingAs($admin, 'web')->put(route('admin.payment-transactions.mark-paid', $transaction));

        $response->assertForbidden();
        $this->assertEquals($paidAt, $transaction->fresh()->paid_at);
        $this->assertSame($logCountAfterFirst, PaymentStatusLog::where('payment_transaction_id', $transaction->id)->count());
    }

    public function test_a_guest_cannot_mark_a_transaction_paid(): void
    {
        $transaction = PaymentTransaction::factory()->create(['status' => 'pending']);

        $response = $this->put(route('admin.payment-transactions.mark-paid', $transaction));

        $response->assertRedirect(route('admin.login'));
        $this->assertSame('pending', $transaction->fresh()->status);
    }

    public function test_a_non_admin_cannot_mark_a_transaction_paid(): void
    {
        $customer = User::factory()->create(['is_admin' => false, 'is_active' => true]);
        $transaction = PaymentTransaction::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($customer, 'web')->put(route('admin.payment-transactions.mark-paid', $transaction));

        $response->assertRedirect(route('admin.login'));
        $this->assertSame('pending', $transaction->fresh()->status);
    }
}
