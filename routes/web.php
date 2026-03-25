<?php

use App\Http\Controllers\ProfileController;
use App\Modules\Inventory\Controllers\InventoryController;
use App\Modules\Inventory\Controllers\StockMovementController;
use App\Modules\POS\Controllers\SaleController;
use App\Modules\Purchase\Controllers\PurchaseController;
use App\Modules\Purchase\Controllers\SupplierController;
use App\Modules\Products\Controllers\ProductCategoryController;
use App\Modules\Products\Controllers\ProductController;
use App\Modules\Reports\Controllers\DashboardController;
use App\Modules\Reports\Controllers\ReportController;
use App\Modules\Warehouse\Controllers\StockOpnameController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('midtrans/webhook', [SaleController::class, 'midtransWebhook'])
    ->middleware('throttle:120,1')
    ->name('midtrans.webhook');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'workspace', 'role:admin,cashier,warehouse'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'workspace', 'throttle:120,1'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->middleware('demo.protect')->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->middleware('demo.protect')->name('profile.destroy');

    Route::middleware('role:admin')->group(function () {
        // Custom routes must come BEFORE resource routes to avoid {wildcard} capture
        Route::get('products/import', [ProductController::class, 'importForm'])->name('products.import.form');
        Route::get('products/import/template', [ProductController::class, 'downloadTemplate'])->name('products.import.template');
        Route::post('products/import', [ProductController::class, 'import'])->name('products.import')->middleware('throttle:10,1');
        Route::get('products/datatable', [ProductController::class, 'datatable'])->name('products.datatable');
        Route::get('products/lookup-open-food-facts', [ProductController::class, 'lookupOpenFoodFacts'])->name('products.lookup-open-food-facts')->middleware('throttle:60,1');
        Route::get('products/generate-sku', [ProductController::class, 'generateSku'])->name('products.generate-sku');
        Route::post('product-categories/bulk', [ProductCategoryController::class, 'bulkStore'])->name('product-categories.bulk-store');
        Route::post('suppliers/bulk', [SupplierController::class, 'bulkStore'])->name('suppliers.bulk-store');

        Route::resource('product-categories', ProductCategoryController::class)->except(['show']);
        Route::resource('products', ProductController::class)->except(['show']);
        Route::resource('suppliers', SupplierController::class)->except(['show']);

        Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
        Route::get('reports/purchases', [ReportController::class, 'purchases'])->name('reports.purchases');
    });

    Route::middleware('role:admin,cashier')->group(function () {
        Route::get('sales/products/search', [SaleController::class, 'searchProducts'])
            ->name('sales.products.search');
        Route::post('sales/qris/create', [SaleController::class, 'createQrisPayment'])
            ->middleware('throttle:40,1')
            ->name('sales.qris.create');
        Route::get('sales/qris/status', [SaleController::class, 'checkQrisPayment'])
            ->middleware('throttle:60,1')
            ->name('sales.qris.status');
        Route::get('sales/{sale}/receipt', [SaleController::class, 'receipt'])
            ->name('sales.receipt');
        Route::resource('sales', SaleController::class)
            ->only(['index', 'create', 'show']);
        Route::post('sales', [SaleController::class, 'store'])
            ->middleware('throttle:40,1')
            ->name('sales.store');
    });

    Route::middleware('role:admin,warehouse')->group(function () {
        Route::get('purchases/products/search', [PurchaseController::class, 'searchProducts'])
            ->name('purchases.products.search');
        Route::resource('purchases', PurchaseController::class)
            ->only(['index', 'create', 'show']);
        Route::post('purchases', [PurchaseController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('purchases.store');

        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('inventory/products/search', [InventoryController::class, 'searchProducts'])->name('inventory.products.search');
        Route::get('inventory/adjustments/create', [InventoryController::class, 'createAdjustment'])->name('inventory.adjustments.create');
        Route::post('inventory/adjustments', [InventoryController::class, 'storeAdjustment'])
            ->middleware('throttle:30,1')
            ->name('inventory.adjustments.store');
        Route::get('stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');

        Route::get('stock-opnames', [StockOpnameController::class, 'index'])->name('stock-opnames.index');
        Route::post('stock-opnames/start', [StockOpnameController::class, 'start'])
            ->middleware('throttle:10,1')
            ->name('stock-opnames.start');
        Route::get('stock-opnames/{stockOpname}', [StockOpnameController::class, 'show'])->name('stock-opnames.show');
        Route::put('stock-opnames/{stockOpname}/counts', [StockOpnameController::class, 'updateCounts'])
            ->middleware('throttle:60,1')
            ->name('stock-opnames.counts.update');
        Route::post('stock-opnames/{stockOpname}/complete', [StockOpnameController::class, 'complete'])
            ->middleware('throttle:10,1')
            ->name('stock-opnames.complete');
        Route::get('stock-opnames/{stockOpname}/report', [StockOpnameController::class, 'report'])->name('stock-opnames.report');
    });
});

require __DIR__.'/auth.php';
