<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'driver' => 'cod',
            'enabled' => true,
            'sort_order' => 0,
            'label_en' => 'Cash on Delivery',
            'label_ar' => 'الدفع عند الاستلام',
            'description_en' => 'Pay in cash when your order arrives.',
            'description_ar' => 'ادفع نقدًا عند وصول طلبك.',
            'credentials' => null,
            'test_mode' => false,
            'min_order_amount_minor' => null,
            'max_order_amount_minor' => null,
            'allowed_order_types' => null,
        ];
    }
}
