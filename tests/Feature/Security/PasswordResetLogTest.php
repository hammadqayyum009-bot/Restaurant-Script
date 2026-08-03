<?php

namespace Tests\Feature\Security;

use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression test: the plaintext password-reset token used to end up in
 * email_logs.body (Mailer::send() logged the fully-rendered body, reset URL
 * and all, unconditionally — the hashing in password_reset_tokens never
 * protected this second copy).
 *
 * The first fix attempt for the "no SMTP configured" case showed the real
 * link on screen instead of emailing it — which is worse than the original
 * bug: password reset's whole security model depends on the link only ever
 * reaching the account owner's inbox, and an on-screen link hands it to
 * whoever submits the form, for any email address, account-takeover style.
 * The corrected behavior: when there is no way to actually deliver the
 * link (mail.default is still 'log'), the feature simply refuses to run —
 * no token generated, nothing shown, nothing logged — rather than degrading
 * into something insecure.
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
        // absence proves no working link leaked into the log, without
        // needing to know the random token value itself.
        $this->assertStringNotContainsString('/reset-password/', $log->body, 'The reset link must never appear in the delivery log.');
        $this->assertStringContainsString('[redacted]', $log->body);
    }

    public function test_normal_behavior_resumes_once_smtp_is_configured(): void
    {
        config(['mail.default' => 'smtp']);
        $user = $this->activeUser();

        $response = $this->post(route('password.email'), ['email' => $user->email]);

        $response->assertSessionHas('success');
        $response->assertSessionMissing('errors');
        $this->assertSame(1, DB::table('password_reset_tokens')->where('email', $user->email)->count(), 'A real, usable token must still be generated and stored once SMTP is configured.');
        $this->assertNotNull(EmailLog::where('to_email', $user->email)->first(), 'The reset email must still be dispatched.');
    }

    public function test_no_mail_driver_configured_password_reset_is_unavailable_for_a_real_account(): void
    {
        config(['mail.default' => 'log']);
        $user = $this->activeUser();

        $response = $this->post(route('password.email'), ['email' => $user->email]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString("isn't available", session('errors')->first('email'));
        $this->assertSame(0, DB::table('password_reset_tokens')->where('email', $user->email)->count(), 'No token should be generated if nothing can deliver it.');
        $this->assertSame(0, EmailLog::count(), 'Nothing should be dispatched, so nothing should be logged.');
        $this->assertStringNotContainsString('/reset-password/', $response->getContent() ?? '', 'No reset link may appear anywhere in the response.');
    }

    public function test_no_mail_driver_configured_produces_an_identical_response_for_a_nonexistent_email(): void
    {
        config(['mail.default' => 'log']);
        $realUser = $this->activeUser();

        $realResponse = $this->post(route('password.email'), ['email' => $realUser->email]);
        $realResponse->assertSessionHasErrors('email');
        $realMessage = session('errors')->first('email');

        $fakeResponse = $this->post(route('password.email'), ['email' => 'definitely-nobody@example.com']);
        $fakeResponse->assertSessionHasErrors('email');
        $fakeMessage = session('errors')->first('email');

        $this->assertSame($realMessage, $fakeMessage, 'The response must be identical whether or not the email belongs to a real account.');
        $this->assertSame(0, DB::table('password_reset_tokens')->count());
        $this->assertSame(0, EmailLog::count());
    }
}
