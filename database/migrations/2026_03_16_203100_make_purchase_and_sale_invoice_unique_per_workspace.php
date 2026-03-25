<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique('purchases_invoice_number_unique');
            $table->unique(['workspace_id', 'invoice_number']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_invoice_number_unique');
            $table->unique(['workspace_id', 'invoice_number']);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['workspace_id', 'invoice_number']);
            $table->unique('invoice_number');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique(['workspace_id', 'invoice_number']);
            $table->unique('invoice_number');
        });
    }
};
