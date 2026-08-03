<?php

namespace Tests\Feature\Billing;

use App\Exceptions\Billing\CreditLimitExceededException;
use App\Models\BillingDocument;
use App\Models\User;
use App\Services\Billing\CreditNoteIssuer;
use App\Services\Billing\DocumentIssuer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Batch 4 / Section G — tests 45-54 from the Stage 2 contract. Standalone
 * documents are used throughout (rather than order-linked ones) so every
 * fixture's totals are exact and self-contained: no Reconciler, no order
 * factory, just DocumentIssuer::createDraftStandalone() with round numbers.
 */
class CreditNoteTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_active' => true]);
    }

    protected function issuer(): DocumentIssuer
    {
        return app(DocumentIssuer::class);
    }

    protected function creditIssuer(): CreditNoteIssuer
    {
        return app(CreditNoteIssuer::class);
    }

    /**
     * @param  array<int, array{name: string, quantity: string, unit_price: string, vat_category?: string}>  $lines
     */
    protected function issuedStandaloneDocument(array $lines, User $actor): BillingDocument
    {
        $document = $this->issuer()->createDraftStandalone([
            'document_type' => 'quotation',
            'currency' => 'AED',
            'buyer_name_en' => 'Credit Note Test Buyer',
            'lines' => array_map(fn ($line) => [
                'name_en' => $line['name'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'vat_category' => $line['vat_category'] ?? 'S',
            ], $lines),
        ]);

        return $this->issuer()->issue($document, $actor);
    }

    public function test_a_partial_credit_note_modifies_no_column_on_the_original_except_status(): void
    {
        $admin = $this->admin();
        $original = $this->issuedStandaloneDocument([
            ['name' => 'Catering package', 'quantity' => '2', 'unit_price' => '57.50'],
        ], $admin);

        $before = $original->getAttributes();
        $line = $original->lines->first();

        $this->creditIssuer()->issue($original, $admin, 'Customer returned one unit', [
            ['source_line_id' => $line->id, 'quantity' => '1'],
        ]);

        $original->refresh();
        $after = $original->getAttributes();

        foreach ($before as $column => $value) {
            if (in_array($column, ['status', 'updated_at'], true)) {
                continue;
            }

            $this->assertSame($value, $after[$column], "Column \"{$column}\" changed on the original — only status may change.");
        }

        $this->assertSame(BillingDocument::STATUS_PARTIALLY_CREDITED, $original->status);
        $this->assertSame(1, $original->lines->count(), 'The original\'s own lines are untouched.');
    }

    public function test_over_crediting_by_quantity_on_a_single_line_is_rejected(): void
    {
        $admin = $this->admin();
        $original = $this->issuedStandaloneDocument([
            ['name' => 'Setup fee', 'quantity' => '1', 'unit_price' => '115.00'],
        ], $admin);
        $line = $original->lines->first();

        $this->expectException(CreditLimitExceededException::class);
        $this->expectExceptionMessage('remaining to credit');

        $this->creditIssuer()->issue($original, $admin, 'Attempting to over-credit by quantity', [
            ['source_line_id' => $line->id, 'quantity' => '2'],
        ]);
    }

    public function test_over_crediting_by_amount_is_rejected(): void
    {
        $admin = $this->admin();
        $original = $this->issuedStandaloneDocument([
            ['name' => 'Setup fee', 'quantity' => '1', 'unit_price' => '115.00'],
        ], $admin);
        $line = $original->lines->first();

        // Simulate the original's own recorded total being lower than what
        // crediting its full line quantity would recompute to (the same gap
        // a Reconciler rounding adjustment could leave on an order-linked
        // document). The quantity guard alone would wave this through —
        // requesting the line's exact original quantity never exceeds it —
        // so this specifically exercises the document-level amount guard.
        $original->update(['grand_total_minor' => $original->grand_total_minor - 500]);

        $this->expectException(CreditLimitExceededException::class);
        $this->expectExceptionMessage('exceeds the remaining creditable balance');

        $this->creditIssuer()->issue($original, $admin, 'Attempting to over-credit by amount', [
            ['source_line_id' => $line->id, 'quantity' => '1'],
        ]);
    }

    public function test_two_partial_credit_notes_that_together_exceed_the_original_are_rejected(): void
    {
        $admin = $this->admin();
        $original = $this->issuedStandaloneDocument([
            ['name' => 'Catering package', 'quantity' => '3', 'unit_price' => '50.00'],
        ], $admin);
        $line = $original->lines->first();

        // First note: credit 2 of 3 units — well within bounds.
        $this->creditIssuer()->issue($original, $admin, 'Partial return, batch one', [
            ['source_line_id' => $line->id, 'quantity' => '2'],
        ]);

        $original->refresh();
        $this->assertSame(BillingDocument::STATUS_PARTIALLY_CREDITED, $original->status);

        // Second note: only 1 unit remains, but this one asks for 2 again —
        // together the two notes would credit 4 units of a 3-unit line.
        $this->expectException(CreditLimitExceededException::class);

        $this->creditIssuer()->issue($original, $admin, 'Partial return, batch two', [
            ['source_line_id' => $line->id, 'quantity' => '2'],
        ]);
    }

    public function test_crediting_every_line_fully_sets_the_original_to_fully_credited(): void
    {
        $admin = $this->admin();
        $original = $this->issuedStandaloneDocument([
            ['name' => 'Setup fee', 'quantity' => '1', 'unit_price' => '115.00'],
            ['name' => 'Per-guest catering', 'quantity' => '2', 'unit_price' => '57.50'],
        ], $admin);

        $lines = $original->lines;

        $this->creditIssuer()->issue($original, $admin, 'Event cancelled, full refund', [
            ['source_line_id' => $lines[0]->id, 'quantity' => '1'],
            ['source_line_id' => $lines[1]->id, 'quantity' => '2'],
        ]);

        $original->refresh();
        $this->assertSame(BillingDocument::STATUS_FULLY_CREDITED, $original->status);
    }

    public function test_a_partial_credit_sets_the_original_to_partially_credited(): void
    {
        $admin = $this->admin();
        $original = $this->issuedStandaloneDocument([
            ['name' => 'Setup fee', 'quantity' => '1', 'unit_price' => '115.00'],
            ['name' => 'Per-guest catering', 'quantity' => '2', 'unit_price' => '57.50'],
        ], $admin);

        $lines = $original->lines;

        $this->creditIssuer()->issue($original, $admin, 'One item returned, rest kept', [
            ['source_line_id' => $lines[0]->id, 'quantity' => '1'],
        ]);

        $original->refresh();
        $this->assertSame(BillingDocument::STATUS_PARTIALLY_CREDITED, $original->status);
    }

    public function test_a_credit_note_without_a_reason_or_with_a_reason_under_the_minimum_length_is_rejected(): void
    {
        $admin = $this->admin();
        $original = $this->issuedStandaloneDocument([
            ['name' => 'Setup fee', 'quantity' => '1', 'unit_price' => '115.00'],
        ], $admin);
        $line = $original->lines->first();

        $payload = ['lines' => [['source_line_id' => $line->id, 'quantity' => '1']]];

        $missingReason = $this->actingAs($admin)->post(route('admin.billing.credit.store', $original), $payload);
        $missingReason->assertSessionHasErrors('reason');

        $shortReason = $this->actingAs($admin)
            ->post(route('admin.billing.credit.store', $original), $payload + ['reason' => 'too short']);
        $shortReason->assertSessionHasErrors('reason');

        $original->refresh();
        $this->assertSame(BillingDocument::STATUS_ISSUED, $original->status, 'No credit note was actually issued.');
        $this->assertSame(0, BillingDocument::where('document_type', 'credit_note')->count());
    }

    public function test_the_credit_note_prints_the_original_document_number_and_issue_date(): void
    {
        $admin = $this->admin();
        $original = $this->issuedStandaloneDocument([
            ['name' => 'Setup fee', 'quantity' => '1', 'unit_price' => '115.00'],
        ], $admin);
        $line = $original->lines->first();

        $creditNote = $this->creditIssuer()->issue($original, $admin, 'Reprinting to verify original reference', [
            ['source_line_id' => $line->id, 'quantity' => '1'],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.billing.print', $creditNote));

        $response->assertOk();
        $response->assertSee($original->document_number);
        $response->assertSee($original->issue_date->format('d/m/Y'));
    }

    public function test_a_credit_note_cannot_be_issued_against_a_draft(): void
    {
        $admin = $this->admin();
        $draft = $this->issuer()->createDraftStandalone([
            'document_type' => 'quotation',
            'currency' => 'AED',
            'buyer_name_en' => 'Draft Buyer',
            'lines' => [['name_en' => 'Setup fee', 'quantity' => '1', 'unit_price' => '115.00', 'vat_category' => 'S']],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.billing.credit.create', $draft));

        $response->assertForbidden();
    }

    public function test_a_credit_note_cannot_be_issued_against_a_credit_note(): void
    {
        $admin = $this->admin();
        $original = $this->issuedStandaloneDocument([
            ['name' => 'Setup fee', 'quantity' => '1', 'unit_price' => '115.00'],
        ], $admin);
        $line = $original->lines->first();

        $creditNote = $this->creditIssuer()->issue($original, $admin, 'First credit note against the original', [
            ['source_line_id' => $line->id, 'quantity' => '1'],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.billing.credit.create', $creditNote));

        $response->assertForbidden();
    }

    /**
     * Not one of the numbered 45-54 items, but the create/store HTTP routes
     * (billing.credit.create / billing.credit.store) had zero coverage
     * otherwise — same "obvious gap" reasoning as StandaloneDocumentTest in
     * Batch 3.
     */
    public function test_an_admin_can_issue_a_credit_note_end_to_end_through_the_http_routes(): void
    {
        $admin = $this->admin();
        $original = $this->issuedStandaloneDocument([
            ['name' => 'Setup fee', 'quantity' => '2', 'unit_price' => '57.50'],
        ], $admin);
        $line = $original->lines->first();

        $createResponse = $this->actingAs($admin)->get(route('admin.billing.credit.create', $original));
        $createResponse->assertOk();
        $createResponse->assertSee('Setup fee');

        $storeResponse = $this->actingAs($admin)->post(route('admin.billing.credit.store', $original), [
            'reason' => 'Customer returned one of the two units',
            'lines' => [
                ['source_line_id' => $line->id, 'quantity' => '1'],
                // A second, untouched line left at 0 — the form always
                // submits every original line, credited or not.
            ],
        ]);

        $creditNote = BillingDocument::where('document_type', 'credit_note')->first();
        $this->assertNotNull($creditNote);
        $storeResponse->assertRedirect(route('admin.billing.show', $creditNote));
        $this->assertStringStartsWith('CN-', $creditNote->document_number);
        $this->assertSame($original->id, $creditNote->parent_document_id);

        $original->refresh();
        $this->assertSame(BillingDocument::STATUS_PARTIALLY_CREDITED, $original->status);
    }
}
