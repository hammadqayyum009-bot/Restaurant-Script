<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class StuckPaymentsAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_active' => true]);
    }

    protected function stuckTransaction(): PaymentTransaction
    {
        config(['payments.reconciliation.stuck_after_minutes' => 30]);

        $order = Order::create([
            'order_number' => 'ORD-'.strtoupper(Str::random(8)),
            'customer_name' => 'Test', 'phone' => '0500000000', 'address' => 'x',
            'order_type' => 'delivery', 'subtotal' => 50, 'delivery_fee' => 0, 'tax' => 0,
            'total' => 50, 'payment_method' => 'moyasar', 'status' => 'pending',
        ]);
        $method = PaymentMethod::factory()->create([
            'driver' => 'moyasar', 'enabled' => true, 'test_mode' => true,
            'credentials' => ['secret_key_test' => 'sk_test_fake'],
        ]);
        $transaction = PaymentTransaction::create([
            'order_id' => $order->id, 'payment_method_id' => $method->id, 'driver' => 'moyasar',
            'amount_minor' => 5000, 'currency' => 'SAR', 'status' => 'pending',
            'provider_reference' => 'inv_stuck_test',
        ]);
        $transaction->forceFill(['created_at' => now()->subMinutes(45)])->save();

        return $transaction->fresh();
    }

    public function test_an_admin_can_view_the_stuck_payments_list(): void
    {
        $transaction = $this->stuckTransaction();

        $response = $this->actingAs($this->admin(), 'web')->get(route('admin.payment-transactions.stuck'));

        $response->assertOk();
        $response->assertSee($transaction->order->order_number);
    }

    public function test_a_non_admin_cannot_view_the_stuck_payments_list(): void
    {
        $customer = User::factory()->create(['is_admin' => false, 'is_active' => true]);

        $response = $this->actingAs($customer, 'web')->get(route('admin.payment-transactions.stuck'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_the_check_now_button_runs_reconciliation_and_reports_the_result(): void
    {
        Http::fake(['api.moyasar.com/*' => Http::response(['status' => 'paid', 'amount' => 5000, 'currency' => 'SAR'], 200)]);
        $transaction = $this->stuckTransaction();

        $response = $this->actingAs($this->admin(), 'web')->post(route('admin.payment-transactions.reconcile'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame('paid', $transaction->fresh()->status);
    }

    public function test_saving_payment_settings_rejects_a_stuck_threshold_below_five_minutes(): void
    {
        $response = $this->actingAs($this->admin(), 'web')->put(route('admin.settings.payment-methods.settings'), [
            'max_attempts_per_order' => 5,
            'stuck_after_minutes' => 4,
        ]);

        $response->assertSessionHasErrors('stuck_after_minutes');
        $this->assertNotSame('4', app(Settings::class)->get('payments_stuck_after_minutes'));
    }

    public function test_saving_payment_settings_accepts_a_valid_threshold(): void
    {
        $response = $this->actingAs($this->admin(), 'web')->put(route('admin.settings.payment-methods.settings'), [
            'max_attempts_per_order' => 3,
            'stuck_after_minutes' => 15,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('15', app(Settings::class)->get('payments_stuck_after_minutes'));
        $this->assertSame('3', app(Settings::class)->get('payments_max_attempts_per_order'));
    }
}
