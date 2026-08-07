<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            // App\Models\Order has no HasFactory trait (out of this
            // module's scope to add), so a plain Order::create() call —
            // matching how every other test in this app builds one — stands
            // in for a default here.
            'order_id' => Order::create([
                'order_number' => 'ORD-'.strtoupper(Str::random(8)),
                'customer_name' => 'Test Customer',
                'phone' => '0500000000',
                'address' => 'Test address',
                'order_type' => 'delivery',
                'subtotal' => 50, 'delivery_fee' => 0, 'tax' => 0, 'total' => 50,
                'payment_method' => 'cash', 'status' => 'pending',
            ])->id,
            'payment_method_id' => PaymentMethod::factory(),
            'driver' => 'cod',
            'amount_minor' => 5000,
            'currency' => 'SAR',
            'status' => 'pending',
            'provider_reference' => null,
            'last_provider_status' => null,
            'failure_reason' => null,
            'paid_at' => null,
        ];
    }
}
