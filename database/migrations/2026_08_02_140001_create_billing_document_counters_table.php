<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_document_counters', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 30);
            $table->unsignedSmallInteger('series_year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['document_type', 'series_year'], 'bdc_type_year_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_document_counters');
    }
};
