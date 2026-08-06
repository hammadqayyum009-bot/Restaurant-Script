<?php

namespace App\Services;

use App\Models\BulkEmailJob;
use App\Models\BulkEmailRecipient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * MailController::sendCompose() used to loop inline over every recipient
 * with no chunking, no cap, and no persisted progress — a large "all
 * customers" send risked a shared-hosting execution timeout partway through,
 * with no way to know how far it got and no way to resume without emailing
 * earlier recipients again. See the full-project audit ("bulk email
 * synchronous send").
 *
 * Same shape as PaymentReconciliationService, deliberately: every recipient
 * is persisted up front (durable — a failed request never loses the list),
 * and run() processes one bounded chunk per invocation, callable three ways
 * with identical behaviour — the Artisan command, the scheduler, and an
 * admin "Process next batch" button — so there is exactly one
 * implementation to reason about, and sending to any audience size never
 * risks a single-request timeout.
 */
class BulkEmailSender
{
    public function __construct(protected Mailer $mailer) {}

    /**
     * Persists the job and every recipient row before anything is sent —
     * this is what makes even a single-recipient send durable: a request
     * that dies after this point still has a resumable, accurate record of
     * who was and wasn't emailed, rather than a loop's in-memory progress
     * that a crash simply loses.
     *
     * @param  Collection<int, array{name: string, email: string}>  $targets
     */
    public function create(string $subject, string $body, Collection $targets, string $audience, ?int $createdBy): BulkEmailJob
    {
        $job = BulkEmailJob::create([
            'subject' => $subject,
            'body' => $body,
            'audience' => $audience,
            'status' => BulkEmailJob::STATUS_PENDING,
            'total_count' => $targets->count(),
            'sent_count' => 0,
            'failed_count' => 0,
            'created_by' => $createdBy,
        ]);

        $now = now();

        $rows = $targets->map(fn (array $target) => [
            'bulk_email_job_id' => $job->id,
            'name' => $target['name'],
            'email' => $target['email'],
            'status' => BulkEmailRecipient::STATUS_PENDING,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($rows !== []) {
            BulkEmailRecipient::insert($rows);
        }

        return $job;
    }

    /**
     * A single named Cache::lock() around the whole chunk — same reasoning
     * as PaymentReconciliationService::run(): stops a cron tick and an admin
     * button click landing at the same moment from both pulling the same
     * pending batch and emailing it twice. Passing $job processes that job's
     * next chunk specifically (the compose screen's immediate first-chunk
     * send); omitting it picks the oldest unfinished job (the cron/command
     * path, which doesn't know which job to resume).
     *
     * @return array{ran: bool, job_id: ?int, checked: int, sent: int, failed: int}
     */
    public function run(?BulkEmailJob $job = null): array
    {
        $lock = Cache::lock('bulk-email-send-run', 300);

        if (! $lock->get()) {
            return ['ran' => false, 'job_id' => $job?->id, 'checked' => 0, 'sent' => 0, 'failed' => 0];
        }

        try {
            $job ??= BulkEmailJob::where('status', BulkEmailJob::STATUS_PENDING)->oldest()->first();

            if (! $job) {
                return ['ran' => true, 'job_id' => null, 'checked' => 0, 'sent' => 0, 'failed' => 0];
            }

            $recipients = $job->recipients()
                ->where('status', BulkEmailRecipient::STATUS_PENDING)
                ->oldest('id')
                ->limit((int) config('notifications.bulk_email.batch_size'))
                ->get();

            $sent = 0;
            $failed = 0;

            foreach ($recipients as $recipient) {
                $vars = $this->mailer->baseVars() + ['name' => $recipient->name, 'email' => $recipient->email];

                $ok = $this->mailer->send(
                    $recipient->email,
                    $recipient->name ?: null,
                    $this->mailer->replace($job->subject, $vars),
                    $this->mailer->replace($job->body, $vars),
                    'manual',
                );

                if ($ok) {
                    $recipient->update(['status' => BulkEmailRecipient::STATUS_SENT, 'sent_at' => now()]);
                    $sent++;
                } else {
                    $recipient->update(['status' => BulkEmailRecipient::STATUS_FAILED, 'error' => 'Delivery failed — see the email log for details.']);
                    $failed++;
                }
            }

            if ($sent > 0) {
                $job->increment('sent_count', $sent);
            }
            if ($failed > 0) {
                $job->increment('failed_count', $failed);
            }

            $stillPending = $job->recipients()->where('status', BulkEmailRecipient::STATUS_PENDING)->exists();

            if (! $stillPending) {
                $job->update(['status' => BulkEmailJob::STATUS_COMPLETED, 'completed_at' => now()]);
            }

            return ['ran' => true, 'job_id' => $job->id, 'checked' => $recipients->count(), 'sent' => $sent, 'failed' => $failed];
        } finally {
            $lock->release();
        }
    }
}
