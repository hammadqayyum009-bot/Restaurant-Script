<?php

declare(strict_types=1);

namespace App\Services\Billing\Concerns;

use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Shared by every service that allocates a document number inside the same
 * transaction as its row writes (App\Services\Billing\DocumentIssuer,
 * App\Services\Billing\CreditNoteIssuer) — extracted rather than duplicated
 * because the SQLite race-safety trick below is easy to get subtly wrong a
 * second time, and both services allocate numbers through the same
 * App\Services\Billing\DocumentNumberer.
 */
trait AllocatesDocumentNumbersSafely
{
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
     * instead. That fallback is deliberately restricted to
     * app()->runningUnitTests() (APP_ENV=testing): RefreshDatabase's tests
     * run sequentially, not under real concurrency, so there is no herd for
     * BEGIN IMMEDIATE to prevent there.
     *
     * Outside tests, the current call paths (DocumentController::issue() ->
     * DocumentIssuer::issue(), CreditNoteController::store() ->
     * CreditNoteIssuer::issue()) never nest this inside another transaction,
     * so transactionLevel() is always 0 here, and BEGIN IMMEDIATE always
     * fires in production as shipped today. But if a future change ever
     * nested one of these calls inside another DB::transaction() on SQLite,
     * silently falling back would quietly remove the protection this whole
     * fix exists for. So outside testing, that combination throws instead of
     * degrading silently — loud failure over a false guarantee.
     */
    protected function transactional(\Closure $callback): mixed
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'sqlite') {
            return DB::transaction($callback);
        }

        if ($connection->transactionLevel() > 0) {
            if (! app()->runningUnitTests()) {
                throw new RuntimeException(
                    static::class.' attempted a numbering transaction from inside an existing transaction on '.
                    'SQLite outside the test harness. BEGIN IMMEDIATE cannot run there, and falling back silently '.
                    'would remove the concurrency protection this method exists for — refusing instead of risking '.
                    'a duplicate or lost document number.',
                );
            }

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
}
