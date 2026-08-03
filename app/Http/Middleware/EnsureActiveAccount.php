<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deactivating a customer (Admin\UserController) must cut off access on
 * their very next request, not just their next login attempt — the session
 * guard never re-checks is_active on its own once a session exists, so this
 * is what actually enforces it for whoever is already signed in when it
 * happens. Mirrors App\Http\Middleware\EnsureAdmin's force-logout shape,
 * applied to the storefront `auth` group instead of the admin panel.
 */
class EnsureActiveAccount
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'This account is no longer active.']);
        }

        return $next($request);
    }
}
