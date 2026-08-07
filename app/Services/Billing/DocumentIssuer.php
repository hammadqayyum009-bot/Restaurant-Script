<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Exceptions\Billing\NumberAllocationFailedException;
use App\Exceptions\Billing\ReconciliationFailedException;
use App\Models\BillingDocument;
use App\Models\BillingDocumentEvent;
use App\Models\Order;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\Billing\Concerns\AllocatesDocumentNumbersSafely;
use Illuminate\Support\Facades\Request;
use RuntimeException;
use Throwable;

/**
 * The single issuing path for every document type — order-linked and
 * standalone converge here. Two steps, deliberately kept apart:
 *
 *  - createDraftFromOrder()/createDraftStandalone() just write a shell row.
 *    Nothing is numbered, nothing is final.
 *  - issue() is the one place a number is allocated and a document becomes
 *    immutable. Reconciliation (App\Services\Billing\Reconciler) runs inside
 *    the same transaction as numbering and the line writes, so a failed
 *    reconciliation burns no number, and a numbering collision (SQLite's
 *    unique-index path) retries the *whole* attempt in a fresh transaction —
 *    never a partially-numbered document.
 */
class DocumentIssuer
{
    use AllocatesDocumentNumbersSafely;

    /**
     * Only these carry a ZATCA-style QR code. A quotation or proforma is not
     * a tax invoice — it has no legal standing to verify — and a delivery
     * note is a fulfillment document, not a financial one.
     */
    public const TAX_INVOICE_TYPES = ['simplified_tax_invoice', 'standard_tax_invoice'];

    public function __construct(
        protected Reconciler $reconciler,
        protected DocumentNumberer $numberer,
        protected BillingSettings $settings,
        protected ActivityLogger $activity,
    ) {}

    /**
     * A tax invoice is a permanent, numbered legal document — one attempt
     * per order. Fully crediting it reverses the value, it doesn't un-issue
     * it, so this stays truthy (blocking a second attempt) even once the
     * original has reached fully_credited; a genuinely new commercial event
     * gets its own order or a standalone document, not a second tax invoice
     * against this one. Drafts don't count — only a document that actually
     * left draft status represents something legally issued.
     */
    public function issuedTaxInvoiceFor(Order $order): ?BillingDocument
    {
        return BillingDocument::where('order_id', $order->id)
            ->whereIn('document_type', self::TAX_INVOICE_TYPES)
            ->where('status', '!=', BillingDocument::STATUS_DRAFT)
            ->first();
    }

    /**
     * @param  array<string, string|null>  $buyer  name_en, name_ar, vat_number, cr_number
     */
    public function createDraftFromOrder(Order $order, string $documentType, array $buyer = []): BillingDocument
    {
        $currency = (string) config('site.currency');

        $document = BillingDocument::create([
            'document_type' => $documentType,
            'series_year' => (int) now()->year,
            'status' => BillingDocument::STATUS_DRAFT,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'currency' => $currency,
            'currency_exponent' => Money::exponent($currency),
            'prices_include_vat' => $this->settings->pricesIncludeVat(),
            'vat_rate_bp' => $this->settings->vatRateBp(),
            'buyer_name_en' => $buyer['name_en'] ?? $order->customer_name,
            'buyer_name_ar' => $buyer['name_ar'] ?? null,
            'buyer_vat_number' => $buyer['vat_number'] ?? null,
            'buyer_cr_number' => $buyer['cr_number'] ?? null,
            'buyer_phone' => $order->phone,
            'buyer_email' => $order->email,
            'buyer_address_en' => $order->address,
        ]);

        $this->activity->log('created', 'Created a '.$documentType.' draft for order #'.$order->order_number, $document);

        return $document;
    }

    /**
     * A document with nothing behind it — no order, no reconciliation
     * target. Its own line items (added via replaceDraftLines(), typically
     * right after this call) are the sole source of truth for its total.
     *
     * @param  array<string, mixed>  $data  document_type, currency, valid_until, buyer_*
     */
    public function createDraftStandalone(array $data): BillingDocument
    {
        $currency = $data['currency'] ?? $this->settings->defaultCurrency();

        $document = BillingDocument::create([
            'document_type' => $data['document_type'],
            'series_year' => (int) now()->year,
            'status' => BillingDocument::STATUS_DRAFT,
            'order_id' => null,
            'order_number' => null,
            'currency' => $currency,
            'currency_exponent' => Money::exponent($currency),
            'prices_include_vat' => $this->settings->pricesIncludeVat(),
            'vat_rate_bp' => $this->settings->vatRateBp(),
            'valid_until' => $data['valid_until'] ?? null,
            'buyer_name_en' => $data['buyer_name_en'] ?? null,
            'buyer_name_ar' => $data['buyer_name_ar'] ?? null,
            'buyer_vat_number' => $data['buyer_vat_number'] ?? null,
            'buyer_cr_number' => $data['buyer_cr_number'] ?? null,
            'buyer_phone' => $data['buyer_phone'] ?? null,
            'buyer_email' => $data['buyer_email'] ?? null,
            'buyer_address_en' => $data['buyer_address_en'] ?? null,
            'buyer_address_ar' => $data['buyer_address_ar'] ?? null,
            'notes_en' => $data['notes_en'] ?? null,
            'notes_ar' => $data['notes_ar'] ?? null,
        ]);

        $this->replaceDraftLines($document, $data['lines'] ?? []);

        $this->activity->log('created', 'Created a '.$data['document_type'].' draft (standalone)', $document);

        return $document;
    }

    /**
     * Replaces a standalone draft's line items wholesale — the edit form
     * always resubmits the complete set, so there is no partial-update case
     * to reconcile. Only ever legal on a draft: BillingDocumentPolicy denies
     * the route this is called from once a document is issued, and this is
     * a second, cheaper guard against calling it directly out of order.
     *
     * Stored uncalculated (net/vat/total all 0) — computeStandaloneTotals()
     * fills those in, at preview time (unpersisted) and at issue time
     * (persisted), from vat_rate_bp/prices_include_vat as they stand at that
     * moment. Same reason order-linked drafts have no lines until issue().
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    public function replaceDraftLines(BillingDocument $document, array $lines): void
    {
        if (! $document->isDraft()) {
            throw new RuntimeException("Only a draft's line items can be replaced.");
        }

        $document->lines()->delete();

        foreach (array_values($lines) as $position => $line) {
            $document->lines()->create([
                'position' => $position,
                'name_en' => $line['name_en'],
                'name_ar' => $line['name_ar'] ?? null,
                'quantity_milli' => Money::toMilli((string) $line['quantity']),
                'unit_price_minor' => Money::toMinor((string) $line['unit_price'], $document->currency),
                'line_net_minor' => 0,
                'line_vat_minor' => 0,
                'line_total_minor' => 0,
                'vat_rate_bp' => $document->vat_rate_bp,
                'vat_category' => $line['vat_category'] ?? 'S',
                'is_delivery_fee' => false,
            ]);
        }
    }

    /**
     * A live, unpersisted preview of the lines/totals a draft would get if
     * issued right now — so the admin can see the numbers (and any
     * reconciliation problem) before committing to a document number.
     *
     * @return array{lines: array<int, array<string, mixed>>, subtotal_net_minor: int, vat_total_minor: int, grand_total_minor: int, rounding_adjustment_minor: int}
     */
    public function preview(BillingDocument $document): array
    {
        if ($document->order_id !== null) {
            return $this->reconciler->reconcileOrder(
                $document->order,
                $document->currency,
                $document->vat_rate_bp,
                $document->prices_include_vat,
            );
        }

        return $this->computeStandaloneTotals($document);
    }

    /**
     * Unlike an order-linked document, a standalone one has no external
     * total to reconcile against — its own lines simply *are* the total, by
     * definition, so there is no equivalent of the D2 discrepancy check here.
     *
     * @return array{lines: array<int, array<string, mixed>>, subtotal_net_minor: int, vat_total_minor: int, grand_total_minor: int, rounding_adjustment_minor: int}
     */
    public function computeStandaloneTotals(BillingDocument $document): array
    {
        $document->loadMissing('lines');

        if ($document->lines->isEmpty()) {
            throw new ReconciliationFailedException('Cannot issue: add at least one line item first.');
        }

        $lines = [];

        foreach ($document->lines as $line) {
            $amountMinor = (int) round($line->unit_price_minor * $line->quantity_milli / 1000);
            $calc = VatCalculator::forLine($amountMinor, $document->vat_rate_bp, $line->vat_category, $document->prices_include_vat);

            $lines[] = [
                'name_en' => $line->name_en,
                'name_ar' => $line->name_ar,
                'quantity_milli' => $line->quantity_milli,
                'unit_price_minor' => $line->unit_price_minor,
                'line_net_minor' => $calc['net'],
                'line_vat_minor' => $calc['vat'],
                'line_total_minor' => $calc['gross'],
                'vat_rate_bp' => $document->vat_rate_bp,
                'vat_category' => $line->vat_category,
                'is_delivery_fee' => false,
            ];
        }

        return [
            'lines' => $lines,
            'subtotal_net_minor' => array_sum(array_column($lines, 'line_net_minor')),
            'vat_total_minor' => array_sum(array_column($lines, 'line_vat_minor')),
            'grand_total_minor' => array_sum(array_column($lines, 'line_total_minor')),
            'rounding_adjustment_minor' => 0,
        ];
    }

    public function issue(BillingDocument $document, User $actor): BillingDocument
    {
        if (! $document->isDraft()) {
            throw new RuntimeException('Only a draft can be issued.');
        }

        $maxAttempts = $this->numberer->maxAttempts();

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return $this->transactional(fn () => $this->attemptIssue($document, $actor));
            } catch (Throwable $e) {
                if (! $this->isRetryableNumberingFailure($e)) {
                    throw $e;
                }

                if ($attempt >= $maxAttempts) {
                    throw new NumberAllocationFailedException(
                        "Could not allocate a {$document->document_type} number after {$maxAttempts} attempts. Please try again.",
                        previous: $e,
                    );
                }

                // The whole attempt — including the counter increment — rolled
                // back with the transaction, so no number was consumed. Retry
                // fresh: re-read the counter, take the next number.
                //
                // A small jittered pause before retrying — standard backoff,
                // not a change to the 3-attempt limit — is what actually makes
                // a retry likely to succeed under real contention: several
                // writers retrying instantly tend to collide again immediately,
                // where spreading them by a few milliseconds does not.
                usleep(random_int(5_000, 25_000) * $attempt);
            }
        }

        throw new NumberAllocationFailedException("Could not allocate a {$document->document_type} number.");
    }

    protected function attemptIssue(BillingDocument $document, User $actor): BillingDocument
    {
        // Reconciliation/total-computation happens first and is never
        // retried on a numbering collision — a real failure here must
        // surface immediately, not after silently burning three attempts'
        // worth of transactions.
        $result = $document->order_id !== null
            ? $this->reconciler->reconcileOrder($document->order, $document->currency, $document->vat_rate_bp, $document->prices_include_vat)
            : $this->computeStandaloneTotals($document);

        $year = (int) now()->year;
        [$number, $formatted] = $this->numberer->allocate($document->document_type, $year);

        $seller = $this->sellerSnapshot();
        $issuedAt = now();
        $isTaxInvoice = in_array($document->document_type, self::TAX_INVOICE_TYPES, true);

        $document->fill(array_merge($seller, [
            'series_year' => $year,
            'number' => $number,
            'document_number' => $formatted,
            'status' => BillingDocument::STATUS_ISSUED,
            'subtotal_net_minor' => $result['subtotal_net_minor'],
            'vat_total_minor' => $result['vat_total_minor'],
            'grand_total_minor' => $result['grand_total_minor'],
            'rounding_adjustment_minor' => $result['rounding_adjustment_minor'],
            'issue_date' => $issuedAt->toDateString(),
            'issued_at' => $issuedAt,
            'issued_by_user_id' => $actor->id,
            'issued_by_name' => $actor->name,
            'qr_payload' => $isTaxInvoice ? $this->qrPayload($seller, $result, $document->currency_exponent, $issuedAt) : null,
        ]));
        $document->save();

        // A no-op for order-linked documents (they have no lines yet at this
        // point); clears the raw, uncalculated lines a standalone draft was
        // edited with, so the computed ones below are the only ones left.
        $document->lines()->delete();

        foreach ($result['lines'] as $position => $line) {
            $document->lines()->create(array_merge($line, ['position' => $position]));
        }

        BillingDocumentEvent::create([
            'document_id' => $document->id,
            'user_id' => $actor->id,
            'user_name' => $actor->name,
            'from_status' => BillingDocument::STATUS_DRAFT,
            'to_status' => BillingDocument::STATUS_ISSUED,
            'ip' => Request::ip(),
        ]);

        $this->activity->log(
            'status',
            "Issued {$document->document_type} {$formatted}".($document->order_number ? " for order #{$document->order_number}" : ''),
            $document,
        );

        return $document->refresh();
    }

    /** @return array<string, string|null> */
    protected function sellerSnapshot(): array
    {
        return [
            'seller_name_en' => $this->settings->sellerName('en'),
            'seller_name_ar' => $this->settings->sellerName('ar'),
            'seller_vat_number' => $this->settings->vatNumber() ?: null,
            'seller_cr_number' => $this->settings->crNumber() ?: null,
            'seller_address_en' => $this->settings->address('en') ?: null,
            'seller_address_ar' => $this->settings->address('ar') ?: null,
            'seller_phone' => $this->settings->phone() ?: null,
            'seller_email' => $this->settings->email() ?: null,
            'seller_logo_path' => $this->settings->logo(),
            'footer_en' => $this->settings->footer('en') ?: null,
            'footer_ar' => $this->settings->footer('ar') ?: null,
            'hijri_date' => $this->settings->showHijri() ? HijriDate::for(now()) : null,
        ];
    }

    /**
     * @param  array<string, string|null>  $seller
     * @param  array{grand_total_minor: int, vat_total_minor: int}  $result
     */
    protected function qrPayload(array $seller, array $result, int $currencyExponent, \DateTimeInterface $issuedAt): string
    {
        $sellerName = $seller['seller_name_ar'] ?: $seller['seller_name_en'] ?: '';

        return Tlv::build(
            $sellerName,
            (string) ($seller['seller_vat_number'] ?? ''),
            $issuedAt->format('Y-m-d\TH:i:s'),
            Money::toDecimalFromExponent($result['grand_total_minor'], $currencyExponent),
            Money::toDecimalFromExponent($result['vat_total_minor'], $currencyExponent),
        );
    }
}
