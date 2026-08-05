<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            // Running total, not a single "last refund" — a transaction can
            // be partially refunded more than once. Validated against
            // amount_minor before ever being incremented; see
            // PaymentTransactionController::refund().
            $table->unsignedInteger('refunded_amount_minor')->default(0)->after('last_verified_at');
            $table->timestamp('refunded_at')->nullable()->after('refunded_amount_minor');
            $table->text('refund_reason')->nullable()->after('refunded_at');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn(['refunded_amount_minor', 'refunded_at', 'refund_reason']);
        });
    }
};
