<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Exceptions\Billing\NumberAllocationFailedException;
use App\Models\BillingDocument;
use App\Models\BillingDocumentEvent;
use App\Models\Order;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use RuntimeException;
use Throwable;

/**
 * The single issuing path for every document type — order-linked and
 * standalone converge here. Two steps, deliberately kept apart:
 *
 *  - createDraftFromOrder()/createDraftStandalone() (Batch 3 adds the latter)
 *    just writes a shell row. Nothing is numbered, nothing is final.
 *  - issue() is the one place a number is allocated and a document becomes
 *    immutable. Reconciliation (App\Services\Billing\Reconciler) runs inside
 *    the same transaction as numbering and the line writes, so a failed
 *    reconciliation burns no number, and a numbering collision (SQLite's
 *    unique-index path) retries the *whole* attempt in a fresh transaction —
 *    never a partially-numbered document.
 */
class DocumentIssuer
{
    public function __construct(
        protected Reconciler $reconciler,
        protected DocumentNumberer $numberer,
        protected BillingSettings $settings,
        protected ActivityLogger $activity,
    ) {
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
     * A live, unpersisted preview of the lines/totals a draft would get if
     * issued right now — so the admin can see the numbers (and any
     * reconciliation problem) before committing to a document number.
     *
     * @return array{lines: array<int, array<string, mixed>>, subtotal_net_minor: int, vat_total_minor: int, grand_total_minor: int, rounding_adjustment_minor: int}
     */
    public function preview(BillingDocument $document): array
    {
        return $this->reconciler->reconcileOrder(
            $document->order,
            $document->currency,
            $document->vat_rate_bp,
            $document->prices_include_vat,
        );
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

    /**
     * True for the two ways a numbering attempt can collide under real
     * concurrency:
     *  - a genuine unique-index violation (two writers picked the same
     *    number — the scenario the SQLite path is built around);
     *  - SQLite's "database is locked" (SQLITE_BUSY), which a plain
     *    UniqueConstraintViolationException catch does not cover, and which
     *    concurrency testing surfaced as a real gap: several concurrent
     *    writers can be refused the file lock outright, as a QueryException,
     *    with no unique-index violation involved at all.
     *
     * Anything else (a real schema problem, a connection failure) is not
     * retried — retrying those would only mask a genuine bug.
     */
    protected function isRetryableNumberingFailure(Throwable $e): bool
    {
        if ($e instanceof UniqueConstraintViolationException) {
            return true;
        }

        return $e instanceof QueryException
            && str_contains(strtolower($e->getMessage()), 'database is locked');
    }

    /**
     * On SQLite, Laravel's default transaction issues a plain (deferred)
     * BEGIN: the write lock is only taken at the first actual write, not at
     * BEGIN time. Under real concurrency that lets several processes all
     * SELECT the counter's last_number before any of them has written
     * anything, so they compute the *same* next number and only collide
     * later at the document insert — a thundering herd that can exhaust all
     * 3 retries even though the safety net (the unique index) still holds.
     *
     * `BEGIN IMMEDIATE` takes SQLite's write lock immediately, so a second
     * process's counter read genuinely waits for the first process's write to
     * finish, rather than racing it — turning "many transactions collide"
     * into "transactions queue", which is what a numbering series needs.
     * MySQL doesn't have this problem (row-level locking, via
     * lockForUpdate() in DocumentNumberer), so it keeps Laravel's normal
     * DB::transaction().
     *
     * SQLite forbids a nested BEGIN outright, so when a transaction is
     * already open on this connection — as RefreshDatabase keeps one open
     * for the whole test, to roll back between tests without re-migrating —
     * this falls back to DB::transaction()'s ordinary (savepoint) nesting
     * instead. That's fine for that case: RefreshDatabase's tests run
     * sequentially, not under real concurrency, so there is no herd for
     * BEGIN IMMEDIATE to prevent.
     */
    protected function transactional(\Closure $callback): mixed
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'sqlite' || $connection->transactionLevel() > 0) {
            return DB::transaction($callback);
        }

        $connection->unprepared('BEGIN IMMEDIATE');

        try {
            $result = $callback();
            $connection->unprepared('COMMIT');

            return $result;
        } catch (Throwable $e) {
            $connection->unprepared('ROLLBACK');
            throw $e;
        }
    }

    protected function attemptIssue(BillingDocument $document, User $actor): BillingDocument
    {
        // Reconciliation happens first and is never retried on a numbering
        // collision — a real reconciliation failure must surface immediately,
        // not after silently burning three attempts' worth of transactions.
        $result = $this->reconciler->reconcileOrder(
            $document->order,
            $document->currency,
            $document->vat_rate_bp,
            $document->prices_include_vat,
        );

        $year = (int) now()->year;
        [$number, $formatted] = $this->numberer->allocate($document->document_type, $year);

        $seller = $this->sellerSnapshot();
        $issuedAt = now();

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
            'qr_payload' => $this->qrPayload($seller, $result, $document->currency, $issuedAt),
        ]));
        $document->save();

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
            "Issued {$document->document_type} {$formatted} for order #{$document->order_number}",
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
    protected function qrPayload(array $seller, array $result, string $currency, \DateTimeInterface $issuedAt): string
    {
        $sellerName = $seller['seller_name_ar'] ?: $seller['seller_name_en'] ?: '';

        return Tlv::build(
            $sellerName,
            (string) ($seller['seller_vat_number'] ?? ''),
            $issuedAt->format('Y-m-d\TH:i:s'),
            Money::toDecimal($result['grand_total_minor'], $currency),
            Money::toDecimal($result['vat_total_minor'], $currency),
        );
    }
}
