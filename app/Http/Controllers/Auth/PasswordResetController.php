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

        if ($user && $user->is_active) {
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );

            $mailer->dispatchTemplate('password_reset', $user->email, $user->name, [
                'name' => $user->name,
                'reset_url' => route('password.reset', ['token' => $token, 'email' => $user->email]),
                'expires_in' => self::TOKEN_LIFETIME_MINUTES.' minutes',
            ]);
        }

        // The same response either way, so the form cannot be used to discover
        // which email addresses have accounts.
        return back()->with('success', 'If that email has an account, a reset link is on its way.');
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
