<?php

namespace App\Modules\Warehouse\Repositories\Contracts;

use App\Modules\Products\Models\Product;
use Illuminate\Support\Collection;

interface StockOpnameInventoryRepositoryInterface
{
    public function allProductsForSession(): Collection;

    public function findProductsForUpdate(array $productIds): Collection;

    public function saveProduct(Product $product): void;
}
