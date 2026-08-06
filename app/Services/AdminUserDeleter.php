<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Guards against ever leaving zero admin accounts. UserController::destroy()'s
 * original check — count admins, then delete if more than one exists — is a
 * plain check-then-act: two concurrent deletes of two different admins, with
 * exactly two existing, can both pass the count check before either commits
 * its delete. Same class of race as DocumentNumberer's number allocation and
 * PaymentTransactionController::reserveRefundAmount() — same fix, a
 * lockForUpdate() row lock inside the transaction that also performs the
 * delete, so a second concurrent attempt blocks until the first commits and
 * then sees the up-to-date count.
 *
 * A standalone class, not a controller method, so the concurrency tests can
 * call it directly — no HTTP request or authenticated session required —
 * exactly how the numbering concurrency tests call DocumentIssuer::issue()
 * directly rather than going through a controller.
 *
 * The retry/BEGIN IMMEDIATE handling below is the same SQLite gap
 * App\Services\Billing\Concerns\AllocatesDocumentNumbersSafely was built to
 * close, reproduced here rather than shared: on SQLite, Laravel's default
 * transaction issues a plain (deferred) BEGIN, so two concurrent writers can
 * both read the admin count before either has written anything, and then
 * SQLite's zero-timeout default refuses the loser's write outright as a raw
 * "database is locked" QueryException instead of making it wait — real
 * concurrency testing of this exact class hit it directly. MySQL/MariaDB
 * (this app's actual production target) has no such gap: lockForUpdate()
 * does real row-level blocking there, so plain DB::transaction() is used
 * unchanged.
 */
class AdminUserDeleter
{
    protected const MAX_ATTEMPTS = 3;

    /**
     * Returns true if the user was deleted, false if it was refused because
     * it is the last remaining admin.
     */
    public function delete(User $user): bool
    {
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                return $this->transactional(function () use ($user) {
                    if ($user->is_admin) {
                        $adminCount = User::where('is_admin', true)->lockForUpdate()->count();

                        if ($adminCount <= 1) {
                            return false;
                        }
                    }

                    $user->delete();

                    return true;
                });
            } catch (Throwable $e) {
                if (! $this->isRetryableLockFailure($e) || $attempt >= self::MAX_ATTEMPTS) {
                    throw $e;
                }

                // Same jittered backoff as the numbering engine's retry loop —
                // spreads two instantly-retrying writers apart so the second
                // attempt actually sees the first one's committed state
                // instead of colliding again immediately.
                usleep(random_int(5_000, 25_000) * $attempt);
            }
        }

        return false; // Unreachable: the loop above always returns or throws.
    }

    protected function isRetryableLockFailure(Throwable $e): bool
    {
        return $e instanceof QueryException
            && str_contains(strtolower($e->getMessage()), 'database is locked');
    }

    /**
     * BEGIN IMMEDIATE takes SQLite's write lock immediately, so a second
     * process's count read genuinely waits for the first process's delete to
     * finish, rather than racing it — turning "both read the same stale
     * count" into "the second one queues and sees the update." MySQL doesn't
     * have this problem (row-level locking via lockForUpdate()), so it keeps
     * Laravel's normal DB::transaction().
     *
     * SQLite forbids a nested BEGIN outright, so when a transaction is
     * already open on this connection — as RefreshDatabase keeps one open
     * for the whole test, to roll back between tests without re-migrating —
     * this falls back to DB::transaction()'s ordinary (savepoint) nesting
     * instead. That fallback is deliberately restricted to
     * app()->runningUnitTests(): RefreshDatabase's tests run sequentially,
     * not under real concurrency, so there is no herd for BEGIN IMMEDIATE to
     * prevent there. Outside tests this call is never nested inside another
     * transaction today, so BEGIN IMMEDIATE always fires in production as
     * shipped — but if a future change ever nested this inside another
     * DB::transaction() on SQLite, silently falling back would quietly
     * remove the protection this method exists for, so that combination
     * throws instead of degrading silently.
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
                    static::class.' attempted a delete transaction from inside an existing transaction on '.
                    'SQLite outside the test harness. BEGIN IMMEDIATE cannot run there, and falling back silently '.
                    'would remove the concurrency protection this method exists for — refusing instead of risking '.
                    'two admins being deleted at once.',
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
