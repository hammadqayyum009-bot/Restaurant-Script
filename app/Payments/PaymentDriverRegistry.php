<?php

namespace App\Payments;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Payments\Contracts\PaymentDriver;
use App\Payments\Exceptions\UnknownPaymentDriverException;
use Illuminate\Support\Collection;

/**
 * Resolves drivers by identifier. Adding a future driver means calling
 * register() (done once, in PaymentServiceProvider, from config('payments.
 * drivers')) — nothing here has a switch statement over driver names.
 */
class PaymentDriverRegistry
{
    /** @var array<string, PaymentDriver> */
    protected array $drivers = [];

    public function register(PaymentDriver $driver): void
    {
        $this->drivers[$driver->identifier()] = $driver;
    }

    public function has(string $identifier): bool
    {
        return isset($this->drivers[$identifier]);
    }

    public function get(string $identifier): PaymentDriver
    {
        return $this->drivers[$identifier] ?? throw new UnknownPaymentDriverException($identifier);
    }

    /** @return array<string, PaymentDriver> */
    public function all(): array
    {
        return $this->drivers;
    }

    /**
     * Enabled, registered, configured, and eligible for this order's amount
     * and order_type — the one place a driver being disabled or missing
     * credentials is enforced, so a checkout screen (Phase 4) never needs to
     * duplicate this logic.
     *
     * @return Collection<int, PaymentMethod>
     */
    public function availableFor(Order $order): Collection
    {
        // Reads config('site.currency') — the same source Billing's
        // DocumentIssuer reads — not a separate payments.currency setting.
        // The two used to diverge silently: changing the storefront
        // currency (an ordinary admin action) never touched the old
        // Payments-only value, so a transaction could be tagged with a
        // currency the customer was never actually shown.
        $amountMinor = Money::toMinor((string) $order->total, config('site.currency'));

        return PaymentMethod::where('enabled', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(function (PaymentMethod $method) use ($order, $amountMinor) {
                if (! $this->has($method->driver)) {
                    return false;
                }

                if (! $this->get($method->driver)->isConfigured($method)) {
                    return false;
                }

                if ($method->isRestrictedToOrderType($order->order_type)) {
                    return false;
                }

                return $method->isWithinAmountLimits($amountMinor);
            })
            ->values();
    }
}
