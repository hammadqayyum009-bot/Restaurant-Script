<?php

namespace App\Services;

use App\Models\MenuItem;
use Illuminate\Support\Facades\Session;

class Cart
{
    protected const SESSION_KEY = 'cart';

    /**
     * Get the raw cart contents keyed by menu item id.
     */
    public function contents(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    public function add(MenuItem $item, int $quantity = 1): void
    {
        $cart = $this->contents();
        $id = (string) $item->id;

        if (isset($cart[$id])) {
            $cart[$id]['quantity'] += $quantity;
        } else {
            $cart[$id] = [
                'id' => $item->id,
                'name' => $item->name,
                'price' => (float) $item->price,
                'image' => $item->image,
                'quantity' => $quantity,
            ];
        }

        Session::put(self::SESSION_KEY, $cart);
    }

    public function updateQuantity(int $itemId, int $quantity): void
    {
        $cart = $this->contents();
        $id = (string) $itemId;

        if (! isset($cart[$id])) {
            return;
        }

        if ($quantity <= 0) {
            unset($cart[$id]);
        } else {
            $cart[$id]['quantity'] = $quantity;
        }

        Session::put(self::SESSION_KEY, $cart);
    }

    public function remove(int $itemId): void
    {
        $cart = $this->contents();
        unset($cart[(string) $itemId]);
        Session::put(self::SESSION_KEY, $cart);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function count(): int
    {
        return collect($this->contents())->sum('quantity');
    }

    public function subtotal(): float
    {
        return collect($this->contents())->sum(fn ($line) => $line['price'] * $line['quantity']);
    }

    public function isEmpty(): bool
    {
        return empty($this->contents());
    }
}
