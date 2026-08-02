<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Lets a customer check an order without an account.
 *
 * The order number alone is not enough — the phone or email the order was
 * placed with has to match too, so a guessed order number reveals nothing.
 */
class OrderTrackingController extends Controller
{
    /** The customer-visible journey, in order. */
    public const STEPS = [
        'pending' => 'Order received',
        'confirmed' => 'Confirmed by the kitchen',
        'preparing' => 'Being prepared',
        'out_for_delivery' => 'Out for delivery',
        'completed' => 'Delivered',
    ];

    public function show()
    {
        return view('track', ['order' => null, 'steps' => self::STEPS]);
    }

    public function find(Request $request)
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:40'],
            'contact' => ['required', 'string', 'max:150'],
        ], [
            'contact.required' => 'Enter the phone number or email you ordered with.',
        ]);

        // Slow down anyone trying to walk the order-number space.
        $key = 'track|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            return back()->withInput()->withErrors([
                'order_number' => 'Too many attempts. Please try again in a minute.',
            ]);
        }

        RateLimiter::hit($key, 60);

        $contact = trim($data['contact']);
        $digits = preg_replace('/\D+/', '', $contact);

        $order = Order::with('items')
            ->where('order_number', trim($data['order_number']))
            ->where(function ($query) use ($contact, $digits) {
                $query->whereRaw('lower(email) = ?', [Str::lower($contact)]);

                if ($digits !== '') {
                    // Phone numbers are typed with spaces, dashes and +, so the
                    // comparison is on digits only.
                    $query->orWhereRaw(
                        "replace(replace(replace(replace(phone, ' ', ''), '-', ''), '+', ''), '(', '') LIKE ?",
                        ['%'.$digits]
                    );
                }
            })
            ->first();

        if (! $order) {
            return back()->withInput()->withErrors([
                'order_number' => 'No order matches those details. Check the order number and the phone or email you used.',
            ]);
        }

        RateLimiter::clear($key);

        return view('track', [
            'order' => $order,
            'steps' => self::STEPS,
        ]);
    }
}
