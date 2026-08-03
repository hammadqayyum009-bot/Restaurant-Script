<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        PaymentMethod::firstOrCreate(
            ['driver' => 'cod'],
            [
                'enabled' => true,
                'sort_order' => 0,
                'label_en' => 'Cash on Delivery',
                'label_ar' => 'الدفع عند الاستلام',
                'description_en' => 'Pay in cash when your order arrives, or at pickup.',
                'description_ar' => 'ادفع نقدًا عند وصول طلبك أو عند الاستلام من الفرع.',
                'max_order_amount_minor' => 50000,
            ],
        );
    }
}
