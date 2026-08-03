<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_status_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payment_transaction_id')->constrained('payment_transactions')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('old_status', 24)->nullable();
            $table->string('new_status', 24);
            $table->string('source', 20);
            $table->text('note')->nullable();

            // Immutable log: created_at only, no updated_at.
            $table->timestamp('created_at')->useCurrent();

            $table->index(['payment_transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_status_logs');
    }
};
