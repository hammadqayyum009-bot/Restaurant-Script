<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regression test: Money::toMinor() throws InvalidArgumentException on a
 * decimal value with more precision than the currency allows, and neither
 * RefundPaymentRequest nor UpdatePaymentMethodRequest stopped an
 * over-precise value before it got there — an admin typing "10.999" for a
 * 2-decimal-place currency got a raw 500 instead of a field error. See the
 * full-project audit ("decimal-precision overflow crashes instead of
 * validating").
 *
 * assertStatus(302) is the "not a 500" check throughout — a genuine crash
 * would never make it to a redirect at all. A validation failure legitimately
 * raises (and Laravel catches) a ValidationException internally, so checking
 * $response->exception for null is the wrong test; the redirect + field
 * error together are what prove this was handled, not crashed.
 */
class DecimalPrecisionValidationTest extends TestCase
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
            'total' => 50, 'payment_method' => 'moyasar', 'status' => 'confirmed',
        ]);
        $method = PaymentMethod::factory()->create([
            'driver' => 'moyasar', 'enabled' => true, 'test_mode' => true,
            'credentials' => ['secret_key_test' => 'sk_test_fake'],
        ]);

        return PaymentTransaction::create([
            'order_id' => $order->id, 'payment_method_id' => $method->id, 'driver' => 'moyasar',
            'amount_minor' => 5000, 'currency' => 'SAR', 'status' => 'paid',
            'provider_reference' => 'inv_precision_test',
        ]);
    }

    public function test_a_refund_amount_with_too_many_decimals_is_rejected_cleanly_not_with_a_500(): void
    {
        $transaction = $this->paidTransaction();

        $response = $this->actingAs($this->admin(), 'web')->post(route('admin.payment-transactions.refund', $transaction), [
            'amount' => '10.999',
            'reason' => 'testing decimal precision overflow guard',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('amount');
        $this->assertSame(0, $transaction->fresh()->refunded_amount_minor);
    }

    public function test_a_refund_amount_with_valid_precision_is_accepted(): void
    {
        Http::fake(['api.moyasar.com/v1/payments/inv_precision_test/refund' => Http::response(['id' => 'inv_precision_test'], 200)]);
        $transaction = $this->paidTransaction();

        $response = $this->actingAs($this->admin(), 'web')->post(route('admin.payment-transactions.refund', $transaction), [
            'amount' => '10.99',
            'reason' => 'testing a valid two decimal place refund amount',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(1099, $transaction->fresh()->refunded_amount_minor);
    }

    public function test_a_min_order_amount_with_too_many_decimals_is_rejected_cleanly_not_with_a_500(): void
    {
        config(['site.currency' => 'SAR']);
        $method = PaymentMethod::factory()->create(['driver' => 'cod']);

        $response = $this->actingAs($this->admin(), 'web')->put(route('admin.settings.payment-methods.update', $method), [
            'min_order_amount' => '10.999',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('min_order_amount');
        $this->assertNull($method->fresh()->min_order_amount_minor);
    }

    public function test_a_max_order_amount_with_too_many_decimals_is_rejected_cleanly_not_with_a_500(): void
    {
        config(['site.currency' => 'SAR']);
        $method = PaymentMethod::factory()->create(['driver' => 'cod']);

        $response = $this->actingAs($this->admin(), 'web')->put(route('admin.settings.payment-methods.update', $method), [
            'min_order_amount' => '1.00',
            'max_order_amount' => '500.999',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('max_order_amount');
    }

    public function test_min_and_max_order_amounts_with_valid_precision_are_accepted(): void
    {
        config(['site.currency' => 'SAR']);
        $method = PaymentMethod::factory()->create(['driver' => 'cod']);

        $response = $this->actingAs($this->admin(), 'web')->put(route('admin.settings.payment-methods.update', $method), [
            'min_order_amount' => '10.50',
            'max_order_amount' => '500.00',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(1050, $method->fresh()->min_order_amount_minor);
        $this->assertSame(50000, $method->fresh()->max_order_amount_minor);
    }

    public function test_a_three_decimal_place_currency_correctly_allows_three_decimals(): void
    {
        config(['site.currency' => 'KWD']);
        $method = PaymentMethod::factory()->create(['driver' => 'cod']);

        $response = $this->actingAs($this->admin(), 'web')->put(route('admin.settings.payment-methods.update', $method), [
            'min_order_amount' => '1.500',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(1500, $method->fresh()->min_order_amount_minor);
    }
}
