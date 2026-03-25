<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Requests\StoreStockAdjustmentRequest;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = [
            'search' => (string) $request->query('search', ''),
            'low_stock' => $request->boolean('low_stock'),
        ];

        return view('inventory.index', [
            'stocks' => $this->inventoryService->paginateStocks($filters),
            'filters' => $filters,
        ]);
    }

    public function createAdjustment(Request $request): View
    {
        $preselectedId = (int) $request->query('product_id', 0);
        $preselected = $preselectedId > 0
            ? $this->inventoryService->findAdjustmentProductById($preselectedId)
            : null;

        return view('inventory.adjustment', [
            'preselected' => $preselected,
        ]);
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $keyword = (string) $request->query('q', '');
        $products = $this->inventoryService->searchAdjustmentProducts($keyword);

        return response()->json(['data' => $products]);
    }

    public function storeAdjustment(StoreStockAdjustmentRequest $request): RedirectResponse
    {
        $this->inventoryService->adjustStock(
            payload: $request->validated(),
            userId: (int) $request->user()->id,
        );

        return redirect()
            ->route('inventory.index')
            ->with('success', 'Stock adjustment recorded successfully.');
    }
}
