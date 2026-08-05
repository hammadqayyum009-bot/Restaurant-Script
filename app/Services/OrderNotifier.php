<?php

namespace App\Services;

use App\Models\Order;
use App\Payments\PaymentDriverRegistry;
use Illuminate\Contracts\Container\Container;

/**
 * The "order placed" customer/admin email, extracted out of
 * CheckoutController (Phase 4) because it is now dispatched from two
 * Payments-module classes — CashOnDeliveryDriver::initiate() and
 * PaymentVerificationService's Paid transition closure — neither of which
 * should depend on an HTTP controller. The content and escaping rules are
 * unchanged from the original CheckoutController::notify().
 */
class OrderNotifier
{
    /**
     * PaymentDriverRegistry is resolved lazily (via the container, not a
     * constructor parameter) deliberately: PaymentServiceProvider builds
     * every registered driver — including CashOnDeliveryDriver, which
     * depends on this class — while constructing the registry singleton
     * itself. Taking PaymentDriverRegistry as a constructor dependency here
     * would make that resolution circular (registry -> CashOnDeliveryDriver
     * -> OrderNotifier -> registry, before the first registry has finished
     * building) and recurse until the container runs out of stack. Calling
     * app(PaymentDriverRegistry::class) instead defers the lookup to
     * notifyPlaced() time, long after boot has finished.
     */
    public function __construct(
        protected Mailer $mailer,
        protected Container $container,
    ) {}

    /**
     * Delivery failures are swallowed by the mailer and recorded in the
     * delivery log — a broken mailbox must never lose an order. Callers are
     * responsible for only calling this once per order (both call sites
     * guard on the order actually transitioning out of "pending").
     */
    public function notifyPlaced(Order $order): void
    {
        $order->loadMissing('items');

        $itemsHtml = '<ul>';
        foreach ($order->items as $item) {
            $itemsHtml .= '<li>'.e($item->quantity.' × '.$item->name).' — '
                .e(config('site.currency').' '.number_format((float) $item->line_total, 2)).'</li>';
        }
        $itemsHtml .= '</ul>';

        $vars = [
            'name' => $order->customer_name,
            'phone' => $order->phone,
            'order_number' => $order->order_number,
            'total' => number_format((float) $order->total, 2),
            'tax' => number_format((float) $order->tax, 2),
            'order_type' => ucfirst($order->order_type),
            'payment_method' => $this->paymentMethodLabel($order),
            'address' => $order->address,
            'order_items' => $itemsHtml,
        ];

        // order_items is a pre-built <ul> (each item already escaped where
        // it's assembled above) — it must render as the list it is, not get
        // HTML-escaped a second time into visible tag text.
        if ($order->email && config('notifications.on_order')) {
            $this->mailer->dispatchTemplate('order_placed', $order->email, $order->customer_name, $vars, rawKeys: ['order_items']);
        }

        if (config('notifications.copy_admin_on_order') && config('notifications.admin_email')) {
            $this->mailer->dispatchTemplate('admin_order', config('notifications.admin_email'), null, $vars, rawKeys: ['order_items']);
        }
    }

    protected function paymentMethodLabel(Order $order): string
    {
        $registry = $this->container->make(PaymentDriverRegistry::class);

        if ($registry->has($order->payment_method)) {
            return $registry->get($order->payment_method)->displayInfo()->label;
        }

        return ucfirst($order->payment_method);
    }
}
