<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            // Drives the verify-on-page-load cooldown: only re-checked once
            // pending for a minimum age, and not again within the cooldown
            // window if the page is refreshed repeatedly.
            $table->timestamp('last_verified_at')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn('last_verified_at');
        });
    }
};
