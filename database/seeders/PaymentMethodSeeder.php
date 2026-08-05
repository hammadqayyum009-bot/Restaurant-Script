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

        // Disabled until an admin enters real Moyasar credentials — there is
        // no admin-facing "create payment method" screen, only edit, so this
        // row has to exist before the settings screen can show it at all.
        PaymentMethod::firstOrCreate(
            ['driver' => 'moyasar'],
            [
                'enabled' => false,
                'test_mode' => true,
                'sort_order' => 1,
                'label_en' => 'Card / mada / Apple Pay',
                'label_ar' => 'بطاقة / مدى / Apple Pay',
                'description_en' => 'Pay online by card, mada or Apple Pay via Moyasar.',
                'description_ar' => 'ادفع عبر الإنترنت بالبطاقة أو مدى أو Apple Pay عبر ميسر.',
            ],
        );
    }
}
