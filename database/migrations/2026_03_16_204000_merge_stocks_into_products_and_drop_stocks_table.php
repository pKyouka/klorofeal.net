<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'minimum_stock')) {
                $table->integer('minimum_stock')->default(0)->after('stock');
            }
        });

        if (Schema::hasTable('stocks')) {
            DB::table('stocks')
                ->select(['id', 'product_id', 'quantity', 'minimum_stock'])
                ->orderBy('id')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('products')
                            ->where('id', $row->product_id)
                            ->update([
                                'stock' => (int) $row->quantity,
                                'minimum_stock' => (int) $row->minimum_stock,
                            ]);
                    }
                });

            Schema::dropIfExists('stocks');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('stocks')) {
            Schema::create('stocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->nullable()->constrained('workspaces')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->integer('quantity')->default(0);
                $table->integer('minimum_stock')->default(0);
                $table->timestamps();
                $table->unique('product_id');
            });

            DB::table('products')
                ->select(['id', 'workspace_id', 'stock', 'minimum_stock', 'created_at', 'updated_at'])
                ->orderBy('id')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('stocks')->insert([
                            'workspace_id' => $row->workspace_id,
                            'product_id' => $row->id,
                            'quantity' => (int) $row->stock,
                            'minimum_stock' => (int) ($row->minimum_stock ?? 0),
                            'created_at' => $row->created_at ?? now(),
                            'updated_at' => $row->updated_at ?? now(),
                        ]);
                    }
                });
        }

        if (Schema::hasColumn('products', 'minimum_stock')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('minimum_stock');
            });
        }
    }
};
