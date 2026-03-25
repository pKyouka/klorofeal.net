<?php

namespace App\Modules\Reports\Repositories\Contracts;

use Illuminate\Support\Collection;

interface DashboardRepositoryInterface
{
    public function totalSalesToday(): float;

    public function totalProducts(): int;

    public function lowStockCount(): int;

    public function lowStockItems(int $limit = 10): Collection;

    public function recentSales(int $limit = 10): Collection;
}
