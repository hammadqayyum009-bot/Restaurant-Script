<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Caps how often a public, unauthenticated form (registration,
 * forgot-password, contact, reservations, reviews) can be submitted from the
 * same IP.
 *
 * Deliberately not ThrottlesLogins: that trait is keyed on email + IP and
 * only counts *failed* attempts, specifically so one attacker can't lock a
 * real customer out of their own account by hammering it from elsewhere.
 * None of these five forms have an account to protect that way — reviews
 * doesn't even have an email field — and the abuse pattern that actually
 * matters here (one source hitting many different targets: spamming the
 * contact inbox, mass-creating fake reservations) is exactly what IP-only,
 * every-attempt-counts keying stops. Same 5-per-60-seconds shape as login,
 * just without the email key or the failure-only counting.
 */
trait ThrottlesPublicSubmissions
{
    protected int $publicSubmissionMaxAttempts = 5;

    protected int $publicSubmissionDecaySeconds = 60;

    /**
     * @param  string  $errorField  The validation-error key the rejection is
     *     reported under — must be a field the calling view actually renders
     *     an error for.
     * @param  string  $prefix  Keeps this endpoint's counter separate from
     *     every other throttled endpoint sharing the same visitor's IP.
     */
    protected function ensurePublicSubmissionIsNotRateLimited(Request $request, string $errorField, string $prefix): void
    {
        $key = $prefix.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, $this->publicSubmissionMaxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                $errorField => $seconds >= 60
                    ? 'Too many attempts. Try again in '.ceil($seconds / 60).' minute(s).'
                    : 'Too many attempts. Try again in '.$seconds.' seconds.',
            ]);
        }

        RateLimiter::hit($key, $this->publicSubmissionDecaySeconds);
    }
}
