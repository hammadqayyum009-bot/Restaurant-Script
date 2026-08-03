<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();

            $table->string('driver', 60)->index();
            $table->boolean('enabled')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            $table->string('label_en', 120)->nullable();
            $table->string('label_ar', 120)->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();

            // Encrypted at the model via an 'encrypted:array' cast. Never
            // queried on, so no index — an index on ciphertext is useless
            // and would only add a place plaintext could accidentally leak.
            $table->text('credentials')->nullable();

            $table->boolean('test_mode')->default(false);

            $table->unsignedInteger('min_order_amount_minor')->nullable();
            $table->unsignedInteger('max_order_amount_minor')->nullable();

            // JSON array of order_type values (e.g. ["delivery","pickup"]).
            // Null means no restriction.
            $table->json('allowed_order_types')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
