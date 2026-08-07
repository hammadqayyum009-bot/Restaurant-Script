<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 *
 * Price/quantity/line_total are a fixed lookup table, not computed at
 * factory-run time — App\Models\Order has no HasFactory trait (it is not in
 * this module's approved list of files to modify), so there is no
 * Order::factory() to lazily default order_id to, and no bcmath dependency
 * (not installed in every environment) to multiply price by quantity with.
 */
class OrderItemFactory extends Factory
{
    protected const OPTIONS = [
        ['price' => '9.99', 'quantity' => 1, 'line_total' => '9.99'],
        ['price' => '15.50', 'quantity' => 2, 'line_total' => '31.00'],
        ['price' => '22.00', 'quantity' => 1, 'line_total' => '22.00'],
        ['price' => '7.25', 'quantity' => 3, 'line_total' => '21.75'],
    ];

    public function definition(): array
    {
        $choice = fake()->randomElement(self::OPTIONS);

        return [
            'order_id' => OrderFactory::new(),
            'menu_item_id' => null,
            'name' => fake()->words(3, true),
            'price' => $choice['price'],
            'quantity' => $choice['quantity'],
            'line_total' => $choice['line_total'],
        ];
    }
}
