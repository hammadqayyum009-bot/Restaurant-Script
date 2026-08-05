<?php

use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureInstalled;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Exceptions\InvalidSignatureException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'installed' => EnsureInstalled::class,
            'admin' => EnsureAdmin::class,
            'active' => EnsureActiveAccount::class,
        ]);

        // A provider webhook cannot carry our CSRF token — its own
        // signature check is the authentication for this one route.
        $middleware->validateCsrfTokens(except: [
            'payments/moyasar/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // A guessed or expired reservations.success link (signed route) —
        // same friendly redirect-with-message pattern used elsewhere for a
        // rejected storefront request, rather than a bare framework 403.
        $exceptions->render(function (InvalidSignatureException $e, $request) {
            return redirect()->route('home')
                ->with('error', 'That confirmation link is invalid or has expired.');
        });
    })->create();
