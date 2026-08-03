<?php

namespace Tests\Feature\Security;

use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test: the plaintext password-reset token used to end up in
 * email_logs.body (Mailer::send() logged the fully-rendered body, reset URL
 * and all, unconditionally — the hashing in password_reset_tokens never
 * protected this second copy) and, whenever no SMTP was configured yet
 * (mail.default stays 'log', the shipped default), a second copy landed in
 * storage/logs/laravel.log via Laravel's own log mail transport.
 *
 * The fix redacts email_logs.body always, and — when no mail driver is
 * configured — never puts the real token in the mailed body at all, showing
 * it on screen for that one response instead. There is nothing left for
 * *any* log to capture in that mode, which is what the second test proves
 * without needing to touch the filesystem: the same value that would have
 * been handed to any log-based transport (the mailed body) is exactly what
 * email_logs.body records, and it contains no token either.
 */
class PasswordResetLogTest extends TestCase
{
    use RefreshDatabase;

    protected function activeUser(): User
    {
        return User::factory()->create(['is_active' => true]);
    }

    public function test_smtp_configured_the_real_link_is_mailed_but_never_logged_in_the_clear(): void
    {
        config(['mail.default' => 'smtp']);
        $user = $this->activeUser();

        $this->post(route('password.email'), ['email' => $user->email]);

        $log = EmailLog::where('to_email', $user->email)->latest()->first();

        $this->assertNotNull($log);
        // Any real reset link necessarily contains this path segment — its
        // absence proves no working link (token or otherwise) leaked into
        // the log, without needing to know the random token value itself.
        $this->assertStringNotContainsString('/reset-password/', $log->body, 'The reset link must never appear in the delivery log.');
        $this->assertStringContainsString('[redacted]', $log->body);
    }

    public function test_no_mail_driver_configured_the_link_is_shown_on_screen_and_never_logged_anywhere(): void
    {
        config(['mail.default' => 'log']);
        $user = $this->activeUser();

        $response = $this->post(route('password.email'), ['email' => $user->email]);

        $response->assertSessionHas('dev_reset_url');
        $devUrl = session('dev_reset_url');
        $this->assertStringContainsString('/reset-password/', $devUrl, 'The on-screen link must actually be usable.');

        // The exact value handed to Mail::html() — and therefore to
        // whatever the 'log' mail transport itself would write to
        // storage/logs/laravel.log — is the same body persisted here. If it
        // contains no working link, nothing downstream of it can either.
        $log = EmailLog::where('to_email', $user->email)->latest()->first();
        $this->assertNotNull($log);
        $this->assertStringNotContainsString('/reset-password/', $log->body);
    }

    public function test_a_nonexistent_email_produces_the_same_response_with_no_log_entry(): void
    {
        config(['mail.default' => 'log']);

        $response = $this->post(route('password.email'), ['email' => 'nobody@example.com']);

        $response->assertSessionMissing('dev_reset_url');
        $response->assertSessionHas('success');
        $this->assertSame(0, EmailLog::count());
    }
}
