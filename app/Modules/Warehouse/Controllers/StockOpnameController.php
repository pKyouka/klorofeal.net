<?php

namespace App\Modules\Warehouse\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Warehouse\Models\StockOpname;
use App\Modules\Warehouse\Requests\UpdateStockOpnameRequest;
use App\Modules\Warehouse\Services\StockOpnameService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockOpnameController extends Controller
{
    public function __construct(
        private readonly StockOpnameService $stockOpnameService,
    ) {
    }

    public function index(): View
    {
        return view('stock-opnames.index', [
            'sessions' => $this->stockOpnameService->paginate(),
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        $session = $this->stockOpnameService->startSession((int) $request->user()->id);

        return redirect()
            ->route('stock-opnames.show', $session)
            ->with('success', 'Stock opname session started. Please input physical stock counts.');
    }

    public function show(StockOpname $stockOpname): View
    {
        $session = $this->stockOpnameService->getSession($stockOpname);

        return view('stock-opnames.show', [
            'session' => $session,
            'discrepancyCount' => $session->items->where('difference', '!=', 0)->count(),
        ]);
    }

    public function updateCounts(UpdateStockOpnameRequest $request, StockOpname $stockOpname): RedirectResponse
    {
        $this->stockOpnameService->updateCounts($stockOpname, $request->validated('items', []));

        return redirect()
            ->route('stock-opnames.show', $stockOpname)
            ->with('success', 'Physical stock counts updated successfully.');
    }

    public function complete(StockOpname $stockOpname): RedirectResponse
    {
        $this->stockOpnameService->completeSession($stockOpname);

        return redirect()
            ->route('stock-opnames.report', $stockOpname)
            ->with('success', 'Stock opname session completed and discrepancies applied to inventory.');
    }

    public function report(StockOpname $stockOpname): View
    {
        $session = $this->stockOpnameService->getSession($stockOpname);

        return view('stock-opnames.report', [
            'session' => $session,
            'discrepancyItems' => $this->stockOpnameService->discrepancyItems($stockOpname),
        ]);
    }
}
