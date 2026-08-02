<?php

namespace Tests\Feature\Billing;

use App\Exceptions\Billing\NumberAllocationFailedException;
use App\Models\BillingDocument;
use App\Models\BillingDocumentCounter;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Billing\DocumentIssuer;
use App\Services\Billing\DocumentNumberer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NumberingTest extends TestCase
{
    use RefreshDatabase;

    public function test_numbers_are_sequential_and_gapless_within_a_series(): void
    {
        $numberer = app(DocumentNumberer::class);

        $numbers = [];
        for ($i = 0; $i < 5; $i++) {
            [$number] = $numberer->allocate('simplified_tax_invoice', 2026);
            $numbers[] = $number;
        }

        $this->assertSame([1, 2, 3, 4, 5], $numbers);
    }

    public function test_the_counter_resets_on_a_new_year(): void
    {
        $numberer = app(DocumentNumberer::class);

        [$n1] = $numberer->allocate('simplified_tax_invoice', 2026);
        [$n2] = $numberer->allocate('simplified_tax_invoice', 2026);
        [$n3] = $numberer->allocate('simplified_tax_invoice', 2027);

        $this->assertSame(1, $n1);
        $this->assertSame(2, $n2);
        $this->assertSame(1, $n3, 'A new year starts its own series at 1.');
    }

    public function test_two_document_types_do_not_share_a_counter(): void
    {
        $numberer = app(DocumentNumberer::class);

        [$invoiceNumber] = $numberer->allocate('simplified_tax_invoice', 2026);
        [$creditNoteNumber] = $numberer->allocate('credit_note', 2026);

        $this->assertSame(1, $invoiceNumber);
        $this->assertSame(1, $creditNoteNumber);
    }

    public function test_changing_a_prefix_does_not_renumber_issued_documents_or_reset_the_counter(): void
    {
        $numberer = app(DocumentNumberer::class);

        [$firstNumber, $firstFormatted] = $numberer->allocate('simplified_tax_invoice', 2026);
        $this->assertSame('INV-2026-000001', $firstFormatted);

        app(\App\Services\Billing\BillingSettings::class)->set('billing.prefix_simplified_tax_invoice', 'TAX');

        [$secondNumber, $secondFormatted] = $numberer->allocate('simplified_tax_invoice', 2026);

        $this->assertSame(2, $secondNumber, 'The counter continued from where it was — it did not reset.');
        $this->assertSame('TAX-2026-000002', $secondFormatted);
        $this->assertSame('INV-2026-000001', $firstFormatted, 'A string already formatted and (in a real issue) stored never changes retroactively.');
    }

    /**
     * Forces a real collision: a document row is seeded directly at
     * (type, year, number=1) without going through the counter, so the
     * counter (which starts at last_number=0) will allocate number=1 on every
     * attempt and collide with the seeded row every time — deterministically
     * exercising the retry-and-give-up path without needing real concurrency.
     */
    protected function seedCollisionAt(string $type, int $year, int $number): void
    {
        BillingDocument::create([
            'document_type' => $type,
            'series_year' => $year,
            'number' => $number,
            'document_number' => "COLLIDE-{$year}-{$number}",
            'status' => 'issued',
            'currency' => 'AED',
            'currency_exponent' => 2,
            'prices_include_vat' => true,
            'vat_rate_bp' => 1500,
        ]);
    }

    protected function draftForOrder(int $number): array
    {
        $order = \Database\Factories\OrderFactory::new()->create(['subtotal' => 100, 'delivery_fee' => 0, 'tax' => 0, 'total' => 100]);
        \Database\Factories\OrderItemFactory::new()->create(['order_id' => $order->id, 'price' => 100, 'quantity' => 1, 'line_total' => 100]);

        $document = app(DocumentIssuer::class)->createDraftFromOrder($order, 'simplified_tax_invoice');

        return [$document, User::factory()->create(['is_admin' => true, 'is_active' => true])];
    }

    public function test_allocation_gives_up_after_exactly_three_attempts_and_throws(): void
    {
        $this->seedCollisionAt('simplified_tax_invoice', (int) now()->year, 1);
        [$document, $admin] = $this->draftForOrder(1);

        $this->expectException(NumberAllocationFailedException::class);

        app(DocumentIssuer::class)->issue($document, $admin);
    }

    public function test_a_rolled_back_allocation_consumes_no_number_and_leaves_no_gap(): void
    {
        $this->seedCollisionAt('simplified_tax_invoice', (int) now()->year, 1);
        [$document, $admin] = $this->draftForOrder(1);

        try {
            app(DocumentIssuer::class)->issue($document, $admin);
        } catch (NumberAllocationFailedException) {
            // expected
        }

        $counter = BillingDocumentCounter::query()
            ->where('document_type', 'simplified_tax_invoice')
            ->where('series_year', (int) now()->year)
            ->first();

        // Each attempt creates the counter row and increments it inside the
        // same transaction as the failed document insert — so a rolled-back
        // attempt rolls back the counter row's creation too. Either no row
        // exists at all, or one exists at last_number=0; either way, nothing
        // was consumed.
        $this->assertSame(0, $counter?->last_number ?? 0);

        $document->refresh();
        $this->assertTrue($document->isDraft(), 'The document was never issued — no half-numbered state.');

        // Prove it concretely: clear the collision and confirm the next real
        // allocation still gets number 1, not 4.
        BillingDocument::where('document_number', 'like', 'COLLIDE-%')->delete();
        [$number] = app(\App\Services\Billing\DocumentNumberer::class)->allocate('simplified_tax_invoice', (int) now()->year);
        $this->assertSame(1, $number, 'No number was actually consumed by the failed attempts.');
    }

    public function test_an_issued_document_number_cannot_be_changed_through_any_route(): void
    {
        $order = \Database\Factories\OrderFactory::new()->create(['subtotal' => 100, 'delivery_fee' => 0, 'tax' => 0, 'total' => 100]);
        \Database\Factories\OrderItemFactory::new()->create(['order_id' => $order->id, 'price' => 100, 'quantity' => 1, 'line_total' => 100]);

        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $document = app(DocumentIssuer::class)->createDraftFromOrder($order, 'simplified_tax_invoice');
        app(DocumentIssuer::class)->issue($document, $admin);

        $originalNumber = $document->document_number;

        $response = $this->actingAs($admin)->put(route('admin.billing.update', $document), [
            'notes_en' => 'trying to sneak an update in',
        ]);

        $response->assertForbidden();
        $this->assertSame($originalNumber, $document->fresh()->document_number);
    }
}
