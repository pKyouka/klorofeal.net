<?php

namespace App\Modules\Inventory\Repositories\Contracts;

use App\Modules\Products\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface InventoryStockRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function adjustmentProducts(): Collection;

    public function searchAdjustmentProducts(string $keyword): Collection;

    public function findAdjustmentProductById(int $id): ?object;

    public function findProductForUpdate(int $productId): ?Product;

    public function saveProduct(Product $product): void;
}
