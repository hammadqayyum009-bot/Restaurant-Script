<?php

namespace App\Providers;

use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Payments\PaymentDriverRegistry;
use App\Policies\PaymentMethodPolicy;
use App\Policies\PaymentTransactionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Every payment admin route and action checks manage-payments or
 * mark-payment-paid, never is_admin directly — same shape as
 * BillingServiceProvider's manage-billing. Both abilities resolve to
 * is_admin for now; a future roles system changes only this file, no call
 * site moves.
 */
class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentDriverRegistry::class, function ($app) {
            $registry = new PaymentDriverRegistry;

            foreach (config('payments.drivers', []) as $driverClass) {
                $registry->register($app->make($driverClass));
            }

            return $registry;
        });
    }

    public function boot(): void
    {
        Gate::define('manage-payments', fn (User $user): bool => $user->isAdmin());
        Gate::define('mark-payment-paid', fn (User $user): bool => $user->isAdmin());
        Gate::define('refund-payment', fn (User $user): bool => $user->isAdmin());

        Gate::policy(PaymentMethod::class, PaymentMethodPolicy::class);
        Gate::policy(PaymentTransaction::class, PaymentTransactionPolicy::class);
    }
}
