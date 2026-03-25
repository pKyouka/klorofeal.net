<?php

namespace App\Modules\Products\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Requests\ImportProductsRequest;
use App\Modules\Products\Requests\StoreProductRequest;
use App\Modules\Products\Requests\UpdateProductRequest;
use App\Modules\Products\Services\ProductCategoryService;
use App\Modules\Products\Services\ProductService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly ProductCategoryService $categoryService,
    ) {
    }

    public function index(Request $request): View
    {
        return view('products.index', [
            'categories' => $this->categoryService->all(),
        ]);
    }

    public function datatable(): JsonResponse
    {
        $result = $this->productService->dataTablePage([
            'start' => request()->integer('start', 0),
            'length' => request()->integer('length', 12),
            'search' => (string) request()->input('search.value', ''),
            'category_id' => request()->input('category_id'),
            'status' => request()->input('status'),
            'order_column' => request()->input('order.0.column', 0),
            'order_dir' => request()->input('order.0.dir', 'desc'),
        ]);

        return response()->json([
            'draw' => request()->integer('draw', 0),
            'recordsTotal' => $result['recordsTotal'],
            'recordsFiltered' => $result['recordsFiltered'],
            'data' => $result['data'],
        ]);
    }

    public function create(): View
    {
        return view('products.create', [
            'categories' => $this->categoryService->all(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->productService->create($request->validated());

        return redirect()
            ->route('products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product): View
    {
        return view('products.edit', [
            'product' => $product,
            'categories' => $this->categoryService->all(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->productService->update($product, $request->validated());

        return redirect()
            ->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->productService->delete($product);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }

    public function importForm(): View
    {
        return view('products.import');
    }

    public function import(ImportProductsRequest $request): RedirectResponse
    {
        $result = $this->productService->importFromCsv($request->file('file'));

        return redirect()
            ->route('products.import.form')
            ->with('import_result', $result);
    }

    public function downloadTemplate(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['sku', 'barcode', 'name', 'category', 'cost_price', 'sell_price', 'stock', 'is_active']);
            fputcsv($handle, ['SKU001', '', 'Contoh Produk 1', 'Minuman', '5000', '8000', '100', '1']);
            fputcsv($handle, ['SKU002', '8991000010001', 'Contoh Produk 2', 'Snack', '3000', '5000', '50', '1']);
            fclose($handle);
        }, 'produk_template.csv', ['Content-Type' => 'text/csv']);
    }

    public function lookupOpenFoodFacts(Request $request): JsonResponse
    {
        $barcode = preg_replace('/\s+/', '', (string) $request->query('barcode', ''));

        if ($barcode === '' || ! preg_match('/^\d{6,20}$/', $barcode)) {
            return response()->json([
                'found' => false,
                'message' => 'Barcode tidak valid.',
            ], 422);
        }

        $url = "https://world.openfoodfacts.org/api/v0/product/{$barcode}.json";

        try {
            $response = Http::acceptJson()
                ->connectTimeout(4)
                ->timeout(8)
                ->retry(1, 150)
                ->get($url);
        } catch (ConnectionException) {
            // Local Windows environments sometimes miss CA bundles; fallback keeps feature usable.
            try {
                $response = Http::acceptJson()
                    ->connectTimeout(4)
                    ->timeout(8)
                    ->retry(1, 150)
                    ->withOptions(['verify' => false])
                    ->get($url);
            } catch (ConnectionException) {
                return response()->json([
                    'found' => false,
                    'message' => 'Layanan OpenFoodFacts sedang lambat atau tidak bisa diakses. Lanjut isi produk secara manual.',
                ], 504);
            }
        }

        if (! $response->ok()) {
            return response()->json([
                'found' => false,
                'message' => 'Gagal menghubungi OpenFoodFacts. Coba lagi.',
            ], 502);
        }

        $payload = $response->json();
        if ((int) data_get($payload, 'status') !== 1) {
            return response()->json([
                'found' => false,
                'message' => 'Produk tidak ditemukan di OpenFoodFacts.',
            ]);
        }

        $product = (array) data_get($payload, 'product', []);
        $name = trim((string) (data_get($product, 'product_name') ?: data_get($product, 'product_name_en') ?: ''));
        $brand = trim((string) data_get($product, 'brands', ''));
        $quantity = trim((string) data_get($product, 'quantity', ''));
        $imageUrl = trim((string) (data_get($product, 'image_front_small_url') ?: data_get($product, 'image_front_url') ?: ''));
        $categoryText = trim((string) (data_get($product, 'categories') ?: data_get($product, 'categories_en') ?: ''));

        $suggestedCategory = $this->suggestCategoryFromText($categoryText);

        return response()->json([
            'found' => true,
            'product' => [
                'barcode' => $barcode,
                'name' => $name,
                'brand' => $brand,
                'quantity' => $quantity,
                'image_url' => $imageUrl,
                'categories' => $categoryText,
                'suggested_category_id' => $suggestedCategory?->id,
                'suggested_category_name' => $suggestedCategory?->name,
                'source' => 'OpenFoodFacts',
            ],
        ]);
    }

    public function generateSku(Request $request): JsonResponse
    {
        // Build category prefix: first 3 uppercase letters of category name
        $catPrefix = 'GEN';
        $categoryId = $request->query('category_id');
        if ($categoryId) {
            $category = $this->categoryService->all()->firstWhere('id', (int) $categoryId);
            if ($category) {
                $letters = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $category->name));
                $catPrefix = str_pad(substr($letters, 0, 3), 3, 'X');
            }
        }

        // Build name code: first 4 uppercase alphanumeric characters of product name
        $nameCode = 'PROD';
        $name = trim((string) $request->query('name', ''));
        if ($name !== '') {
            $cleaned = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name));
            $nameCode = str_pad(substr($cleaned, 0, 4), 4, 'X');
        }

        $prefix = "{$catPrefix}-{$nameCode}";

        // Find the highest existing sequence for this prefix
        $maxSeq = 0;
        foreach (Product::where('sku', 'like', "{$prefix}-%")->pluck('sku') as $sku) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/', $sku, $m)) {
                $maxSeq = max($maxSeq, (int) $m[1]);
            }
        }

        $nextSeq = str_pad((string) ($maxSeq + 1), 3, '0', STR_PAD_LEFT);

        return response()->json(['sku' => "{$prefix}-{$nextSeq}"]);
    }

    private function suggestCategoryFromText(?string $categoryText): ?object
    {
        $normalized = Str::of((string) $categoryText)->lower()->value();
        if ($normalized === '') {
            return null;
        }

        foreach ($this->categoryService->all() as $category) {
            $name = Str::of((string) $category->name)->lower()->value();
            $slug = Str::of(Str::slug((string) $category->name, '-'))->lower()->value();

            if (($name !== '' && str_contains($normalized, $name)) || ($slug !== '' && str_contains($normalized, $slug))) {
                return (object) [
                    'id' => $category->id,
                    'name' => $category->name,
                ];
            }
        }

        return null;
    }
}
