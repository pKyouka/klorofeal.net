<?php

namespace App\Providers;

use App\Modules\Inventory\Repositories\Contracts\InventoryStockRepositoryInterface;
use App\Modules\Inventory\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Modules\Inventory\Repositories\InventoryStockRepository;
use App\Modules\Inventory\Repositories\StockMovementRepository;
use App\Modules\POS\Repositories\Contracts\PosProductRepositoryInterface;
use App\Modules\POS\Repositories\Contracts\SaleRepositoryInterface;
use App\Modules\POS\Repositories\PosProductRepository;
use App\Modules\POS\Repositories\SaleRepository;
use App\Modules\Purchase\Repositories\Contracts\PurchaseProductRepositoryInterface;
use App\Modules\Purchase\Repositories\Contracts\PurchaseRepositoryInterface;
use App\Modules\Purchase\Repositories\Contracts\SupplierRepositoryInterface;
use App\Modules\Purchase\Repositories\PurchaseProductRepository;
use App\Modules\Purchase\Repositories\PurchaseRepository;
use App\Modules\Purchase\Repositories\SupplierRepository;
use App\Modules\Products\Repositories\Contracts\ProductCategoryRepositoryInterface;
use App\Modules\Products\Repositories\Contracts\ProductRepositoryInterface;
use App\Modules\Products\Repositories\ProductCategoryRepository;
use App\Modules\Products\Repositories\ProductRepository;
use App\Modules\Reports\Repositories\Contracts\DashboardRepositoryInterface;
use App\Modules\Reports\Repositories\Contracts\ReportRepositoryInterface;
use App\Modules\Reports\Repositories\DashboardRepository;
use App\Modules\Reports\Repositories\ReportRepository;
use App\Modules\Warehouse\Repositories\Contracts\StockOpnameInventoryRepositoryInterface;
use App\Modules\Warehouse\Repositories\Contracts\StockOpnameRepositoryInterface;
use App\Modules\Warehouse\Repositories\StockOpnameInventoryRepository;
use App\Modules\Warehouse\Repositories\StockOpnameRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProductCategoryRepositoryInterface::class, ProductCategoryRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(PosProductRepositoryInterface::class, PosProductRepository::class);
        $this->app->bind(SaleRepositoryInterface::class, SaleRepository::class);
        $this->app->bind(SupplierRepositoryInterface::class, SupplierRepository::class);
        $this->app->bind(PurchaseProductRepositoryInterface::class, PurchaseProductRepository::class);
        $this->app->bind(PurchaseRepositoryInterface::class, PurchaseRepository::class);
        $this->app->bind(InventoryStockRepositoryInterface::class, InventoryStockRepository::class);
        $this->app->bind(StockMovementRepositoryInterface::class, StockMovementRepository::class);
        $this->app->bind(StockOpnameRepositoryInterface::class, StockOpnameRepository::class);
        $this->app->bind(StockOpnameInventoryRepositoryInterface::class, StockOpnameInventoryRepository::class);
        $this->app->bind(DashboardRepositoryInterface::class, DashboardRepository::class);
        $this->app->bind(ReportRepositoryInterface::class, ReportRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
