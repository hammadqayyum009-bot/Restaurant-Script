<?php

namespace Tests\Feature\Payments\Tap;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class TapRefundTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_active' => true]);
    }

    protected function paidTransaction(): PaymentTransaction
    {
        $order = Order::create([
            'order_number' => 'ORD-'.strtoupper(Str::random(8)), 'customer_name' => 'Test', 'phone' => '0500000000',
            'address' => 'x', 'order_type' => 'delivery', 'subtotal' => 50, 'delivery_fee' => 0, 'tax' => 0,
            'total' => 50, 'payment_method' => 'cash', 'status' => 'confirmed',
        ]);
        $method = PaymentMethod::factory()->create([
            'driver' => 'tap', 'enabled' => true, 'test_mode' => true,
            'credentials' => ['secret_key_test' => 'sk_test_fake'],
        ]);

        return PaymentTransaction::create([
            'order_id' => $order->id, 'payment_method_id' => $method->id, 'driver' => 'tap',
            'amount_minor' => 5000, 'currency' => 'SAR', 'status' => 'paid',
            'provider_reference' => 'chg_refund',
        ]);
    }

    public function test_a_full_refund_marks_the_transaction_refunded(): void
    {
        Http::fake(['api.tap.company/v2/refunds' => Http::response(['id' => 'rf_1', 'charge_id' => 'chg_refund'], 200)]);

        $transaction = $this->paidTransaction();

        $response = $this->actingAs($this->admin(), 'web')->post(route('admin.payment-transactions.refund', $transaction), [
            'amount' => '50.00',
            'reason' => 'Customer requested a full refund.',
        ]);

        $response->assertRedirect();
        $fresh = $transaction->fresh();
        $this->assertSame('refunded', $fresh->status);
        $this->assertSame(5000, $fresh->refunded_amount_minor);
        $this->assertNotNull($fresh->refunded_at);

        Http::assertSent(fn ($request) => str_contains($request->body(), '"charge_id":"chg_refund"')
            && str_contains($request->body(), '"amount":"50.00"'));
    }

    public function test_a_partial_refund_marks_the_transaction_partially_refunded(): void
    {
        Http::fake(['api.tap.company/v2/refunds' => Http::response(['id' => 'rf_2'], 200)]);

        $transaction = $this->paidTransaction();

        $this->actingAs($this->admin(), 'web')->post(route('admin.payment-transactions.refund', $transaction), [
            'amount' => '20.00',
            'reason' => 'Partial refund for a missing item.',
        ]);

        $fresh = $transaction->fresh();
        $this->assertSame('partially_refunded', $fresh->status);
        $this->assertSame(2000, $fresh->refunded_amount_minor);
    }

    public function test_a_refund_amount_exceeding_the_original_is_rejected_before_calling_the_provider(): void
    {
        Http::fake();

        $transaction = $this->paidTransaction();

        $response = $this->actingAs($this->admin(), 'web')->post(route('admin.payment-transactions.refund', $transaction), [
            'amount' => '999.00',
            'reason' => 'This should never reach Tap.',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('error');
        $this->assertSame(0, $transaction->fresh()->refunded_amount_minor);
        Http::assertNothingSent();
    }

    public function test_a_second_partial_refund_cannot_push_the_total_past_the_original_amount(): void
    {
        Http::fake(['api.tap.company/v2/refunds' => Http::response(['id' => 'rf_3'], 200)]);

        $transaction = $this->paidTransaction();
        $admin = $this->admin();

        $this->actingAs($admin, 'web')->post(route('admin.payment-transactions.refund', $transaction), [
            'amount' => '30.00', 'reason' => 'First partial refund of the order.',
        ]);

        $response = $this->actingAs($admin, 'web')->post(route('admin.payment-transactions.refund', $transaction), [
            'amount' => '30.00', 'reason' => 'Second refund that would overshoot.',
        ]);

        $response->assertSessionHas('error');
        $this->assertSame(3000, $transaction->fresh()->refunded_amount_minor);
    }

    public function test_a_failed_provider_refund_releases_the_reservation_and_does_not_change_status(): void
    {
        Http::fake(['api.tap.company/v2/refunds' => Http::response(['message' => 'error'], 422)]);

        $transaction = $this->paidTransaction();

        $response = $this->actingAs($this->admin(), 'web')->post(route('admin.payment-transactions.refund', $transaction), [
            'amount' => '20.00', 'reason' => 'This provider call will fail.',
        ]);

        $response->assertSessionHas('error');
        $fresh = $transaction->fresh();
        $this->assertSame(0, $fresh->refunded_amount_minor, 'The reservation must be released on failure.');
        $this->assertSame('paid', $fresh->status);
    }

    public function test_a_pending_transaction_cannot_be_refunded(): void
    {
        $transaction = $this->paidTransaction();
        $transaction->update(['status' => 'pending']);

        $response = $this->actingAs($this->admin(), 'web')->post(route('admin.payment-transactions.refund', $transaction), [
            'amount' => '10.00', 'reason' => 'Should be blocked by policy.',
        ]);

        $response->assertForbidden();
    }

    public function test_a_non_admin_cannot_refund(): void
    {
        $customer = User::factory()->create(['is_admin' => false, 'is_active' => true]);
        $transaction = $this->paidTransaction();

        $response = $this->actingAs($customer, 'web')->post(route('admin.payment-transactions.refund', $transaction), [
            'amount' => '10.00', 'reason' => 'Should be blocked entirely.',
        ]);

        $response->assertRedirect(route('admin.login'));
    }
}
