<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'installed' => \App\Http\Middleware\EnsureInstalled::class,
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'active' => \App\Http\Middleware\EnsureActiveAccount::class,
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
        $exceptions->render(function (\Illuminate\Routing\Exceptions\InvalidSignatureException $e, $request) {
            return redirect()->route('home')
                ->with('error', 'That confirmation link is invalid or has expired.');
        });
    })->create();
