<?php

namespace Tests\Feature\Billing;

use App\Models\User;
use App\Services\Billing\BillingSettings;
use App\Services\Billing\DocumentIssuer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DocumentRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_active' => true]);
    }

    protected function orderDocument(string $type, array $buyer = []): \App\Models\BillingDocument
    {
        $order = \Database\Factories\OrderFactory::new()->create(['subtotal' => 100, 'delivery_fee' => 10, 'tax' => 0, 'total' => 110]);
        \Database\Factories\OrderItemFactory::new()->create(['order_id' => $order->id, 'price' => 100, 'quantity' => 1, 'line_total' => 100]);

        $document = app(DocumentIssuer::class)->createDraftFromOrder($order, $type, $buyer);

        return app(DocumentIssuer::class)->issue($document, $this->admin());
    }

    /** @return array<string, array{string}> */
    public static function availableDocumentTypes(): array
    {
        // credit_note is Batch 4 — not issuable yet, so not included here.
        return [
            'simplified tax invoice' => ['simplified_tax_invoice'],
            'standard tax invoice' => ['standard_tax_invoice'],
            'quotation' => ['quotation'],
            'proforma' => ['proforma'],
            'delivery note' => ['delivery_note'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('availableDocumentTypes')]
    public function test_the_print_view_renders_for_every_available_document_type_without_error(string $type): void
    {
        $buyer = $type === 'standard_tax_invoice' ? ['vat_number' => '300000000000003', 'name_en' => 'B2B Buyer'] : [];
        $document = $this->orderDocument($type, $buyer);

        $response = $this->actingAs($this->admin())->get(route('admin.billing.print', $document));

        $response->assertOk();
        $response->assertSee($document->document_number);
    }

    public function test_only_tax_invoices_carry_a_qr_code_the_others_do_not(): void
    {
        $taxInvoice = $this->orderDocument('simplified_tax_invoice');
        $quotation = $this->orderDocument('quotation');

        $this->assertNotNull($taxInvoice->qr_payload);
        $this->assertNull($quotation->qr_payload);

        $taxResponse = $this->actingAs($this->admin())->get(route('admin.billing.print', $taxInvoice));
        $quoteResponse = $this->actingAs($this->admin())->get(route('admin.billing.print', $quotation));

        $taxResponse->assertSee('data:image/svg+xml;base64,', false);
        $quoteResponse->assertDontSee('data:image/svg+xml;base64,', false);
    }

    public function test_arabic_falls_back_to_english_when_the_arabic_value_is_empty_never_a_blank_cell(): void
    {
        app(BillingSettings::class)->set('billing.seller_name_ar', ''); // deliberately empty

        $document = $this->orderDocument('simplified_tax_invoice', ['name_en' => 'English Only Buyer']);
        // buyer_name_ar was never supplied, so it is null on this document.
        $this->assertNull($document->buyer_name_ar);

        $response = $this->actingAs($this->admin())->get(route('admin.billing.print', $document));

        $response->assertOk();
        // The English buyer name must appear (the Arabic-primary slot falls
        // back to it) rather than the block being blank.
        $response->assertSee('English Only Buyer');
    }

    public function test_every_user_facing_document_string_resolves_through_the_lang_files(): void
    {
        // Regression guard for the bug this batch found: every bilingual
        // label was "{{ __('documents.key') }} / LiteralEnglishWord" — the
        // English half hardcoded, not translated. This walks the actual
        // shipped files and fails if that pattern reappears.
        $files = array_merge(
            [base_path('resources/views/documents/print.blade.php')],
            collect(File::files(resource_path('views/documents/partials')))->map(fn ($f) => $f->getPathname())->all(),
        );

        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            // Every "__('documents.xxx') /" pairing must be followed by
            // another __(...) call, not a bare literal.
            $this->assertDoesNotMatchRegularExpression(
                '/__\(\'documents\.[a-z_]+\'\)\s*\}\}\s*\/\s*[A-Za-z][A-Za-z .%]*[a-z]</',
                $contents,
                basename($file).' has a hardcoded English literal after a translated label — should be another __(...) call.',
            );
        }
    }

    public function test_qr_payload_is_snapshotted_at_issue_and_the_print_view_never_recomputes_it(): void
    {
        $document = $this->orderDocument('simplified_tax_invoice');
        $originalPayload = $document->qr_payload;

        // Change everything the payload is built from.
        app(BillingSettings::class)->set('billing.seller_name_en', 'A Totally Different Company');
        app(BillingSettings::class)->set('billing.vat_number', '399999999999999');

        $response = $this->actingAs($this->admin())->get(route('admin.billing.print', $document));
        $response->assertOk();

        $document->refresh();
        $this->assertSame($originalPayload, $document->qr_payload, 'qr_payload is a stored column, never recomputed at render time.');
    }

    public function test_a_standalone_document_computes_its_own_totals_and_uses_the_default_currency(): void
    {
        app(BillingSettings::class)->set('billing.default_currency', 'SAR');

        $document = app(DocumentIssuer::class)->createDraftStandalone([
            'document_type' => 'quotation',
            'currency' => 'SAR',
            'buyer_name_en' => 'Catering Client',
            'lines' => [
                ['name_en' => 'Setup fee', 'quantity' => '1', 'unit_price' => '500.00', 'vat_category' => 'S'],
                ['name_en' => 'Per-guest catering', 'quantity' => '40', 'unit_price' => '25.00', 'vat_category' => 'S'],
            ],
        ]);

        $document = app(DocumentIssuer::class)->issue($document, $this->admin());

        $this->assertNull($document->order_id);
        $this->assertSame('SAR', $document->currency);
        // 500.00 + 40*25.00 = 1500.00 gross, inclusive default -> back-calculated VAT.
        $this->assertSame(150000, $document->grand_total_minor);
        $this->assertSame(2, $document->lines->count());
    }
}
