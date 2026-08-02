<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Cart;
use App\Services\Mailer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function __construct(protected Cart $cart)
    {
    }

    public function show()
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('menu.index')->with('error', 'Your cart is empty. Add some delicious dishes first!');
        }

        return view('checkout', [
            'items' => $this->cart->contents(),
            'subtotal' => $this->cart->subtotal(),
        ]);
    }

    public function store(Request $request, Mailer $mailer)
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('menu.index')->with('error', 'Your cart is empty.');
        }

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['required', 'string', 'max:500'],
            'order_type' => ['required', 'in:delivery,pickup'],
            'payment_method' => ['required', 'in:cash,card'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $subtotal = $this->cart->subtotal();
        $deliveryFee = $data['order_type'] === 'delivery' && $subtotal > 0
            ? (float) config('shop.delivery_fee')
            : 0;

        $order = Order::create([
            'user_id' => $request->user()?->id,
            'order_number' => 'ORD-'.strtoupper(Str::random(8)),
            'customer_name' => $data['customer_name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'address' => $data['address'],
            'order_type' => $data['order_type'],
            'notes' => $data['notes'] ?? null,
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'total' => $subtotal + $deliveryFee,
            'payment_method' => $data['payment_method'],
            'status' => 'pending',
        ]);

        foreach ($this->cart->contents() as $line) {
            OrderItem::create([
                'order_id' => $order->id,
                'menu_item_id' => $line['id'],
                'name' => $line['name'],
                'price' => $line['price'],
                'quantity' => $line['quantity'],
                'line_total' => $line['price'] * $line['quantity'],
            ]);
        }

        $this->cart->clear();
        $order->load('items');

        $this->notify($mailer, $order);

        return redirect()->route('checkout.success', $order->order_number);
    }


    /**
     * Emails the customer their confirmation and, when enabled, alerts the
     * restaurant. Delivery failures are swallowed by the mailer and recorded in
     * the delivery log — a broken mailbox must never lose an order.
     */
    protected function notify(Mailer $mailer, Order $order): void
    {
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
            'order_type' => ucfirst($order->order_type),
            'payment_method' => $order->payment_method === 'cash' ? 'Cash on delivery' : 'Card on delivery',
            'address' => $order->address,
            'order_items' => $itemsHtml,
        ];

        if ($order->email && config('notifications.on_order')) {
            $mailer->sendTemplate('order_placed', $order->email, $order->customer_name, $vars);
        }

        if (config('notifications.copy_admin_on_order') && config('notifications.admin_email')) {
            $mailer->sendTemplate('admin_order', config('notifications.admin_email'), null, $vars);
        }
    }

    public function success(string $orderNumber)
    {
        $order = Order::with('items')->where('order_number', $orderNumber)->firstOrFail();

        return view('checkout-success', compact('order'));
    }
}
