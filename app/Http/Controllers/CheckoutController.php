<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Cart;
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

    public function store(Request $request)
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
        $deliveryFee = $data['order_type'] === 'delivery' && $subtotal > 0 ? 10 : 0;

        $order = Order::create([
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

        return redirect()->route('checkout.success', $order->order_number);
    }

    public function success(string $orderNumber)
    {
        $order = Order::with('items')->where('order_number', $orderNumber)->firstOrFail();

        return view('checkout-success', compact('order'));
    }
}
