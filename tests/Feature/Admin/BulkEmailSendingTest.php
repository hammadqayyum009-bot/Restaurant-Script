<?php

namespace Tests\Feature\Admin;

use App\Models\BulkEmailJob;
use App\Models\BulkEmailRecipient;
use App\Models\EmailLog;
use App\Models\User;
use App\Services\BulkEmailSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * MailController::sendCompose() used to loop inline over every recipient
 * with no chunking, no cap, and no persisted progress — a large send risked
 * a shared-hosting execution timeout partway through. BulkEmailSender
 * persists every recipient up front and processes one bounded chunk per
 * run() call, mirroring PaymentReconciliationService. See the full-project
 * audit ("bulk email synchronous send").
 */
class BulkEmailSendingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('notifications.bulk_email.batch_size', 3);
    }

    protected function targets(int $count): Collection
    {
        return collect(range(1, $count))->map(fn ($i) => [
            'name' => "Recipient {$i}",
            'email' => "recipient{$i}@example.com",
        ]);
    }

    public function test_a_send_with_more_recipients_than_one_chunk_completes_across_multiple_runs_with_no_duplicates_and_none_skipped(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $sender = app(BulkEmailSender::class);

        $job = $sender->create('Hello {{name}}', 'Body for {{name}}', $this->targets(7), 'custom', $admin->id);

        $this->assertSame(7, $job->total_count);
        $this->assertSame(BulkEmailJob::STATUS_PENDING, $job->fresh()->status);
        $this->assertSame(7, BulkEmailRecipient::where('bulk_email_job_id', $job->id)->count());

        // Batch size 3, 7 recipients: 3 + 3 + 1 = three runs to finish.
        $result1 = $sender->run($job);
        $this->assertSame(3, $result1['sent']);
        $this->assertSame(BulkEmailJob::STATUS_PENDING, $job->fresh()->status);

        $result2 = $sender->run($job);
        $this->assertSame(3, $result2['sent']);
        $this->assertSame(BulkEmailJob::STATUS_PENDING, $job->fresh()->status);

        $result3 = $sender->run($job);
        $this->assertSame(1, $result3['sent']);
        $this->assertSame(BulkEmailJob::STATUS_COMPLETED, $job->fresh()->status);

        // Nothing left to do — a further run() must not touch anything.
        $result4 = $sender->run($job);
        $this->assertSame(0, $result4['checked']);
        $this->assertSame(0, $result4['sent']);

        $job->refresh();
        $this->assertSame(7, $job->sent_count);
        $this->assertSame(0, $job->failed_count);
        $this->assertNotNull($job->completed_at);

        // Every recipient sent exactly once — no duplicates, none skipped.
        $this->assertSame(7, BulkEmailRecipient::where('bulk_email_job_id', $job->id)->where('status', 'sent')->count());
        $this->assertSame(0, BulkEmailRecipient::where('bulk_email_job_id', $job->id)->where('status', 'pending')->count());

        for ($i = 1; $i <= 7; $i++) {
            $this->assertSame(
                1,
                EmailLog::where('to_email', "recipient{$i}@example.com")->where('type', 'manual')->count(),
                "recipient{$i}@example.com should have been emailed exactly once."
            );
        }
    }

    public function test_run_picks_the_oldest_unfinished_job_when_none_is_specified(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $sender = app(BulkEmailSender::class);

        $older = $sender->create('Older', 'Body', $this->targets(1), 'custom', $admin->id);
        $older->forceFill(['created_at' => now()->subMinute()])->save();
        $sender->create('Newer', 'Body', $this->targets(1), 'custom', $admin->id);

        $result = $sender->run();

        $this->assertSame($older->id, $result['job_id']);
    }

    public function test_composing_a_send_creates_a_durable_job_and_processes_the_first_chunk_immediately(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        User::factory()->count(2)->create(['is_admin' => false, 'is_active' => true]);

        $response = $this->actingAs($admin, 'web')->post(route('admin.email.compose.send'), [
            'audience' => 'all_customers',
            'subject' => 'Hi {{name}}',
            'body' => 'Body',
        ]);

        $job = BulkEmailJob::firstOrFail();
        $response->assertRedirect(route('admin.email.bulk.show', $job));

        $job->refresh();
        $this->assertSame(2, $job->total_count);
        $this->assertSame(2, $job->sent_count);
        $this->assertSame(BulkEmailJob::STATUS_COMPLETED, $job->status);
    }

    public function test_the_process_next_batch_button_advances_an_unfinished_job(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $sender = app(BulkEmailSender::class);
        $job = $sender->create('Subject', 'Body', $this->targets(5), 'custom', $admin->id);
        // Batch size 3: creating alone leaves it fully pending (create() never sends).
        $this->assertSame(0, $job->sent_count);

        $response = $this->actingAs($admin, 'web')->post(route('admin.email.bulk.process', $job));

        $response->assertRedirect();
        $job->refresh();
        $this->assertSame(3, $job->sent_count);
        $this->assertSame(BulkEmailJob::STATUS_PENDING, $job->status);
    }
}
