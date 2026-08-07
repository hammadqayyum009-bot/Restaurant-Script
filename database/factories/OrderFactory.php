<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_number' => 'ORD-'.strtoupper(Str::random(8)),
            'customer_name' => fake()->name(),
            'phone' => fake()->numerify('05########'),
            'email' => fake()->safeEmail(),
            'address' => fake()->address(),
            'order_type' => 'delivery',
            'notes' => null,
            'subtotal' => 0,
            'delivery_fee' => 0,
            'tax' => 0,
            'total' => 0,
            'payment_method' => 'cash',
            'status' => 'pending',
        ];
    }
}
