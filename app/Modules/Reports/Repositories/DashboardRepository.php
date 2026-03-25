<?php

namespace App\Modules\Reports\Repositories;

use App\Modules\POS\Models\Sale;
use App\Modules\Products\Models\Product;
use App\Modules\Reports\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Support\Collection;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function totalSalesToday(): float
    {
        return (float) Sale::query()
            ->whereDate('sold_at', today())
            ->sum('total_amount');
    }

    public function totalProducts(): int
    {
        return Product::query()->count();
    }

    public function lowStockCount(): int
    {
        return Product::query()
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->count();
    }

    public function lowStockItems(int $limit = 10): Collection
    {
        return Product::query()
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->orderByRaw('(minimum_stock - stock) DESC')
            ->limit($limit)
            ->get(['id', 'sku', 'name', 'stock', 'minimum_stock']);
    }

    public function recentSales(int $limit = 10): Collection
    {
        return Sale::query()
            ->with('cashier')
            ->latest('sold_at')
            ->limit($limit)
            ->get();
    }
}
