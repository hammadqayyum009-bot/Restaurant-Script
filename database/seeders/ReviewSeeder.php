<?php

namespace Database\Seeders;

use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        if (Review::count() > 0) {
            return;
        }

        $reviews = [
            ['name' => 'Fatima Al Suwaidi', 'rating' => 5, 'comment' => 'The lamb Mandi tastes exactly like my grandmother used to make in Sharjah. Generous portions and the rice is perfectly spiced. Our new Friday family order.'],
            ['name' => 'Yousef Al Mazrouei', 'rating' => 5, 'comment' => 'Ordered the Mixed Grill Platter for a family gathering — every skewer was smoky and tender. Delivery arrived hot and right on time.'],
            ['name' => 'Sara Abdullah', 'rating' => 4, 'comment' => 'Loved the Kunafa, easily one of the best in the city. Would appreciate a bit more spice on the shawarma next time, but overall excellent.'],
            ['name' => 'Ahmed Khalifa', 'rating' => 5, 'comment' => 'Authentic Karak Chai and the Chicken Machboos was outstanding. Feels like a proper Gulf kitchen, not a generic takeaway.'],
        ];

        foreach ($reviews as $review) {
            Review::create($review);
        }
    }
}
