<?php

namespace Tests\Feature\Billing;

use App\Models\User;
use App\Services\Billing\DocumentIssuer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class DocumentPrintTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regression test: the print view's __('documents.*') calls originally
     * resolved against the app's own locale (English, since neither the
     * admin panel nor the storefront is localised — Section B), not the
     * document's Arabic content, so every bilingual label rendered as
     * "English / English" instead of "Arabic / English". Caught by actually
     * screenshotting the rendered page rather than just asserting HTTP 200.
     */
    public function test_print_view_renders_arabic_labels_regardless_of_the_apps_own_locale(): void
    {
        $this->assertSame('en', config('app.locale'), 'The app locale stays English — the print view must force Arabic itself, not rely on it.');

        $order = \Database\Factories\OrderFactory::new()->create(['subtotal' => 100, 'delivery_fee' => 0, 'tax' => 0, 'total' => 100]);
        \Database\Factories\OrderItemFactory::new()->create(['order_id' => $order->id, 'price' => 100, 'quantity' => 1, 'line_total' => 100]);

        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $document = app(DocumentIssuer::class)->createDraftFromOrder($order, 'simplified_tax_invoice');
        $document = app(DocumentIssuer::class)->issue($document, $admin);

        $response = $this->actingAs($admin)->get(route('admin.billing.print', $document));

        $response->assertOk();
        $response->assertSee(__('documents.grand_total', [], 'ar'));
        $response->assertSee(__('documents.description', [], 'ar'));
        $response->assertDontSeeText(__('documents.grand_total', [], 'ar').' / '.__('documents.grand_total', [], 'ar'), false);
    }

    public function test_the_forced_arabic_locale_does_not_leak_past_the_print_response(): void
    {
        $order = \Database\Factories\OrderFactory::new()->create(['subtotal' => 100, 'delivery_fee' => 0, 'tax' => 0, 'total' => 100]);
        \Database\Factories\OrderItemFactory::new()->create(['order_id' => $order->id, 'price' => 100, 'quantity' => 1, 'line_total' => 100]);

        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $document = app(DocumentIssuer::class)->createDraftFromOrder($order, 'simplified_tax_invoice');
        $document = app(DocumentIssuer::class)->issue($document, $admin);

        $this->actingAs($admin)->get(route('admin.billing.print', $document));

        $this->assertSame('en', App::getLocale(), 'Rendering the print view must not leave the app locale switched to Arabic for whatever runs next.');
    }

    public function test_the_print_view_renders_for_both_order_linked_tax_invoice_types(): void
    {
        foreach (['simplified_tax_invoice', 'standard_tax_invoice'] as $type) {
            $order = \Database\Factories\OrderFactory::new()->create(['subtotal' => 100, 'delivery_fee' => 0, 'tax' => 0, 'total' => 100]);
            \Database\Factories\OrderItemFactory::new()->create(['order_id' => $order->id, 'price' => 100, 'quantity' => 1, 'line_total' => 100]);

            $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
            $document = app(DocumentIssuer::class)->createDraftFromOrder($order, $type, [
                'name_en' => 'B2B Buyer',
                'vat_number' => '300000000000003',
            ]);
            $document = app(DocumentIssuer::class)->issue($document, $admin);

            $response = $this->actingAs($admin)->get(route('admin.billing.print', $document));
            $response->assertOk();
        }
    }

    public function test_a_draft_cannot_be_printed(): void
    {
        $order = \Database\Factories\OrderFactory::new()->create(['subtotal' => 100, 'delivery_fee' => 0, 'tax' => 0, 'total' => 100]);
        \Database\Factories\OrderItemFactory::new()->create(['order_id' => $order->id, 'price' => 100, 'quantity' => 1, 'line_total' => 100]);

        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $document = app(DocumentIssuer::class)->createDraftFromOrder($order, 'simplified_tax_invoice');

        $response = $this->actingAs($admin)->get(route('admin.billing.print', $document));

        $response->assertRedirect(route('admin.billing.show', $document));
    }
}
