<?php

namespace App\Modules\Purchase\Repositories\Contracts;

use Illuminate\Support\Collection;

interface PurchaseProductRepositoryInterface
{
    public function searchActive(string $keyword = '', int $limit = 20): Collection;

    public function findByIdsForUpdate(array $ids): Collection;

    public function increaseStock(int $productId, int $quantity): void;
}
