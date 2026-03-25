<?php

namespace App\Modules\Purchase\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Purchase\Models\Supplier;
use App\Modules\Purchase\Requests\StoreSupplierRequest;
use App\Modules\Purchase\Requests\UpdateSupplierRequest;
use App\Modules\Purchase\Services\SupplierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(
        private readonly SupplierService $supplierService,
    ) {
    }

    public function index(): View
    {
        return view('suppliers.index', [
            'suppliers' => $this->supplierService->paginate(),
        ]);
    }

    public function create(): View
    {
        return view('suppliers.create');
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $this->supplierService->create($request->validated());

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier created successfully.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', [
            'supplier' => $supplier,
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $this->supplierService->update($supplier, $request->validated());

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->supplierService->delete($supplier);

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $rows = (array) $request->input('rows', []);
        $rows = array_values(array_filter($rows, fn ($row) => trim($row['name'] ?? '') !== ''));

        if (empty($rows)) {
            return redirect()->back()->withErrors(['rows' => 'Minimal satu baris harus diisi.']);
        }

        $result = $this->supplierService->bulkCreate($rows);

        $message = "{$result['imported']} supplier berhasil disimpan.";
        if (! empty($result['errors'])) {
            $message .= ' ' . count($result['errors']) . ' baris gagal.';
        }

        return redirect()
            ->route('suppliers.index')
            ->with('success', $message)
            ->with('import_result', $result);
    }
}
