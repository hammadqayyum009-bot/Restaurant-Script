<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Exceptions\Billing\NumberAllocationFailedException;
use App\Models\BillingDocumentCounter;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Allocates gapless, sequential numbers per (document_type, series_year).
 *
 * allocate() must be called from inside the transaction that also inserts the
 * BillingDocument row (see App\Services\Billing\DocumentIssuer, Batch 2) — if
 * that transaction rolls back, the counter increment rolls back with it and no
 * number is lost.
 *
 * Race safety is two layers, because `lockForUpdate()` is a no-op on SQLite:
 *  - MySQL/MariaDB: `lockForUpdate()` serialises concurrent readers.
 *  - SQLite: the unique index on billing_documents(document_type, series_year,
 *    number) is what actually rejects a duplicate; the caller (DocumentIssuer)
 *    catches that and retries the whole attempt in a fresh transaction, up to
 *    config('billing.max_allocation_attempts').
 */
class DocumentNumberer
{
    public function __construct(protected BillingSettings $settings)
    {
    }

    /**
     * @return array{0: int, 1: string} [number, formatted document number]
     */
    public function allocate(string $documentType, int $year): array
    {
        $counter = $this->lockedCounter($documentType, $year);

        $next = $counter->last_number + 1;
        $counter->update(['last_number' => $next]);

        return [$next, $this->format($documentType, $year, $next)];
    }

    protected function lockedCounter(string $documentType, int $year): BillingDocumentCounter
    {
        $counter = BillingDocumentCounter::query()
            ->where('document_type', $documentType)
            ->where('series_year', $year)
            ->lockForUpdate()
            ->first();

        if ($counter) {
            return $counter;
        }

        try {
            BillingDocumentCounter::query()->create([
                'document_type' => $documentType,
                'series_year' => $year,
                'last_number' => 0,
            ]);
        } catch (UniqueConstraintViolationException) {
            // A concurrent request created the row first — re-read it below.
            // Creating the counter row itself never consumes a document number.
        }

        return BillingDocumentCounter::query()
            ->where('document_type', $documentType)
            ->where('series_year', $year)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function format(string $documentType, int $year, int $number): string
    {
        $padding = $this->settings->numberPadding();
        $prefix = $this->settings->prefix($documentType);
        $template = $this->settings->numberFormat();

        return str_replace(
            ['{PREFIX}', '{YYYY}', '{NUMBER}'],
            [$prefix, (string) $year, str_pad((string) $number, $padding, '0', STR_PAD_LEFT)],
            $template,
        );
    }

    public function maxAttempts(): int
    {
        return (int) config('billing.max_allocation_attempts', 3);
    }
}
