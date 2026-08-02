<?php

/**
 * Standalone worker for the numbering concurrency tests
 * (NumberingConcurrencySqliteTest / NumberingConcurrencyMysqlTest).
 *
 * Run as its own OS process — real concurrency, not simulated interleaving —
 * against a database shared with sibling worker processes. Each worker
 * issues exactly one pre-seeded draft and prints either "OK <document_number>"
 * or "FAIL <exception class>: <message>" on a single line, so the parent test
 * can tell a genuine failure apart from a successful, distinctly-numbered
 * issue.
 *
 * argv[1] = document id to issue
 * argv[2] = admin user id to issue as
 */

require __DIR__.'/../../../../vendor/autoload.php';

$app = require __DIR__.'/../../../../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$documentId = (int) ($argv[1] ?? 0);
$userId = (int) ($argv[2] ?? 0);

try {
    $document = \App\Models\BillingDocument::findOrFail($documentId);
    $user = \App\Models\User::findOrFail($userId);

    $issued = $app->make(\App\Services\Billing\DocumentIssuer::class)->issue($document, $user);

    fwrite(STDOUT, 'OK '.$issued->document_number.PHP_EOL);
} catch (Throwable $e) {
    fwrite(STDOUT, 'FAIL '.get_class($e).': '.$e->getMessage().PHP_EOL);
}
