<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Payments\PaymentDriverRegistry;
use App\Services\Cart;
use App\Services\Ordering;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Checkout is two steps (Phase 4): this controller only ever creates the
 * Order and its items. Which payment method is used, and initiating that
 * payment, happens on the signed screen CheckoutPaymentController serves
 * next — order creation and payment selection are deliberately separate so
 * a customer can retry a failed payment against the same order instead of
 * placing a duplicate one.
 */
class CheckoutController extends Controller
{
    public function __construct(protected Cart $cart, protected Ordering $ordering) {}

    public function show()
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('menu.index')->with('error', 'Your cart is empty. Add some delicious dishes first!');
        }

        $subtotal = $this->cart->subtotal();

        if (! $this->ordering->meetsMinimum($subtotal)) {
            return redirect()->route('cart.index')->with('error', $this->minimumMessage($subtotal));
        }

        $orderTypes = $this->ordering->orderTypes();

        if (empty($orderTypes)) {
            return redirect()->route('menu.index')
                ->with('error', 'Online ordering is closed at the moment. Please call us to place your order.');
        }

        return view('checkout', [
            'items' => $this->cart->contents(),
            'subtotal' => $subtotal,
            'orderTypes' => $orderTypes,
            'ordering' => $this->ordering,
            'totals' => $this->ordering->totals($subtotal, $orderTypes[0]),
        ]);
    }

    public function store(Request $request, PaymentDriverRegistry $registry)
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('menu.index')->with('error', 'Your cart is empty.');
        }

        $subtotal = $this->cart->subtotal();

        if (! $this->ordering->meetsMinimum($subtotal)) {
            return redirect()->route('cart.index')->with('error', $this->minimumMessage($subtotal));
        }

        $orderTypes = $this->ordering->orderTypes();

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['required', 'string', 'max:500'],
            'order_type' => ['required', Rule::in($orderTypes)],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'order_type.in' => 'That order type is not available right now.',
        ]);

        $totals = $this->ordering->totals($subtotal, $data['order_type']);

        // payment_method is deliberately omitted here — the column's own
        // 'cash' default applies transiently until the next screen sets it
        // to the driver actually chosen, so no schema change was needed to
        // make this column optional at creation time.
        $order = Order::create([
            'user_id' => $request->user()?->id,
            'order_number' => 'ORD-'.strtoupper(Str::random(8)),
            'customer_name' => $data['customer_name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'address' => $data['address'],
            'order_type' => $data['order_type'],
            'notes' => $data['notes'] ?? null,
            'subtotal' => $totals['subtotal'],
            'delivery_fee' => $totals['delivery_fee'],
            'tax' => $totals['tax'],
            'total' => $totals['total'],
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

        if ($registry->availableFor($order)->isEmpty()) {
            // No payment method is eligible for this order at all (e.g.
            // every gateway is disabled or misconfigured, or the amount
            // falls outside every method's limits). The order already
            // exists — send the customer to tracking rather than a
            // selection screen with nothing to select.
            return redirect()->route('track.show')
                ->with('error', 'Your order '.$order->order_number.' was received, but no payment method is currently available. Please contact us to arrange payment.');
        }

        // Signed, not a plain ID-keyed route: the auto-increment order id is
        // trivially walkable, and this screen (and everything downstream of
        // it) shows the customer's own order details — same reasoning as
        // the reservation confirmation link.
        return redirect()->to(
            URL::temporarySignedRoute('checkout.payment.show', now()->addHours(6), ['order' => $order->id])
        );
    }

    protected function minimumMessage(float $subtotal): string
    {
        return 'Minimum order is '.config('site.currency').' '
            .number_format($this->ordering->minimumOrder(), 2)
            .'. Your cart is '.config('site.currency').' '.number_format($subtotal, 2).'.';
    }
}
