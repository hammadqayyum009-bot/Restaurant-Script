<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Every billing route and action checks the `manage-billing` ability, never
 * `is_admin` directly. Today that ability resolves to the existing is_admin
 * flag; if a real roles/permissions system is added later, this one
 * definition changes and no call site moves.
 */
class BillingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('manage-billing', fn (User $user): bool => $user->isAdmin());
    }
}
