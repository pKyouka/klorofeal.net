<?php

namespace App\Modules\Purchase\Repositories;

use App\Modules\Products\Models\Product;
use App\Modules\Purchase\Repositories\Contracts\PurchaseProductRepositoryInterface;
use Illuminate\Support\Collection;

class PurchaseProductRepository implements PurchaseProductRepositoryInterface
{
    public function searchActive(string $keyword = '', int $limit = 20): Collection
    {
        if (strlen(trim($keyword)) < 2) {
            return collect();
        }

        return Product::query()
            ->where('is_active', true)
            ->when(trim($keyword) !== '', function ($query) use ($keyword) {
                $query->where(function ($nestedQuery) use ($keyword) {
                    $nestedQuery->where('name', 'like', "%{$keyword}%")
                        ->orWhere('sku', 'like', "%{$keyword}%")
                        ->orWhere('barcode', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'sku', 'barcode', 'name', 'cost_price', 'stock']);
    }

    public function findByIdsForUpdate(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        return Product::query()
            ->whereIn('id', $ids)
            ->lockForUpdate()
            ->get();
    }

    public function increaseStock(int $productId, int $quantity): void
    {
        Product::query()
            ->where('id', $productId)
            ->increment('stock', $quantity);
    }
}
