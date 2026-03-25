<?php

namespace App\Modules\Reports\Services;

use App\Modules\Reports\Repositories\Contracts\DashboardRepositoryInterface;

class DashboardService
{
    public function __construct(
        private readonly DashboardRepositoryInterface $dashboardRepository,
    ) {
    }

    public function metrics(): array
    {
        return [
            'total_sales_today' => $this->dashboardRepository->totalSalesToday(),
            'total_products' => $this->dashboardRepository->totalProducts(),
            'low_stock_count' => $this->dashboardRepository->lowStockCount(),
            'low_stock_items' => $this->dashboardRepository->lowStockItems(),
            'recent_sales' => $this->dashboardRepository->recentSales(),
        ];
    }
}
