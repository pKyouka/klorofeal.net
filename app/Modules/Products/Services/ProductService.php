<?php

namespace App\Modules\Products\Services;

use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductCategory;
use App\Modules\Products\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
    ) {
    }

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->paginateWithFilters($filters, $perPage);
    }

    public function dataTablePage(array $filters): array
    {
        return $this->productRepository->dataTablePage($filters);
    }

    public function create(array $payload): Product
    {
        return $this->productRepository->create($payload);
    }

    public function update(Product $product, array $payload): Product
    {
        return $this->productRepository->update($product, $payload);
    }

    public function delete(Product $product): void
    {
        if (
            $product->saleItems()->exists()
            || $product->purchaseItems()->exists()
            || $product->stockMovements()->exists()
            || $product->stockOpnameItems()->exists()
        ) {
            throw ValidationException::withMessages([
                'product' => 'Cannot delete product that is already used in transactions.',
            ]);
        }

        $this->productRepository->delete($product);
    }

    /**
     * Import products from an uploaded CSV file.
     * Returns ['imported' => int, 'errors' => string[]].
     */
    public function importFromCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getPathname(), 'r');
        if ($handle === false) {
            return ['imported' => 0, 'errors' => ['Tidak dapat membaca file.']];
        }

        $imported = 0;
        $errors = [];
        $rowNum = 0;
        $headers = null;

        while (($row = fgetcsv($handle, 2000, ',')) !== false) {
            $rowNum++;

            if ($rowNum === 1) {
                $row[0] = ltrim($row[0], "\xEF\xBB\xBF"); // Strip UTF-8 BOM
                $headers = array_map(fn ($h) => strtolower(trim($h)), $row);

                $required = ['sku', 'name', 'cost_price', 'sell_price', 'stock'];
                $missing = array_diff($required, $headers);
                if (! empty($missing)) {
                    fclose($handle);

                    return ['imported' => 0, 'errors' => ['Header CSV tidak valid. Kolom yang dibutuhkan: ' . implode(', ', $missing)]];
                }
                continue;
            }

            if ($headers === null) {
                continue;
            }

            while (count($row) < count($headers)) {
                $row[] = '';
            }

            $data = array_combine($headers, array_slice($row, 0, count($headers)));

            $sku = trim($data['sku'] ?? '');
            $name = trim($data['name'] ?? '');

            if ($sku === '' && $name === '') {
                continue; // blank row
            }

            if ($sku === '') {
                $errors[] = "Baris {$rowNum}: SKU tidak boleh kosong.";
                continue;
            }

            if ($name === '') {
                $errors[] = "Baris {$rowNum}: Nama produk tidak boleh kosong.";
                continue;
            }

            $barcode = trim($data['barcode'] ?? '') ?: null;
            $categoryName = trim($data['category'] ?? $data['category_name'] ?? '');
            $costPrice = (float) str_replace(',', '.', trim($data['cost_price'] ?? '0'));
            $sellPrice = (float) str_replace(',', '.', trim($data['sell_price'] ?? '0'));
            $stock = max(0, (int) trim($data['stock'] ?? '0'));
            $isActive = ! in_array(strtolower(trim($data['is_active'] ?? '1')), ['0', 'no', 'false', 'tidak'], true);

            if (Product::where('sku', $sku)->exists()) {
                $errors[] = "Baris {$rowNum}: SKU '{$sku}' sudah ada.";
                continue;
            }

            if ($barcode !== null && Product::where('barcode', $barcode)->exists()) {
                $errors[] = "Baris {$rowNum}: Barcode '{$barcode}' sudah ada.";
                continue;
            }

            $categoryId = $categoryName !== '' ? $this->resolveOrCreateCategory($categoryName) : null;

            try {
                $this->productRepository->create([
                    'sku' => $sku,
                    'barcode' => $barcode,
                    'name' => $name,
                    'category_id' => $categoryId,
                    'cost_price' => $costPrice,
                    'sell_price' => $sellPrice,
                    'stock' => $stock,
                    'minimum_stock' => 0,
                    'is_active' => $isActive,
                ]);
                $imported++;
            } catch (\Exception) {
                $errors[] = "Baris {$rowNum} ({$name}): Gagal diimport.";
            }
        }

        fclose($handle);

        return ['imported' => $imported, 'errors' => $errors];
    }

    private function resolveOrCreateCategory(string $name): int
    {
        $existing = ProductCategory::where('name', $name)->first();
        if ($existing) {
            return $existing->id;
        }

        $slug = Str::slug($name);
        $candidate = $slug;
        $i = 2;
        while (ProductCategory::where('slug', $candidate)->exists()) {
            $candidate = $slug . '-' . $i++;
        }

        return ProductCategory::create([
            'name' => $name,
            'slug' => $candidate,
            'description' => '',
        ])->id;
    }
    }
