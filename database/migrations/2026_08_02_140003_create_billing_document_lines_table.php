<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_document_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('billing_documents')->cascadeOnDelete();
            $table->foreignId('menu_item_id')->nullable()->constrained('menu_items')->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->foreignId('source_line_id')->nullable()
                ->constrained('billing_document_lines')->restrictOnDelete();

            $table->unsignedSmallInteger('position')->default(0);
            $table->string('name_en', 200);
            $table->string('name_ar', 200)->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();

            $table->unsignedBigInteger('quantity_milli');
            $table->bigInteger('unit_price_minor');
            $table->bigInteger('line_net_minor');
            $table->bigInteger('line_vat_minor');
            $table->bigInteger('line_total_minor');
            $table->unsignedInteger('vat_rate_bp');
            $table->char('vat_category', 1)->default('S');
            $table->boolean('is_delivery_fee')->default(false);

            $table->timestamps();

            $table->index(['document_id', 'position']);
            $table->index('source_line_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_document_lines');
    }
};
