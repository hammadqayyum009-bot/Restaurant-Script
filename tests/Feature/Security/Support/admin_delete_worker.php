<?php

use App\Models\User;
use App\Services\AdminUserDeleter;
use Illuminate\Contracts\Console\Kernel;

/**
 * Standalone worker for the last-admin-deletion concurrency tests
 * (LastAdminDeletionConcurrencySqliteTest / LastAdminDeletionConcurrencyMysqlTest).
 *
 * Run as its own OS process — real concurrency, not simulated interleaving —
 * against a database shared with a sibling worker process, both racing to
 * delete a *different* admin at (as close as the OS scheduler allows) the
 * same moment. Prints exactly one of:
 *   OK deleted   — this worker's target admin was deleted
 *   OK refused   — AdminUserDeleter correctly refused (last-admin guard)
 *   FAIL <exception class>: <message>
 *
 * argv[1] = user id of the admin this worker should try to delete
 */

require __DIR__.'/../../../../vendor/autoload.php';

$app = require __DIR__.'/../../../../bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$userId = (int) ($argv[1] ?? 0);

try {
    $user = User::findOrFail($userId);

    $deleted = $app->make(AdminUserDeleter::class)->delete($user);

    fwrite(STDOUT, ($deleted ? 'OK deleted' : 'OK refused').PHP_EOL);
} catch (Throwable $e) {
    fwrite(STDOUT, 'FAIL '.get_class($e).': '.$e->getMessage().PHP_EOL);
}
