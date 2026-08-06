<?php

namespace Tests\Feature\Billing;

use App\Models\BillingDocument;
use App\Models\Order;
use App\Models\User;
use App\Services\Billing\CreditNoteIssuer;
use App\Services\Billing\DocumentIssuer;
use Database\Factories\OrderFactory;
use Database\Factories\OrderItemFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test: neither createFromOrder() nor storeFromOrder() checked
 * whether an order already had an issued tax invoice — a double-click, or a
 * second deliberate attempt weeks later, created a duplicate legal document.
 * See the full-project audit ("no guard against issuing a second tax
 * invoice for the same order").
 */
class DuplicateTaxInvoiceGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_active' => true]);
    }

    /** @return array{0: Order, 1: BillingDocument} */
    protected function orderWithIssuedTaxInvoice(): array
    {
        $order = OrderFactory::new()->create(['subtotal' => 100, 'delivery_fee' => 0, 'tax' => 0, 'total' => 100]);
        OrderItemFactory::new()->create(['order_id' => $order->id, 'price' => 100, 'quantity' => 1, 'line_total' => 100]);

        $document = app(DocumentIssuer::class)->createDraftFromOrder($order, 'simplified_tax_invoice');
        $document = app(DocumentIssuer::class)->issue($document, $this->admin());

        return [$order, $document];
    }

    public function test_the_create_screen_disables_both_tax_invoice_options_once_one_is_issued(): void
    {
        [$order, $document] = $this->orderWithIssuedTaxInvoice();

        $response = $this->actingAs($this->admin(), 'web')->get(route('admin.billing.from-order.create', $order));

        $response->assertOk();
        $response->assertSee('disabled', false);
        $response->assertSee($document->document_number);
    }

    public function test_the_create_screen_still_offers_quotation_and_proforma(): void
    {
        [$order] = $this->orderWithIssuedTaxInvoice();

        $response = $this->actingAs($this->admin(), 'web')->get(route('admin.billing.from-order.create', $order));

        $response->assertOk();
        $response->assertSee('value="quotation"', false);
        $response->assertSee('value="proforma"', false);
        $response->assertDontSee('disabled="disabled" name="document_type" value="quotation"', false);
    }

    public function test_a_direct_request_to_store_a_second_simplified_tax_invoice_is_rejected(): void
    {
        [$order, $existing] = $this->orderWithIssuedTaxInvoice();

        $response = $this->actingAs($this->admin(), 'web')->post(route('admin.billing.from-order.store', $order), [
            'document_type' => 'simplified_tax_invoice',
        ]);

        $response->assertRedirect(route('admin.billing.show', $existing));
        $response->assertSessionHas('error');
        $this->assertSame(1, BillingDocument::where('order_id', $order->id)->count());
    }

    public function test_a_direct_request_to_store_a_standard_tax_invoice_is_also_rejected_when_a_simplified_one_exists(): void
    {
        [$order, $existing] = $this->orderWithIssuedTaxInvoice();

        $response = $this->actingAs($this->admin(), 'web')->post(route('admin.billing.from-order.store', $order), [
            'document_type' => 'standard_tax_invoice',
            'buyer_name_en' => 'Some Business',
            'buyer_vat_number' => '300000000000003',
        ]);

        $response->assertRedirect(route('admin.billing.show', $existing));
        $response->assertSessionHas('error');
    }

    public function test_a_quotation_can_still_be_created_for_an_order_that_already_has_a_tax_invoice(): void
    {
        [$order] = $this->orderWithIssuedTaxInvoice();

        $response = $this->actingAs($this->admin(), 'web')->post(route('admin.billing.from-order.store', $order), [
            'document_type' => 'quotation',
            'buyer_name_en' => 'Some Customer',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertSame(2, BillingDocument::where('order_id', $order->id)->count());
    }

    public function test_the_guard_stays_in_place_even_after_the_original_is_fully_credited(): void
    {
        [$order, $document] = $this->orderWithIssuedTaxInvoice();

        app(CreditNoteIssuer::class)->issue(
            $document,
            $this->admin(),
            'Full credit — refunding the whole order for this test.',
            [['source_line_id' => $document->fresh()->lines->first()->id, 'quantity' => '1']],
        );

        $this->assertSame(BillingDocument::STATUS_FULLY_CREDITED, $document->fresh()->status);

        $response = $this->actingAs($this->admin(), 'web')->post(route('admin.billing.from-order.store', $order), [
            'document_type' => 'simplified_tax_invoice',
        ]);

        $response->assertSessionHas('error');
        $this->assertSame(1, BillingDocument::where('order_id', $order->id)->where('document_type', 'simplified_tax_invoice')->count());
    }
}
