<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_email_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->longText('body');
            // selected | all_customers | all_users | custom — the audience
            // choice made on the compose screen, kept only for display.
            $table->string('audience');
            $table->string('status')->default('pending')->index(); // pending | completed
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_email_jobs');
    }
};
