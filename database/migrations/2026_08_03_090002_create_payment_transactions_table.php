<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->restrictOnDelete();

            // Copied from the payment method at creation time, so a later
            // change to the method's driver never rewrites a past transaction.
            $table->string('driver', 60);

            $table->unsignedInteger('amount_minor');
            $table->char('currency', 3);

            $table->string('status', 24)->default('pending');

            $table->string('provider_reference', 120)->nullable();
            $table->string('last_provider_status', 60)->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            // MySQL/MariaDB allow unlimited NULLs through a unique index, so
            // Cash on Delivery (which never sets provider_reference) is
            // unaffected — this only stops the same provider reference being
            // recorded twice for the same driver.
            $table->unique(['driver', 'provider_reference']);

            $table->index(['order_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
