<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Keeps sign-in forms from being brute-forced. Attempts are counted per
 * email + IP, so one attacker cannot lock a real customer out by hammering
 * their address from elsewhere.
 */
trait ThrottlesLogins
{
    protected int $maxAttempts = 5;

    protected int $decaySeconds = 60;

    protected function throttleKey(Request $request, string $prefix): string
    {
        return $prefix.'|'.Str::lower((string) $request->input('email')).'|'.$request->ip();
    }

    protected function ensureIsNotRateLimited(Request $request, string $prefix): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request, $prefix), $this->maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request, $prefix));

        throw ValidationException::withMessages([
            'email' => $seconds >= 60
                ? 'Too many sign-in attempts. Try again in '.ceil($seconds / 60).' minute(s).'
                : 'Too many sign-in attempts. Try again in '.$seconds.' seconds.',
        ]);
    }

    protected function recordFailedAttempt(Request $request, string $prefix): void
    {
        RateLimiter::hit($this->throttleKey($request, $prefix), $this->decaySeconds);
    }

    protected function clearAttempts(Request $request, string $prefix): void
    {
        RateLimiter::clear($this->throttleKey($request, $prefix));
    }
}
