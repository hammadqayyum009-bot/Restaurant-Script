<?php

namespace App\Console\Commands;

use App\Payments\PaymentReconciliationService;
use Illuminate\Console\Command;

class ReconcileStuckPayments extends Command
{
    protected $signature = 'payments:reconcile';

    protected $description = 'Re-verify payment transactions that have sat pending or processing longer than the configured threshold.';

    public function handle(PaymentReconciliationService $service): int
    {
        $result = $service->run();

        if (! $result['ran']) {
            $this->warn('Reconciliation is already running (another run holds the lock) — skipped.');

            return self::SUCCESS;
        }

        $this->info("Checked {$result['checked']} stuck transaction(s), resolved {$result['resolved']}.");

        return self::SUCCESS;
    }
}
