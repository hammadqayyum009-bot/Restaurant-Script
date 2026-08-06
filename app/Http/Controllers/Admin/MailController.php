<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BulkEmailJob;
use App\Models\EmailLog;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\BulkEmailSender;
use App\Services\Mailer;
use App\Services\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class MailController extends Controller
{
    public function __construct(
        protected Settings $settings,
        protected Mailer $mailer,
        protected ActivityLogger $activity,
        protected BulkEmailSender $bulkEmailSender,
    ) {}

    /* ---------------- SMTP ---------------- */

    public function smtp()
    {
        return view('admin.email.smtp', [
            'lastFailure' => EmailLog::where('status', 'failed')->latest()->first(),
        ]);
    }

    public function saveSmtp(Request $request)
    {
        $data = $request->validate([
            'mail_host' => ['required', 'string', 'max:150'],
            'mail_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:150'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_encryption' => ['required', 'in:tls,ssl,none'],
            'mail_from_address' => ['required', 'email', 'max:150'],
            'mail_from_name' => ['required', 'string', 'max:120'],
            'notify_admin_email' => ['nullable', 'email', 'max:150'],
        ]);

        // Leaving the password box empty keeps the stored one, so the form can
        // be re-saved without retyping the secret.
        if ($data['mail_password'] === null || $data['mail_password'] === '') {
            unset($data['mail_password']);
        }

        foreach (['notify_on_register', 'notify_on_order', 'notify_on_order_status', 'notify_on_reservation',
            'notify_copy_admin_on_order', 'notify_copy_admin_on_reservation'] as $toggle) {
            $data[$toggle] = $request->boolean($toggle) ? '1' : '0';
        }

        $this->settings->setMany($data, 'mail');

        $this->activity->settings('email');

        return back()->with('success', 'Email settings saved. Send a test email to confirm they work.');
    }

    public function sendTest(Request $request)
    {
        $data = $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        $ok = $this->mailer->send(
            $data['test_email'],
            null,
            'Test email from '.config('site.name'),
            '<p>This is a test email from your admin panel.</p><p>If you are reading it, SMTP is configured correctly.</p>',
            'test'
        );

        return back()->with(
            $ok ? 'success' : 'error',
            $ok
                ? 'Test email sent to '.$data['test_email'].'.'
                : 'Sending failed. Check the delivery log below for the exact error.'
        );
    }

    /* ---------------- Templates ---------------- */

    public function templates()
    {
        return view('admin.email.templates', [
            'templates' => Mailer::TEMPLATES,
            'mailer' => $this->mailer,
        ]);
    }

    public function saveTemplates(Request $request)
    {
        foreach (array_keys(Mailer::TEMPLATES) as $key) {
            $subject = $request->input('tpl_'.$key.'_subject');
            $body = $request->input('tpl_'.$key.'_body');

            if ($subject !== null) {
                $this->settings->set('tpl_'.$key.'_subject', $subject, 'mail_templates');
            }

            if ($body !== null) {
                $this->settings->set('tpl_'.$key.'_body', $body, 'mail_templates');
            }
        }

        $this->activity->log('email', 'Edited the email templates');

        return back()->with('success', 'Email templates saved.');
    }

    /* ---------------- Manual send ---------------- */

    public function compose()
    {
        return view('admin.email.compose', [
            'users' => User::orderBy('name')->get(['id', 'name', 'email', 'is_admin']),
        ]);
    }

    /**
     * Every audience size — including a single "custom" address — goes
     * through the same durable BulkEmailSender job, deliberately: one code
     * path to keep correct, rather than a "small send" shortcut that skips
     * persistence and a "large send" path that needs it. Even a
     * one-recipient send benefits, since a job persisted before the SMTP
     * call is what makes a failed send recoverable instead of silently
     * lost. The first chunk is processed synchronously in this same
     * request, so an ordinary-sized send (the overwhelming majority) still
     * finishes and reports "done" immediately; an oversized one is left
     * safely at its partial progress, picked up by the schedule or the
     * "Process next batch" button.
     */
    public function sendCompose(Request $request)
    {
        $data = $request->validate([
            'audience' => ['required', 'in:selected,all_customers,all_users,custom'],
            'recipients' => ['array'],
            'recipients.*' => ['integer', 'exists:users,id'],
            'custom_email' => ['nullable', 'email'],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string'],
        ]);

        $targets = $this->resolveRecipients($data);

        if ($targets->isEmpty()) {
            return back()->withInput()->with('error', 'No recipients matched — pick at least one.');
        }

        $job = $this->bulkEmailSender->create($data['subject'], $data['body'], $targets, $data['audience'], Auth::id());

        $this->activity->log('email', 'Queued bulk email to '.$targets->count().' recipient(s) ('.$data['audience'].')');

        $this->bulkEmailSender->run($job);

        return redirect()->route('admin.email.bulk.show', $job);
    }

    public function bulkShow(BulkEmailJob $bulkEmailJob)
    {
        $bulkEmailJob->load(['recipients' => fn ($q) => $q->where('status', 'failed')->latest('id')->limit(50)]);

        return view('admin.email.bulk-show', ['job' => $bulkEmailJob]);
    }

    public function bulkProcess(BulkEmailJob $bulkEmailJob)
    {
        if ($bulkEmailJob->isCompleted()) {
            return back()->with('success', 'This send is already complete.');
        }

        $result = $this->bulkEmailSender->run($bulkEmailJob);

        if (! $result['ran']) {
            return back()->with('error', 'A batch is already being processed (another run holds the lock) — try again shortly.');
        }

        $this->activity->log('email', "Processed a batch of bulk email job #{$bulkEmailJob->id}: {$result['sent']} sent, {$result['failed']} failed.");

        return back()->with('success', "Processed {$result['checked']}: {$result['sent']} sent, {$result['failed']} failed.");
    }

    public function logs(Request $request)
    {
        $query = EmailLog::query();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return view('admin.email.logs', [
            'logs' => $query->latest()->paginate(30)->withQueryString(),
            'activeStatus' => $status,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, array{name: string, email: string}>
     */
    protected function resolveRecipients(array $data)
    {
        return match ($data['audience']) {
            'all_customers' => User::where('is_admin', false)->where('is_active', true)
                ->get(['name', 'email'])->map(fn ($u) => ['name' => $u->name, 'email' => $u->email]),
            'all_users' => User::where('is_active', true)
                ->get(['name', 'email'])->map(fn ($u) => ['name' => $u->name, 'email' => $u->email]),
            'custom' => collect(array_filter([$data['custom_email'] ?? null]))
                ->map(fn ($email) => ['name' => '', 'email' => $email]),
            default => User::whereIn('id', $data['recipients'] ?? [])
                ->get(['name', 'email'])->map(fn ($u) => ['name' => $u->name, 'email' => $u->email]),
        };
    }
}
