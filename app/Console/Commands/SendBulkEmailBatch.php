<?php

namespace App\Console\Commands;

use App\Services\BulkEmailSender;
use Illuminate\Console\Command;

class SendBulkEmailBatch extends Command
{
    protected $signature = 'email:send-bulk-batch';

    protected $description = 'Send the next batch of pending recipients from the oldest unfinished bulk email job.';

    public function handle(BulkEmailSender $sender): int
    {
        $result = $sender->run();

        if (! $result['ran']) {
            $this->warn('A bulk email batch is already running (another run holds the lock) — skipped.');

            return self::SUCCESS;
        }

        if ($result['job_id'] === null) {
            $this->info('No unfinished bulk email jobs.');

            return self::SUCCESS;
        }

        $this->info("Job #{$result['job_id']}: checked {$result['checked']}, sent {$result['sent']}, failed {$result['failed']}.");

        return self::SUCCESS;
    }
}
