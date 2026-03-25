<?php

namespace App\Modules\Purchase\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Purchase\Requests\StorePurchaseRequest;
use App\Modules\Purchase\Services\PurchaseService;
use App\Modules\Purchase\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseService $purchaseService,
        private readonly SupplierService $supplierService,
    ) {
    }

    public function index(): View
    {
        return view('purchases.index', [
            'purchases' => $this->purchaseService->paginate(),
        ]);
    }

    public function create(): View
    {
        return view('purchases.create', [
            'suppliers' => $this->supplierService->all(),
            'products' => [],
        ]);
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $keyword = (string) $request->query('q', '');
        $products = $this->purchaseService->searchProducts($keyword);

        return response()->json([
            'data' => $products,
        ]);
    }

    public function store(StorePurchaseRequest $request): RedirectResponse
    {
        $purchase = $this->purchaseService->createPurchase(
            supplierId: (int) $request->validated('supplier_id'),
            items: $request->validated('items', []),
        );

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('success', 'Purchase transaction recorded successfully.');
    }

    public function show(Purchase $purchase): View
    {
        return view('purchases.show', [
            'purchase' => $purchase->load(['items.product', 'supplier']),
        ]);
    }
}
