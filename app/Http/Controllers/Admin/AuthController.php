<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ThrottlesLogins;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ThrottlesLogins;

    public function showLogin()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited($request, 'admin-login');

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $this->recordFailedAttempt($request, 'admin-login');

            throw ValidationException::withMessages([
                'email' => 'Those details do not match our records.',
            ]);
        }

        $user = Auth::user();

        if (! $user->isAdmin() || ! $user->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'That account cannot access the admin panel.',
            ]);
        }

        $this->clearAttempts($request, 'admin-login');
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
