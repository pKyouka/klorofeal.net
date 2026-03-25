<?php

namespace App\Modules\Reports\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ReportRepositoryInterface
{
    public function salesSummary(string $dateFrom, string $dateTo): array;

    public function dailySales(string $dateFrom, string $dateTo): Collection;

    public function monthlySales(int $months = 12): Collection;

    public function topSellingProducts(string $dateFrom, string $dateTo, int $limit = 10): Collection;

    public function inventoryStockReport(int $perPage = 20): LengthAwarePaginator;

    public function inventoryStats(): array;

    public function lowStockReport(int $limit = 20): Collection;

    public function purchaseSummary(string $dateFrom, string $dateTo): array;

    public function dailyPurchases(string $dateFrom, string $dateTo): Collection;

    public function monthlyPurchases(int $months = 12): Collection;

    public function recentPurchases(string $dateFrom, string $dateTo, int $perPage = 20): LengthAwarePaginator;
}
