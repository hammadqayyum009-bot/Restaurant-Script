<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regression test: payment_transactions.order_id is restrictOnDelete()
 * (Payments Phase 1), but OrderController::destroy() predates that
 * constraint and never learned about it — any order with a transaction
 * (even a still-pending Cash on Delivery one) threw an uncaught
 * QueryException instead of a friendly message. See the full-project audit.
 */
class OrderDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_active' => true]);
    }

    protected function makeOrder(): Order
    {
        return Order::create([
            'order_number' => 'ORD-'.strtoupper(Str::random(8)),
            'customer_name' => 'Test Customer', 'phone' => '0500000000', 'address' => 'x',
            'order_type' => 'delivery', 'subtotal' => 50, 'delivery_fee' => 0, 'tax' => 0,
            'total' => 50, 'payment_method' => 'cod', 'status' => 'confirmed',
        ]);
    }

    public function test_an_order_with_a_payment_transaction_cannot_be_deleted(): void
    {
        $order = $this->makeOrder();
        $method = PaymentMethod::factory()->create(['driver' => 'cod', 'enabled' => true]);
        PaymentTransaction::create([
            'order_id' => $order->id, 'payment_method_id' => $method->id, 'driver' => 'cod',
            'amount_minor' => 5000, 'currency' => 'SAR', 'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin(), 'web')->delete(route('admin.orders.destroy', $order));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNotNull($order->fresh(), 'The order must still exist — deletion was refused, not silently ignored.');
    }

    public function test_an_order_with_no_payment_transaction_still_deletes_normally(): void
    {
        $order = $this->makeOrder();

        $response = $this->actingAs($this->admin(), 'web')->delete(route('admin.orders.destroy', $order));

        $response->assertRedirect(route('admin.orders.index'));
        $response->assertSessionHas('success');
        $this->assertNull(Order::find($order->id));
    }

    public function test_the_order_show_page_hides_the_delete_button_once_a_transaction_exists(): void
    {
        $order = $this->makeOrder();
        $method = PaymentMethod::factory()->create(['driver' => 'cod', 'enabled' => true]);
        PaymentTransaction::create([
            'order_id' => $order->id, 'payment_method_id' => $method->id, 'driver' => 'cod',
            'amount_minor' => 5000, 'currency' => 'SAR', 'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin(), 'web')->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertDontSee('Delete order');
    }
}
