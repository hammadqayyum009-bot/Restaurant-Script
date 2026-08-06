<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

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
 */
class AdminUserDeleter
{
    /**
     * Returns true if the user was deleted, false if it was refused because
     * it is the last remaining admin.
     */
    public function delete(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            if ($user->is_admin) {
                $adminCount = User::where('is_admin', true)->lockForUpdate()->count();

                if ($adminCount <= 1) {
                    return false;
                }
            }

            $user->delete();

            return true;
        });
    }
}
