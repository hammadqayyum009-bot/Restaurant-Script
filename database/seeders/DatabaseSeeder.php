<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (User::count() === 0) {
            User::create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'is_admin' => true,
                'is_active' => true,
            ]);
        }

        $this->call([
            MenuSeeder::class,
            PageSeeder::class,
            ReviewSeeder::class,
            PaymentMethodSeeder::class,
        ]);
    }
}
