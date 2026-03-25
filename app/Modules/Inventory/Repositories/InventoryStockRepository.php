<?php

namespace App\Modules\Inventory\Repositories;

use App\Modules\Inventory\Repositories\Contracts\InventoryStockRepositoryInterface;
use App\Modules\Products\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class InventoryStockRepository implements InventoryStockRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $lowStockOnly = (bool) ($filters['low_stock'] ?? false);

        return Product::query()
            ->with('category')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($lowStockOnly, fn ($query) => $query->whereColumn('stock', '<=', 'minimum_stock'))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function adjustmentProducts(): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'stock']);
    }

    public function searchAdjustmentProducts(string $keyword): Collection
    {
        if (strlen(trim($keyword)) < 2) {
            return collect();
        }

        return Product::query()
            ->where('is_active', true)
            ->where(function ($query) use ($keyword) {
                $query->where('name', 'like', "%{$keyword}%")
                    ->orWhere('sku', 'like', "%{$keyword}%")
                    ->orWhere('barcode', 'like', "%{$keyword}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'sku', 'barcode', 'name', 'stock']);
    }

    public function findAdjustmentProductById(int $id): ?object
    {
        return Product::query()
            ->where('id', $id)
            ->where('is_active', true)
            ->first(['id', 'sku', 'barcode', 'name', 'stock']);
    }

    public function findProductForUpdate(int $productId): ?Product
    {
        return Product::query()
            ->where('id', $productId)
            ->lockForUpdate()
            ->first();
    }

    public function saveProduct(Product $product): void
    {
        $product->save();
    }
}
