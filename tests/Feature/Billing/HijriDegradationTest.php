<?php

namespace Tests\Feature\Billing;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Billing\BillingSettings;
use App\Services\Billing\DocumentIssuer;
use App\Services\Billing\HijriDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Decision 3 (approved): if ext-intl is missing, the Hijri toggle disables
 * itself with no crash and no wrong date — the Gregorian date always prints.
 * This container has ext-intl installed, so HijriDate::forceAvailability()
 * simulates its absence deterministically rather than actually uninstalling
 * the extension.
 */
class HijriDegradationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        HijriDate::forceAvailability(null);
        parent::tearDown();
    }

    public function test_print_view_renders_without_the_hijri_date_and_without_error_when_ext_intl_is_absent(): void
    {
        HijriDate::forceAvailability(false);

        app(BillingSettings::class)->set('billing.show_hijri', '1');

        $order = \Database\Factories\OrderFactory::new()->create(['subtotal' => 100, 'delivery_fee' => 0, 'tax' => 0, 'total' => 100]);
        \Database\Factories\OrderItemFactory::new()->create(['order_id' => $order->id, 'price' => 100, 'quantity' => 1, 'line_total' => 100]);

        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $document = app(DocumentIssuer::class)->createDraftFromOrder($order, 'simplified_tax_invoice');
        $document = app(DocumentIssuer::class)->issue($document, $admin);

        $this->assertNull($document->hijri_date, 'showHijri() was on, but HijriDate::available() was forced false — no date should have been stored.');

        $response = $this->actingAs($admin)->get(route('admin.billing.print', $document));

        $response->assertOk();
        $response->assertDontSee(__('documents.hijri_date', [], 'ar'));
    }

    public function test_hijri_date_available_reports_false_when_forced_and_true_by_default(): void
    {
        $this->assertTrue(HijriDate::available(), 'This container really does have ext-intl.');

        HijriDate::forceAvailability(false);
        $this->assertFalse(HijriDate::available());
        $this->assertNull(HijriDate::for(now()));

        HijriDate::forceAvailability(null);
        $this->assertTrue(HijriDate::available());
    }
}
