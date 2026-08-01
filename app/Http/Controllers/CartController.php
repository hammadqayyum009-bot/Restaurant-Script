<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Services\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(protected Cart $cart)
    {
    }

    public function index()
    {
        return view('cart', [
            'items' => $this->cart->contents(),
            'subtotal' => $this->cart->subtotal(),
        ]);
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $item = MenuItem::where('is_available', true)->findOrFail($data['menu_item_id']);
        $this->cart->add($item, $data['quantity'] ?? 1);

        if ($request->wantsJson()) {
            return response()->json($this->state());
        }

        return back()->with('success', "{$item->name} added to your cart.");
    }

    public function update(Request $request, int $menuItemId)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:50'],
        ]);

        $this->cart->updateQuantity($menuItemId, $data['quantity']);

        if ($request->wantsJson()) {
            return response()->json($this->state());
        }

        return back();
    }

    public function remove(Request $request, int $menuItemId)
    {
        $this->cart->remove($menuItemId);

        if ($request->wantsJson()) {
            return response()->json($this->state());
        }

        return back()->with('success', 'Item removed from cart.');
    }

    public function clear(Request $request)
    {
        $this->cart->clear();

        if ($request->wantsJson()) {
            return response()->json($this->state());
        }

        return back();
    }

    protected function state(): array
    {
        return [
            'items' => $this->cart->contents(),
            'count' => $this->cart->count(),
            'subtotal' => $this->cart->subtotal(),
            'html' => view('partials.mini-cart', [
                'items' => $this->cart->contents(),
                'subtotal' => $this->cart->subtotal(),
            ])->render(),
        ];
    }
}
