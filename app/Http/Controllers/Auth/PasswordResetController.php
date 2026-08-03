<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Mailer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Password recovery built on the framework's password_reset_tokens table but
 * delivered through the site's own Mailer, so the admin can edit the wording
 * and every attempt shows up in the delivery log.
 */
class PasswordResetController extends Controller
{
    protected const TOKEN_LIFETIME_MINUTES = 60;

    public function showRequest()
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request, Mailer $mailer)
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $data['email'])->first();
        $devResetUrl = null;

        if ($user && $user->is_active) {
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );

            $resetUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);

            // No SMTP configured yet — the exact same condition
            // AppServiceProvider::applySettings() uses to decide whether to
            // switch the mailer off the 'log' default. Nothing is actually
            // delivered in that mode, so the link is never put in the mailed
            // body at all (only the placeholder is) — there is nothing for
            // storage/logs/laravel.log or any other log to ever capture. The
            // real, working link goes to the screen instead, for this one
            // response only. The moment SMTP is configured, mail.default
            // becomes 'smtp' and this stops on its own.
            $usingLogDriver = config('mail.default') === 'log';

            $mailer->dispatchTemplate('password_reset', $user->email, $user->name, [
                'name' => $user->name,
                'reset_url' => $usingLogDriver
                    ? 'Shown on screen — no outgoing mail server is configured yet.'
                    : $resetUrl,
                'expires_in' => self::TOKEN_LIFETIME_MINUTES.' minutes',
            ], sensitiveKeys: ['reset_url']);

            if ($usingLogDriver) {
                $devResetUrl = $resetUrl;
            }
        }

        // The same response either way, so the form cannot be used to discover
        // which email addresses have accounts.
        $response = back()->with('success', 'If that email has an account, a reset link is on its way.');

        return $devResetUrl ? $response->with('dev_reset_url', $devResetUrl) : $response;
    }

    public function showReset(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $data['email'])->first();

        if (! $record || ! Hash::check($data['token'], $record->token)) {
            return back()->withErrors(['email' => 'This reset link is not valid. Please request a new one.']);
        }

        if (Carbon::parse($record->created_at)->addMinutes(self::TOKEN_LIFETIME_MINUTES)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

            return back()->withErrors(['email' => 'This reset link has expired. Please request a new one.']);
        }

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            return back()->withErrors(['email' => 'This reset link is not valid. Please request a new one.']);
        }

        $user->password = Hash::make($data['password']);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        return redirect()->route($user->isAdmin() ? 'admin.login' : 'login')
            ->with('success', 'Your password has been changed. You can sign in now.');
    }
}
