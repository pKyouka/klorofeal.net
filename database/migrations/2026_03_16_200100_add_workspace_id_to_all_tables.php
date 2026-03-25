<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that receive workspace_id (all tenant-scoped business data).
     * External_barcodes is intentionally excluded — it is a global lookup table.
     */
    private array $tables = [
        'users',
        'product_categories',
        'products',
        'suppliers',
        'purchases',
        'purchase_items',
        'sales',
        'sale_items',
        'stocks',
        'stock_movements',
        'stock_opnames',
        'stock_opname_items',
        'qris_payments',
    ];

    public function up(): void
    {
        // --- Add workspace_id to every business table ---
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('workspace_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('workspaces')
                    ->cascadeOnDelete();
            });
        }

        // --- Replace globally-unique constraints with per-workspace ones ---
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->unique(['workspace_id', 'slug']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['sku']);
            $table->unique(['workspace_id', 'sku']);
        });
    }

    public function down(): void
    {
        // Restore global unique constraints
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['workspace_id', 'sku']);
            $table->unique(['sku']);
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropUnique(['workspace_id', 'slug']);
            $table->unique(['slug']);
        });

        foreach (array_reverse($this->tables) as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('workspace_id');
            });
        }
    }
};
