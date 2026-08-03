<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Exceptions\Billing\CreditLimitExceededException;
use App\Exceptions\Billing\NumberAllocationFailedException;
use App\Models\BillingDocument;
use App\Models\BillingDocumentEvent;
use App\Models\BillingDocumentLine;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\Billing\Concerns\AllocatesDocumentNumbersSafely;
use Illuminate\Support\Facades\Request;
use RuntimeException;
use Throwable;

/**
 * Issues a credit note against an already-issued document — see
 * App\Models\BillingDocument's docblock: this is the only way a document's
 * status ever regresses after issue, and it changes nothing else on the
 * original (Section G / see CreditNoteTest for the "no other column
 * touched" assertion).
 *
 * A credit note has no draft phase: it is created and numbered in the same
 * atomic step (create() + issue() converge here, unlike DocumentIssuer,
 * where they are deliberately kept apart). Numbering goes through the same
 * App\Services\Billing\DocumentNumberer and the same SQLite race-safety
 * wrapper as every other document type (AllocatesDocumentNumbersSafely).
 *
 * Two independent over-credit guards, both cumulative across every credit
 * note ever issued against the same original — not just the one being
 * submitted:
 *  - line-level: the credited quantity of a given original line can never
 *    exceed that line's own original quantity;
 *  - document-level: the credited amount (grand_total_minor) can never
 *    exceed the original document's own grand_total_minor.
 * Either can fail on its own; see CreditNoteTest for cases where the
 * line-level guard passes but the document-level guard is what actually
 * catches the over-credit (two notes whose individual line requests are
 * each in-bounds but whose combined amount is not).
 */
class CreditNoteIssuer
{
    use AllocatesDocumentNumbersSafely;

    public function __construct(
        protected DocumentNumberer $numberer,
        protected ActivityLogger $activity,
    ) {
    }

    /**
     * @param  array<int, array{source_line_id: int, quantity: string}>  $lines
     */
    public function issue(BillingDocument $original, User $actor, string $reason, array $lines): BillingDocument
    {
        if ($original->document_type === 'credit_note') {
            throw new RuntimeException('Cannot issue a credit note against a credit note.');
        }

        if (! in_array($original->status, [BillingDocument::STATUS_ISSUED, BillingDocument::STATUS_PARTIALLY_CREDITED], true)) {
            throw new RuntimeException('Cannot issue a credit note against a document that is not issued.');
        }

        $maxAttempts = $this->numberer->maxAttempts();

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return $this->transactional(fn () => $this->attemptIssue($original, $actor, $reason, $lines));
            } catch (Throwable $e) {
                if (! $this->isRetryableNumberingFailure($e)) {
                    throw $e;
                }

                if ($attempt >= $maxAttempts) {
                    throw new NumberAllocationFailedException(
                        "Could not allocate a credit_note number after {$maxAttempts} attempts. Please try again.",
                        previous: $e,
                    );
                }

                // Same jittered backoff as DocumentIssuer::issue() — see that
                // method's comment for why this specific range.
                usleep(random_int(5_000, 25_000) * $attempt);
            }
        }

        throw new NumberAllocationFailedException('Could not allocate a credit_note number.');
    }

    protected function attemptIssue(BillingDocument $original, User $actor, string $reason, array $lines): BillingDocument
    {
        $original->loadMissing('lines');

        $computed = $this->computeCreditLines($original, $lines);

        $year = (int) now()->year;
        [$number, $formatted] = $this->numberer->allocate('credit_note', $year);
        $issuedAt = now();

        $creditNote = BillingDocument::create([
            'document_type' => 'credit_note',
            'series_year' => $year,
            'number' => $number,
            'document_number' => $formatted,
            'status' => BillingDocument::STATUS_ISSUED,
            'order_id' => null,
            'order_number' => $original->order_number,
            'parent_document_id' => $original->id,
            'currency' => $original->currency,
            'currency_exponent' => $original->currency_exponent,
            'prices_include_vat' => $original->prices_include_vat,
            'vat_rate_bp' => $original->vat_rate_bp,
            'subtotal_net_minor' => $computed['subtotal_net_minor'],
            'vat_total_minor' => $computed['vat_total_minor'],
            'grand_total_minor' => $computed['grand_total_minor'],
            'rounding_adjustment_minor' => 0,
            'issue_date' => $issuedAt->toDateString(),
            'issued_at' => $issuedAt,
            'issued_by_user_id' => $actor->id,
            'issued_by_name' => $actor->name,
            'seller_name_en' => $original->seller_name_en,
            'seller_name_ar' => $original->seller_name_ar,
            'seller_vat_number' => $original->seller_vat_number,
            'seller_cr_number' => $original->seller_cr_number,
            'seller_address_en' => $original->seller_address_en,
            'seller_address_ar' => $original->seller_address_ar,
            'seller_phone' => $original->seller_phone,
            'seller_email' => $original->seller_email,
            'seller_logo_path' => $original->seller_logo_path,
            'buyer_name_en' => $original->buyer_name_en,
            'buyer_name_ar' => $original->buyer_name_ar,
            'buyer_vat_number' => $original->buyer_vat_number,
            'buyer_cr_number' => $original->buyer_cr_number,
            'buyer_phone' => $original->buyer_phone,
            'buyer_email' => $original->buyer_email,
            'buyer_address_en' => $original->buyer_address_en,
            'buyer_address_ar' => $original->buyer_address_ar,
            'footer_en' => $original->footer_en,
            'footer_ar' => $original->footer_ar,
            'credit_reason' => $reason,
            // Not a tax invoice — see DocumentIssuer::TAX_INVOICE_TYPES.
            'qr_payload' => null,
        ]);

        foreach ($computed['lines'] as $position => $line) {
            $creditNote->lines()->create(array_merge($line, ['position' => $position]));
        }

        $fromStatus = $original->status;
        $creditedSoFar = $computed['prior_document_total_minor'] + $computed['grand_total_minor'];
        $toStatus = $creditedSoFar >= $original->grand_total_minor
            ? BillingDocument::STATUS_FULLY_CREDITED
            : BillingDocument::STATUS_PARTIALLY_CREDITED;

        // The ONLY column this touches on the original.
        $original->update(['status' => $toStatus]);

        BillingDocumentEvent::create([
            'document_id' => $original->id,
            'user_id' => $actor->id,
            'user_name' => $actor->name,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
            'ip' => Request::ip(),
        ]);

        $this->activity->log(
            'status',
            "Issued credit note {$formatted} against {$original->document_number}",
            $creditNote,
        );

        return $creditNote->refresh();
    }

    /**
     * @param  array<int, array{source_line_id: int, quantity: string}>  $lines
     * @return array{lines: array<int, array<string, mixed>>, subtotal_net_minor: int, vat_total_minor: int, grand_total_minor: int, prior_document_total_minor: int}
     */
    protected function computeCreditLines(BillingDocument $original, array $lines): array
    {
        if (empty($lines)) {
            throw new CreditLimitExceededException('Add at least one line to credit.');
        }

        $computedLines = [];

        foreach ($lines as $requested) {
            $originalLine = $original->lines->firstWhere('id', (int) $requested['source_line_id']);

            if (! $originalLine) {
                throw new RuntimeException('That line does not belong to the document being credited.');
            }

            $requestedQtyMilli = Money::toMilli((string) $requested['quantity']);
            $remainingQtyMilli = $this->remainingQuantityMilli($originalLine);

            if ($requestedQtyMilli > $remainingQtyMilli) {
                throw new CreditLimitExceededException(
                    "Cannot credit {$requested['quantity']} of \"{$originalLine->name_en}\" — only ".Money::milliToDecimal($remainingQtyMilli).' remaining to credit.',
                );
            }

            $amountMinor = (int) round($originalLine->unit_price_minor * $requestedQtyMilli / 1000);
            $calc = VatCalculator::forLine($amountMinor, $originalLine->vat_rate_bp, $originalLine->vat_category, $original->prices_include_vat);

            $computedLines[] = [
                'source_line_id' => $originalLine->id,
                'name_en' => $originalLine->name_en,
                'name_ar' => $originalLine->name_ar,
                'quantity_milli' => $requestedQtyMilli,
                'unit_price_minor' => $originalLine->unit_price_minor,
                'line_net_minor' => $calc['net'],
                'line_vat_minor' => $calc['vat'],
                'line_total_minor' => $calc['gross'],
                'vat_rate_bp' => $originalLine->vat_rate_bp,
                'vat_category' => $originalLine->vat_category,
                'is_delivery_fee' => $originalLine->is_delivery_fee,
            ];
        }

        $noteTotal = array_sum(array_column($computedLines, 'line_total_minor'));
        $remainingDocumentAmount = $this->remainingAmountMinor($original);

        if ($noteTotal > $remainingDocumentAmount) {
            $remaining = Money::toDecimalFromExponent($remainingDocumentAmount, $original->currency_exponent);

            throw new CreditLimitExceededException(
                "This credit note's amount exceeds the remaining creditable balance on {$original->document_number} ({$remaining} {$original->currency} left).",
            );
        }

        return [
            'lines' => $computedLines,
            'subtotal_net_minor' => array_sum(array_column($computedLines, 'line_net_minor')),
            'vat_total_minor' => array_sum(array_column($computedLines, 'line_vat_minor')),
            'grand_total_minor' => $noteTotal,
            'prior_document_total_minor' => $original->grand_total_minor - $remainingDocumentAmount,
        ];
    }

    /**
     * How much of this original line has never been credited by any prior
     * credit note — the upper bound a new request may draw from, cumulative
     * across every credit note ever issued against its parent document.
     */
    public function remainingQuantityMilli(BillingDocumentLine $line): int
    {
        $credited = (int) BillingDocumentLine::query()
            ->where('source_line_id', $line->id)
            ->whereHas('document', fn ($q) => $q->where('parent_document_id', $line->document_id)->where('document_type', 'credit_note'))
            ->sum('quantity_milli');

        return max(0, $line->quantity_milli - $credited);
    }

    /**
     * How much of this original document's grand total has never been
     * credited by any prior credit note.
     */
    public function remainingAmountMinor(BillingDocument $document): int
    {
        $credited = (int) BillingDocument::query()
            ->where('parent_document_id', $document->id)
            ->where('document_type', 'credit_note')
            ->sum('grand_total_minor');

        return max(0, $document->grand_total_minor - $credited);
    }
}
