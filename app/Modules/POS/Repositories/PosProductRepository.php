<?php

namespace App\Modules\POS\Repositories;

use App\Modules\POS\Repositories\Contracts\PosProductRepositoryInterface;
use App\Modules\Products\Models\Product;
use Illuminate\Support\Collection;

class PosProductRepository implements PosProductRepositoryInterface
{
    public function searchActive(string $keyword = '', int $limit = 20): Collection
    {
        $keyword = trim($keyword);

        if (strlen($keyword) < 2) {
            return collect();
        }

        return Product::query()
            ->where('is_active', true)
            ->where(function ($nestedQuery) use ($keyword) {
                $nestedQuery->where('name', 'like', "%{$keyword}%")
                    ->orWhere('sku', 'like', "%{$keyword}%")
                    ->orWhere('barcode', 'like', "%{$keyword}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'sku', 'barcode', 'name', 'sell_price', 'stock']);
    }

    public function findByIdsForUpdate(array $ids, bool $forUpdate = true): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $query = Product::query()->whereIn('id', $ids);
        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $query->get();
    }

    public function decreaseStock(int $productId, int $quantity): void
    {
        Product::query()
            ->where('id', $productId)
            ->decrement('stock', $quantity);
    }
}
