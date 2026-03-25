<?php

namespace App\Modules\Reports\Services;

use App\Modules\Reports\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Support\Carbon;

class ReportService
{
    public function __construct(
        private readonly ReportRepositoryInterface $reportRepository,
    ) {
    }

    public function salesReport(array $filters): array
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($filters);

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'summary' => $this->reportRepository->salesSummary($dateFrom, $dateTo),
            'daily_sales' => $this->reportRepository->dailySales($dateFrom, $dateTo),
            'monthly_sales' => $this->reportRepository->monthlySales(),
            'top_selling_products' => $this->reportRepository->topSellingProducts($dateFrom, $dateTo),
        ];
    }

    public function inventoryReport(): array
    {
        return [
            'stats' => $this->reportRepository->inventoryStats(),
            'stock_report' => $this->reportRepository->inventoryStockReport(),
            'low_stock_report' => $this->reportRepository->lowStockReport(),
        ];
    }

    public function purchaseReport(array $filters): array
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($filters);

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'summary' => $this->reportRepository->purchaseSummary($dateFrom, $dateTo),
            'daily_purchases' => $this->reportRepository->dailyPurchases($dateFrom, $dateTo),
            'monthly_purchases' => $this->reportRepository->monthlyPurchases(),
            'recent_purchases' => $this->reportRepository->recentPurchases($dateFrom, $dateTo),
        ];
    }

    private function resolveDateRange(array $filters): array
    {
        $dateFrom = isset($filters['date_from']) && $filters['date_from'] !== ''
            ? Carbon::parse($filters['date_from'])->toDateString()
            : now()->startOfMonth()->toDateString();

        $dateTo = isset($filters['date_to']) && $filters['date_to'] !== ''
            ? Carbon::parse($filters['date_to'])->toDateString()
            : now()->toDateString();

        return [$dateFrom, $dateTo];
    }
}
