<?php

namespace Tests\Feature\Payments\Tap;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TapWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function tapMethod(): PaymentMethod
    {
        return PaymentMethod::factory()->create([
            'driver' => 'tap', 'enabled' => true, 'test_mode' => true,
            'credentials' => ['secret_key_test' => 'sk_test_fake'],
        ]);
    }

    /**
     * Hashstring verification is a deliberate, documented open item (see
     * documentation/payments-known-limitations.md) — every delivery is
     * rejected right now. This is what "fails closed" looks like: stored,
     * never processed, never a 2xx.
     */
    public function test_a_webhook_is_stored_but_rejected_since_hashstring_verification_is_not_implemented(): void
    {
        $this->tapMethod();

        $response = $this->postJson('/payments/tap/webhook', ['id' => 'evt_1', 'status' => 'CAPTURED']);

        $response->assertStatus(503);

        $event = PaymentWebhookEvent::where('provider_event_id', 'evt_1')->first();
        $this->assertNotNull($event);
        $this->assertFalse($event->signature_valid);
        $this->assertFalse($event->processed);
    }

    public function test_a_duplicate_webhook_with_the_same_event_id_is_stored_exactly_once(): void
    {
        $this->tapMethod();

        $this->postJson('/payments/tap/webhook', ['id' => 'evt_dup', 'status' => 'CAPTURED']);
        $this->postJson('/payments/tap/webhook', ['id' => 'evt_dup', 'status' => 'CAPTURED']);

        $this->assertSame(1, PaymentWebhookEvent::where('provider_event_id', 'evt_dup')->count());
    }

    public function test_a_webhook_for_a_transaction_that_does_not_exist_yet_is_stored_without_crashing(): void
    {
        $this->tapMethod();

        $response = $this->postJson('/payments/tap/webhook', ['id' => 'evt_unknown_ref', 'data' => ['id' => 'chg_never_created']]);

        $response->assertStatus(503);
        $event = PaymentWebhookEvent::where('provider_event_id', 'evt_unknown_ref')->first();
        $this->assertNotNull($event);
        $this->assertNull($event->payment_transaction_id);
    }

    public function test_a_webhook_is_correlated_to_its_transaction_when_the_reference_is_recognized(): void
    {
        $method = $this->tapMethod();
        $order = Order::create([
            'order_number' => 'ORD-'.strtoupper(Str::random(8)), 'customer_name' => 'Test', 'phone' => '0500000000',
            'address' => 'x', 'order_type' => 'delivery', 'subtotal' => 50, 'delivery_fee' => 0, 'tax' => 0,
            'total' => 50, 'payment_method' => 'cash', 'status' => 'pending',
        ]);
        $transaction = PaymentTransaction::create([
            'order_id' => $order->id, 'payment_method_id' => $method->id, 'driver' => 'tap',
            'amount_minor' => 5000, 'currency' => 'SAR', 'status' => 'pending',
            'provider_reference' => 'chg_known',
        ]);

        $this->postJson('/payments/tap/webhook', ['id' => 'evt_known', 'data' => ['id' => 'chg_known']]);

        $event = PaymentWebhookEvent::where('provider_event_id', 'evt_known')->first();
        $this->assertSame($transaction->id, $event->payment_transaction_id);
    }

    public function test_the_raw_payload_is_stored_before_anything_else(): void
    {
        $this->tapMethod();

        $this->postJson('/payments/tap/webhook', ['id' => 'evt_raw', 'foo' => 'bar']);

        $event = PaymentWebhookEvent::where('provider_event_id', 'evt_raw')->first();
        $this->assertStringContainsString('"foo":"bar"', $event->payload);
    }

    public function test_a_webhook_with_no_extractable_id_falls_back_to_a_body_hash_so_the_column_is_never_empty(): void
    {
        $this->tapMethod();

        $response = $this->call('POST', '/payments/tap/webhook', [], [], [], [], '{"no_id_field":true}');

        $response->assertStatus(503);
        $this->assertSame(1, PaymentWebhookEvent::count());
        $this->assertNotEmpty(PaymentWebhookEvent::first()->provider_event_id);
    }
}
