<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('external_barcodes', function (Blueprint $table) {
            $table->id();
            $table->string('barcode', 20)->unique();
            $table->string('product_name');
            $table->string('brand')->nullable();
            $table->string('country_code', 8)->nullable();
            $table->string('source_primary', 64);
            $table->json('sources');
            $table->json('source_meta')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('source_primary');
            $table->index('product_name');
            $table->index('country_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_barcodes');
    }
};
