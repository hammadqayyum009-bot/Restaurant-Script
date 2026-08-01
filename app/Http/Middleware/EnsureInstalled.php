<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstalled
{
    /**
     * Redirects to the setup wizard until storage/installed.lock exists.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! file_exists(storage_path('installed.lock'))) {
            return redirect()->route('install.welcome');
        }

        return $next($request);
    }
}
