<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Kept alongside the relation so history survives the account being
            // deleted — an audit trail that vanishes is not an audit trail.
            $table->string('user_name')->nullable();
            $table->string('action', 40)->index();
            $table->string('subject_type', 60)->nullable()->index();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description');
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
