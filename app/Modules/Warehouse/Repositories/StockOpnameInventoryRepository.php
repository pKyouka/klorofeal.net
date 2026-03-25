<?php

namespace App\Modules\Warehouse\Repositories;

use App\Modules\Products\Models\Product;
use App\Modules\Warehouse\Repositories\Contracts\StockOpnameInventoryRepositoryInterface;
use Illuminate\Support\Collection;

class StockOpnameInventoryRepository implements StockOpnameInventoryRepositoryInterface
{
    public function allProductsForSession(): Collection
    {
        return Product::query()
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'stock']);
    }

    public function findProductsForUpdate(array $productIds): Collection
    {
        if ($productIds === []) {
            return collect();
        }

        return Product::query()
            ->whereIn('id', $productIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    public function saveProduct(Product $product): void
    {
        $product->save();
    }
}
