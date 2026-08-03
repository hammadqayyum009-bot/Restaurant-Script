<?php

namespace Tests\Feature\Billing;

use App\Models\BillingDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Basic coverage for the create/store/edit/update routes this batch adds —
 * not in the assigned 61-78 list, but new route surface with no prior
 * coverage at all would be an obvious gap.
 */
class StandaloneDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_active' => true]);
    }

    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'document_type' => 'quotation',
            'currency' => 'SAR',
            'buyer_name_en' => 'Catering Client',
            'lines' => [
                ['name_en' => 'Setup fee', 'quantity' => '1', 'unit_price' => '500.00', 'vat_category' => 'S'],
            ],
        ], $overrides);
    }

    public function test_the_create_form_loads(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.billing.create'));

        $response->assertOk();
        $response->assertSee('Quotation');
        $response->assertSee('Proforma Invoice');
        $response->assertSee('Delivery Note');
    }

    public function test_storing_creates_a_draft_with_lines(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.billing.store'), $this->validPayload());

        $document = BillingDocument::first();
        $response->assertRedirect(route('admin.billing.show', $document));

        $this->assertTrue($document->isDraft());
        $this->assertNull($document->order_id);
        $this->assertSame('quotation', $document->document_type);
        $this->assertSame(1, $document->lines->count());
        $this->assertSame('Setup fee', $document->lines->first()->name_en);
        // Not yet computed — that only happens at issue time.
        $this->assertSame(0, $document->lines->first()->line_total_minor);
    }

    public function test_storing_without_any_lines_is_rejected(): void
    {
        $response = $this->actingAs($this->admin())
            ->post(route('admin.billing.store'), $this->validPayload(['lines' => []]));

        $response->assertSessionHasErrors('lines');
        $this->assertSame(0, BillingDocument::count());
    }

    public function test_a_tax_invoice_type_is_rejected_for_standalone_creation(): void
    {
        $response = $this->actingAs($this->admin())
            ->post(route('admin.billing.store'), $this->validPayload(['document_type' => 'simplified_tax_invoice']));

        $response->assertSessionHasErrors('document_type');
    }

    public function test_the_edit_form_shows_existing_lines(): void
    {
        $this->actingAs($this->admin())->post(route('admin.billing.store'), $this->validPayload());
        $document = BillingDocument::first();

        $response = $this->actingAs($this->admin())->get(route('admin.billing.edit', $document));

        $response->assertOk();
        $response->assertSee('Setup fee');
        $response->assertSee('500', false);
    }

    public function test_an_order_linked_draft_redirects_away_from_edit(): void
    {
        $order = \Database\Factories\OrderFactory::new()->create(['subtotal' => 50, 'delivery_fee' => 0, 'tax' => 0, 'total' => 50]);
        \Database\Factories\OrderItemFactory::new()->create(['order_id' => $order->id, 'price' => 50, 'quantity' => 1, 'line_total' => 50]);

        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.billing.from-order.store', $order), ['document_type' => 'simplified_tax_invoice']);
        $document = BillingDocument::first();

        $response = $this->actingAs($admin)->get(route('admin.billing.edit', $document));

        $response->assertRedirect(route('admin.billing.show', $document));
    }

    public function test_updating_replaces_the_line_items_wholesale(): void
    {
        $this->actingAs($this->admin())->post(route('admin.billing.store'), $this->validPayload());
        $document = BillingDocument::first();

        $payload = $this->validPayload([
            'lines' => [
                ['name_en' => 'New line A', 'quantity' => '2', 'unit_price' => '10.00', 'vat_category' => 'S'],
                ['name_en' => 'New line B', 'quantity' => '1', 'unit_price' => '5.00', 'vat_category' => 'Z'],
            ],
        ]);
        unset($payload['document_type']); // not part of the update payload

        $response = $this->actingAs($this->admin())->put(route('admin.billing.update', $document), $payload);

        $response->assertRedirect();
        $document->refresh();
        $this->assertSame(2, $document->lines->count());
        $this->assertSame('New line A', $document->lines->first()->name_en);
    }

    public function test_a_standalone_draft_can_be_issued_end_to_end(): void
    {
        $this->actingAs($this->admin())->post(route('admin.billing.store'), $this->validPayload());
        $document = BillingDocument::first();

        $response = $this->actingAs($this->admin())->post(route('admin.billing.issue', $document));

        $response->assertRedirect(route('admin.billing.show', $document));
        $document->refresh();
        $this->assertTrue($document->isIssued());
        $this->assertStringStartsWith('QT-', $document->document_number);
        $this->assertSame(50000, $document->grand_total_minor);
    }

    public function test_a_guest_is_redirected_from_the_standalone_create_route(): void
    {
        $response = $this->get(route('admin.billing.create'));

        $response->assertRedirect(route('admin.login'));
    }
}
