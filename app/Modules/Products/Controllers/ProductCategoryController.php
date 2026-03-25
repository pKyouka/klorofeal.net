<?php

namespace App\Modules\Products\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Products\Models\ProductCategory;
use App\Modules\Products\Requests\StoreProductCategoryRequest;
use App\Modules\Products\Requests\UpdateProductCategoryRequest;
use App\Modules\Products\Services\ProductCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductCategoryController extends Controller
{
    public function __construct(
        private readonly ProductCategoryService $categoryService,
    ) {
    }

    public function index(): View
    {
        return view('product-categories.index', [
            'categories' => $this->categoryService->paginate(),
        ]);
    }

    public function create(): View
    {
        return view('product-categories.create');
    }

    public function store(StoreProductCategoryRequest $request): RedirectResponse
    {
        $this->categoryService->create($request->validated());

        return redirect()
            ->route('product-categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(ProductCategory $productCategory): View
    {
        return view('product-categories.edit', [
            'category' => $productCategory,
        ]);
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory): RedirectResponse
    {
        $this->categoryService->update($productCategory, $request->validated());

        return redirect()
            ->route('product-categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(ProductCategory $productCategory): RedirectResponse
    {
        $this->categoryService->delete($productCategory);

        return redirect()
            ->route('product-categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $rows = (array) $request->input('rows', []);
        $rows = array_values(array_filter($rows, fn ($row) => trim($row['name'] ?? '') !== ''));

        if (empty($rows)) {
            return redirect()->back()->withErrors(['rows' => 'Minimal satu baris harus diisi.']);
        }

        $result = $this->categoryService->bulkCreate($rows);

        $message = "{$result['imported']} kategori berhasil disimpan.";
        if (! empty($result['errors'])) {
            $message .= ' ' . count($result['errors']) . ' baris gagal.';
        }

        return redirect()
            ->route('product-categories.index')
            ->with('success', $message)
            ->with('import_result', $result);
    }
}
