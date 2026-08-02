<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_documents', function (Blueprint $table) {
            $table->id();

            $table->string('document_type', 30)->index();
            $table->unsignedSmallInteger('series_year');
            $table->unsignedInteger('number')->nullable();
            $table->string('document_number', 40)->nullable();
            $table->string('status', 24)->default('draft')->index();

            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('order_number', 40)->nullable();
            $table->foreignId('parent_document_id')->nullable()
                ->constrained('billing_documents')->restrictOnDelete();

            $table->char('currency', 3);
            $table->unsignedTinyInteger('currency_exponent');
            $table->boolean('prices_include_vat');
            $table->unsignedInteger('vat_rate_bp');

            $table->bigInteger('subtotal_net_minor')->default(0);
            $table->bigInteger('vat_total_minor')->default(0);
            $table->bigInteger('grand_total_minor')->default(0);
            $table->bigInteger('rounding_adjustment_minor')->default(0);

            $table->date('issue_date')->nullable();
            $table->timestamp('issued_at')->nullable()->index();
            $table->foreignId('issued_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('issued_by_name', 120)->nullable();
            $table->date('valid_until')->nullable();

            $table->string('seller_name_en', 160)->nullable();
            $table->string('seller_name_ar', 160)->nullable();
            $table->string('seller_vat_number', 20)->nullable();
            $table->string('seller_cr_number', 40)->nullable();
            $table->text('seller_address_en')->nullable();
            $table->text('seller_address_ar')->nullable();
            $table->string('seller_phone', 40)->nullable();
            $table->string('seller_email', 160)->nullable();
            $table->string('seller_logo_path', 255)->nullable();

            $table->string('buyer_name_en', 160)->nullable();
            $table->string('buyer_name_ar', 160)->nullable();
            $table->string('buyer_vat_number', 20)->nullable();
            $table->string('buyer_cr_number', 40)->nullable();
            $table->string('buyer_phone', 40)->nullable();
            $table->string('buyer_email', 160)->nullable();
            $table->text('buyer_address_en')->nullable();
            $table->text('buyer_address_ar')->nullable();

            $table->text('qr_payload')->nullable();
            $table->string('hijri_date', 24)->nullable();
            $table->text('notes_en')->nullable();
            $table->text('notes_ar')->nullable();
            $table->text('footer_en')->nullable();
            $table->text('footer_ar')->nullable();
            $table->text('credit_reason')->nullable();
            $table->timestamp('archived_at')->nullable();

            $table->timestamps();

            $table->unique('document_number');
            $table->unique(['document_type', 'series_year', 'number'], 'bd_series_number_unique');
            $table->index(['status', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_documents');
    }
};
