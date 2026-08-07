<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_document_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('billing_documents')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // Kept alongside the relation so history survives the account being
            // deleted — an audit trail that vanishes is not an audit trail.
            $table->string('user_name', 120)->nullable();
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->text('reason')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index('document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_document_events');
    }
};
