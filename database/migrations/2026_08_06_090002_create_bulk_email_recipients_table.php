<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_email_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulk_email_job_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('email');
            $table->string('status')->default('pending')->index(); // pending | sent | failed
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            // Every chunk pull is "give me N pending rows for this job,
            // oldest first" — this is exactly that access path.
            $table->index(['bulk_email_job_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_email_recipients');
    }
};
