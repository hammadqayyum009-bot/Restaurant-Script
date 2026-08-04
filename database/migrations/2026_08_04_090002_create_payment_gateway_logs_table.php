<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_logs', function (Blueprint $table) {
            $table->id();

            // Nullable: a "test connection" call has no transaction yet.
            $table->foreignId('payment_transaction_id')->nullable()->constrained('payment_transactions')->nullOnDelete();

            $table->string('driver', 60);
            $table->string('endpoint', 255);
            $table->string('http_method', 10);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('provider_reference', 120)->nullable();

            // Redacted before this row is ever written — see MoyasarClient.
            $table->text('request_summary')->nullable();
            $table->text('response_summary')->nullable();

            // Immutable log: created_at only.
            $table->timestamp('created_at')->useCurrent();

            $table->index(['payment_transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_logs');
    }
};
