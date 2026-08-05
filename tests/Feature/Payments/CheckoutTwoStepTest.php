<?php

namespace Tests\Feature\Payments;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Phase 4: checkout is two steps — CheckoutController::store() only ever
 * creates the Order, and CheckoutPaymentController is where a payment method
 * is chosen and initiated. Every screen in between is behind a signed URL,
 * the same IDOR protection already established for the reservation
 * confirmation link.
 */
class CheckoutTwoStepTest extends TestCase
{
    use RefreshDatabase;

    protected function seedCartAndCheckoutPayload(): array
    {
        $category = MenuCategory::create(['name' => 'Mains', 'slug' => 'mains']);
        $item = MenuItem::create([
            'menu_category_id' => $category->id,
            'name' => 'Machboos', 'slug' => 'machboos', 'price' => 45,
        ]);

        session(['cart' => [
            (string) $item->id => ['id' => $item->id, 'name' => $item->name, 'price' => 45.0, 'image' => null, 'quantity' => 1],
        ]]);

        return [
            'customer_name' => 'Test Customer',
            'phone' => '0500000000',
            'email' => 'customer@example.com',
            'address' => 'Test address',
            'order_type' => 'delivery',
        ];
    }

    public function test_store_creates_the_order_without_deciding_a_payment_method(): void
    {
        $payload = $this->seedCartAndCheckoutPayload();
        PaymentMethod::factory()->create(['driver' => 'cod', 'enabled' => true]);

        $response = $this->post(route('checkout.store'), $payload);

        $order = Order::first();
        $this->assertNotNull($order);
        // The DB default applies transiently — no explicit decision has
        // been made yet, which is the point: the next screen decides it.
        $this->assertSame('cash', $order->payment_method);
        $this->assertSame('pending', $order->status);
    }

    public function test_store_redirects_to_a_signed_payment_method_screen(): void
    {
        PaymentMethod::factory()->create(['driver' => 'cod', 'enabled' => true]);
        $payload = $this->seedCartAndCheckoutPayload();

        $response = $this->post(route('checkout.store'), $payload);

        $order = Order::first();
        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('/checkout/'.$order->id.'/payment', $location);
        $this->assertStringContainsString('signature=', $location);

        $this->get($location)->assertOk();
    }

    public function test_the_bare_unsigned_payment_screen_is_rejected(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-TEST0001', 'customer_name' => 'X', 'phone' => '0500000000',
            'address' => 'x', 'order_type' => 'delivery', 'subtotal' => 50, 'delivery_fee' => 0,
            'tax' => 0, 'total' => 50, 'payment_method' => 'cash', 'status' => 'pending',
        ]);

        $response = $this->get('/checkout/'.$order->id.'/payment');

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error');
    }

    public function test_swapping_the_order_id_in_a_valid_signed_url_does_not_grant_access_to_a_different_order(): void
    {
        $mine = Order::create([
            'order_number' => 'ORD-MINE0001', 'customer_name' => 'A', 'phone' => '0500000000',
            'address' => 'x', 'order_type' => 'delivery', 'subtotal' => 50, 'delivery_fee' => 0,
            'tax' => 0, 'total' => 50, 'payment_method' => 'cash', 'status' => 'pending',
        ]);
        $someoneElses = Order::create([
            'order_number' => 'ORD-THEIRS01', 'customer_name' => 'B', 'phone' => '0500000000',
            'address' => 'x', 'order_type' => 'delivery', 'subtotal' => 50, 'delivery_fee' => 0,
            'tax' => 0, 'total' => 50, 'payment_method' => 'cash', 'status' => 'pending',
        ]);

        $myUrl = URL::temporarySignedRoute('checkout.payment.show', now()->addHours(6), ['order' => $mine->id]);
        $tampered = str_replace('/checkout/'.$mine->id.'/payment', '/checkout/'.$someoneElses->id.'/payment', $myUrl);

        $response = $this->get($tampered);

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error');
    }

    protected function orderWithMethods(): Order
    {
        PaymentMethod::factory()->create(['driver' => 'cod', 'enabled' => true]);

        return Order::create([
            'order_number' => 'ORD-'.uniqid(), 'customer_name' => 'Test', 'phone' => '0500000000',
            'address' => 'x', 'order_type' => 'delivery', 'subtotal' => 50, 'delivery_fee' => 0,
            'tax' => 0, 'total' => 50, 'payment_method' => 'cash', 'status' => 'pending',
        ]);
    }

    public function test_initiating_cash_on_delivery_redirects_straight_to_the_result_screen(): void
    {
        $order = $this->orderWithMethods();
        $codMethod = PaymentMethod::where('driver', 'cod')->first();

        $url = URL::temporarySignedRoute('checkout.payment.show', now()->addHours(6), ['order' => $order->id]);
        $response = $this->post($url, ['payment_method_id' => $codMethod->id]);

        $response->assertRedirect();
        $this->assertStringContainsString('/checkout/'.$order->id.'/result', $response->headers->get('Location'));
        $this->assertSame('cod', $order->fresh()->payment_method);
        $this->assertSame('confirmed', $order->fresh()->status);
    }

    public function test_initiating_an_online_gateway_renders_the_redirecting_screen_with_the_provider_url(): void
    {
        Http::fake([
            'api.moyasar.com/*' => Http::response(['id' => 'inv_1', 'url' => 'https://moyasar.example/pay/inv_1'], 200),
        ]);

        $order = $this->orderWithMethods();
        $moyasar = PaymentMethod::factory()->create([
            'driver' => 'moyasar', 'enabled' => true, 'test_mode' => true,
            'credentials' => ['secret_key_test' => 'sk_test_fake'],
        ]);

        $url = URL::temporarySignedRoute('checkout.payment.show', now()->addHours(6), ['order' => $order->id]);
        $response = $this->post($url, ['payment_method_id' => $moyasar->id]);

        $response->assertOk();
        $response->assertSee('https://moyasar.example/pay/inv_1', false);
        $this->assertSame('moyasar', $order->fresh()->payment_method);
        $this->assertSame(1, $order->fresh()->paymentTransactions()->count());
    }

    public function test_a_payment_method_id_not_eligible_for_this_order_is_rejected(): void
    {
        $order = $this->orderWithMethods();

        $url = URL::temporarySignedRoute('checkout.payment.show', now()->addHours(6), ['order' => $order->id]);
        $response = $this->post($url, ['payment_method_id' => 999999]);

        $response->assertSessionHas('error');
        $this->assertSame(0, $order->paymentTransactions()->count());
    }

    public function test_the_retry_cap_blocks_further_attempts_once_reached(): void
    {
        config(['payments.max_attempts_per_order' => 2]);

        $order = $this->orderWithMethods();
        $codMethod = PaymentMethod::where('driver', 'cod')->first();
        $url = URL::temporarySignedRoute('checkout.payment.show', now()->addHours(6), ['order' => $order->id]);

        $this->post($url, ['payment_method_id' => $codMethod->id]);
        $this->post($url, ['payment_method_id' => $codMethod->id]);
        $this->assertSame(2, $order->paymentTransactions()->count());

        $response = $this->post($url, ['payment_method_id' => $codMethod->id]);

        $response->assertSessionHas('error');
        $this->assertSame(2, $order->paymentTransactions()->count(), 'A third attempt must be blocked once the cap is reached.');
    }

    public function test_the_payment_initiation_route_is_throttled_by_ip(): void
    {
        $order = $this->orderWithMethods();
        $url = URL::temporarySignedRoute('checkout.payment.show', now()->addHours(6), ['order' => $order->id]);

        $last = null;
        for ($i = 0; $i < 11; $i++) {
            // An invalid method id every time — fails validation without
            // ever consuming a retry-cap slot, isolating the throttle
            // behaviour from the cap behaviour tested above.
            $last = $this->post($url, ['payment_method_id' => 999999]);
        }

        $last->assertStatus(429);
    }
}
