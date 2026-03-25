<?php

namespace App\Modules\Reports\Repositories;

use App\Modules\Products\Models\Product;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Reports\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportRepository implements ReportRepositoryInterface
{
    public function salesSummary(string $dateFrom, string $dateTo): array
    {
        $query = DB::table('sales')
            ->whereDate('sold_at', '>=', $dateFrom)
            ->whereDate('sold_at', '<=', $dateTo);

        return [
            'transaction_count' => (int) $query->count(),
            'total_sales' => (float) $query->sum('total_amount'),
        ];
    }

    public function dailySales(string $dateFrom, string $dateTo): Collection
    {
        return DB::table('sales')
            ->selectRaw('DATE(sold_at) as date, COUNT(*) as transactions, SUM(total_amount) as total_amount')
            ->whereDate('sold_at', '>=', $dateFrom)
            ->whereDate('sold_at', '<=', $dateTo)
            ->groupByRaw('DATE(sold_at)')
            ->orderBy('date')
            ->get();
    }

    public function monthlySales(int $months = 12): Collection
    {
        return DB::table('sales')
            ->selectRaw("DATE_FORMAT(sold_at, '%Y-%m') as month, COUNT(*) as transactions, SUM(total_amount) as total_amount")
            ->whereDate('sold_at', '>=', now()->subMonths($months - 1)->startOfMonth()->toDateString())
            ->groupByRaw("DATE_FORMAT(sold_at, '%Y-%m')")
            ->orderBy('month')
            ->get();
    }

    public function topSellingProducts(string $dateFrom, string $dateTo, int $limit = 10): Collection
    {
        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->selectRaw('products.id as product_id, products.sku, products.name, SUM(sale_items.qty) as total_qty, SUM(sale_items.subtotal) as total_amount')
            ->whereDate('sales.sold_at', '>=', $dateFrom)
            ->whereDate('sales.sold_at', '<=', $dateTo)
            ->groupBy('products.id', 'products.sku', 'products.name')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get();
    }

    public function inventoryStockReport(int $perPage = 20): LengthAwarePaginator
    {
        return Product::query()
            ->with('category')
            ->orderByRaw('(stock - minimum_stock) ASC')
            ->paginate($perPage);
    }

    public function lowStockReport(int $limit = 20): Collection
    {
        return Product::query()
            ->with('category')
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->orderByRaw('(minimum_stock - stock) DESC')
            ->limit($limit)
            ->get();
    }

    public function inventoryStats(): array
    {
        return [
            'total_tracked' => (int) Product::count(),
            'low_stock_count' => (int) Product::whereColumn('stock', '<=', 'minimum_stock')->where('stock', '>', 0)->count(),
            'out_of_stock_count' => (int) Product::where('stock', 0)->count(),
            'safe_stock_count' => (int) Product::whereColumn('stock', '>', 'minimum_stock')->count(),
        ];
    }

    public function purchaseSummary(string $dateFrom, string $dateTo): array
    {
        $query = DB::table('purchases')
            ->whereDate('purchased_at', '>=', $dateFrom)
            ->whereDate('purchased_at', '<=', $dateTo);

        return [
            'transaction_count' => (int) $query->count(),
            'total_purchases' => (float) $query->sum('total_amount'),
        ];
    }

    public function dailyPurchases(string $dateFrom, string $dateTo): Collection
    {
        return DB::table('purchases')
            ->selectRaw('DATE(purchased_at) as date, COUNT(*) as transactions, SUM(total_amount) as total_amount')
            ->whereDate('purchased_at', '>=', $dateFrom)
            ->whereDate('purchased_at', '<=', $dateTo)
            ->groupByRaw('DATE(purchased_at)')
            ->orderBy('date')
            ->get();
    }

    public function monthlyPurchases(int $months = 12): Collection
    {
        return DB::table('purchases')
            ->selectRaw("DATE_FORMAT(purchased_at, '%Y-%m') as month, COUNT(*) as transactions, SUM(total_amount) as total_amount")
            ->whereDate('purchased_at', '>=', now()->subMonths($months - 1)->startOfMonth()->toDateString())
            ->groupByRaw("DATE_FORMAT(purchased_at, '%Y-%m')")
            ->orderBy('month')
            ->get();
    }

    public function recentPurchases(string $dateFrom, string $dateTo, int $perPage = 20): LengthAwarePaginator
    {
        return Purchase::query()
            ->with('supplier')
            ->whereDate('purchased_at', '>=', $dateFrom)
            ->whereDate('purchased_at', '<=', $dateTo)
            ->latest('purchased_at')
            ->paginate($perPage)
            ->withQueryString();
    }
}
