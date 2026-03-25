<?php

namespace App\Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Reports\Requests\ReportFilterRequest;
use App\Modules\Reports\Services\ReportService;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
    ) {
    }

    public function sales(ReportFilterRequest $request): View
    {
        $filters = $request->validated();

        return view('reports.sales', $this->reportService->salesReport($filters));
    }

    public function inventory(): View
    {
        return view('reports.inventory', $this->reportService->inventoryReport());
    }

    public function purchases(ReportFilterRequest $request): View
    {
        $filters = $request->validated();

        return view('reports.purchases', $this->reportService->purchaseReport($filters));
    }
}
