<?php

namespace Tests\Feature\Security;

use App\Models\EmailLog;
use App\Services\Mailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test: Mailer::replace() was a raw str_replace() with no
 * escaping, rendered via {!! $body !!} in the shared email layout.
 * Reservation name/phone/notes and checkout name/phone/address — all
 * unauthenticated free text — went in raw, letting a malicious field turn
 * into a live link (or worse) inside a real, legitimately-sent email.
 *
 * The fix escapes every var by default at the replace() boundary, with two
 * deliberate exceptions (order_items, and contact's already-escaped
 * message) — this file proves both the escaping and that those two
 * exceptions still render as intended, not as escaped tag soup.
 */
class OutboundEmailEscapingTest extends TestCase
{
    use RefreshDatabase;

    protected const PAYLOAD = '<a href="http://evil.example">Verify your booking</a>';

    public function test_a_malicious_reservation_field_is_escaped_in_the_outbound_email(): void
    {
        config(['notifications.on_reservation' => true]);

        $this->post(route('reservations.store'), [
            'name' => self::PAYLOAD,
            'phone' => '0500000000',
            'email' => 'guest@example.com',
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '19:00',
            'guests' => 2,
            'notes' => '',
        ]);

        $log = EmailLog::where('to_email', 'guest@example.com')->latest()->first();

        $this->assertNotNull($log);
        $this->assertStringNotContainsString('<a href="http://evil.example">', $log->body, 'The link must not render as live HTML.');
        $this->assertStringContainsString(e(self::PAYLOAD), $log->body, 'The value must still be visible, just escaped.');
    }

    public function test_order_items_html_list_is_not_double_escaped_while_the_address_field_still_is(): void
    {
        $mailer = app(Mailer::class);

        $mailer->sendTemplate('order_placed', 'customer@example.com', 'Test Customer', [
            'name' => 'Test Customer',
            'order_number' => 'ORD-TEST01',
            'total' => '50.00',
            'currency' => 'AED',
            'order_type' => 'Delivery',
            'payment_method' => 'Cash on delivery',
            'address' => self::PAYLOAD,
            'order_items' => '<ul><li>1 &times; Mandi &mdash; AED 50.00</li></ul>',
        ], rawKeys: ['order_items']);

        $log = EmailLog::where('to_email', 'customer@example.com')->latest()->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('<ul><li>1 &times; Mandi', $log->body, 'order_items must render as a real list, not escaped text.');
        $this->assertStringNotContainsString('<a href="http://evil.example">', $log->body, 'The address field must still be escaped — this is not a blanket "trust the caller" exemption.');
    }

    public function test_the_contact_forms_pre_escaped_message_is_not_double_escaped(): void
    {
        config(['notifications.admin_email' => 'admin@example.com']);

        $this->post(route('contact.store'), [
            'name' => 'Test Sender',
            'email' => 'sender@example.com',
            'phone' => '0500000000',
            'message' => "Line one\nLine two",
        ]);

        $log = EmailLog::where('to_email', 'admin@example.com')->latest()->first();

        $this->assertNotNull($log);
        // nl2br() must survive as a real <br>, not become "&lt;br /&gt;".
        $this->assertStringContainsString('<br', $log->body, 'The line break must still render, not have been escaped a second time.');
        $this->assertStringContainsString('Line one', $log->body);
        $this->assertStringContainsString('Line two', $log->body);
    }

    public function test_a_malicious_contact_name_is_still_escaped_even_though_message_is_exempt(): void
    {
        config(['notifications.admin_email' => 'admin@example.com']);

        $this->post(route('contact.store'), [
            'name' => self::PAYLOAD,
            'email' => 'sender2@example.com',
            'phone' => '0500000000',
            'message' => 'Hello',
        ]);

        $log = EmailLog::where('to_email', 'admin@example.com')->latest()->first();

        $this->assertNotNull($log);
        $this->assertStringNotContainsString('<a href="http://evil.example">', $log->body, 'Marking message as raw must not exempt the other fields.');
    }
}
