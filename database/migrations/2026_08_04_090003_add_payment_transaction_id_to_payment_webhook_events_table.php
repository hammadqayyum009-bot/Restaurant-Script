<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_webhook_events', function (Blueprint $table) {
            // Nullable: an event that never resolves to a known transaction
            // (unrecognized reference, or the webhook arriving before our own
            // row commits) is still stored, just uncorrelated — never dropped,
            // never crashes. Set at processing time once matched.
            $table->foreignId('payment_transaction_id')->nullable()->after('id')
                ->constrained('payment_transactions')->nullOnDelete();
            $table->index(['payment_transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::table('payment_webhook_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_transaction_id');
        });
    }
};
