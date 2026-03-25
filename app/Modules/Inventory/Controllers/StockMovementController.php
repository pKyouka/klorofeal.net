<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = [
            'search' => (string) $request->query('search', ''),
            'type' => (string) $request->query('type', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
        ];

        return view('stock-movements.index', [
            'movements' => $this->inventoryService->paginateMovements($filters),
            'filters' => $filters,
        ]);
    }
}
