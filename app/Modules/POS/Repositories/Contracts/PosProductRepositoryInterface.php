<?php

namespace App\Modules\POS\Repositories\Contracts;

use Illuminate\Support\Collection;

interface PosProductRepositoryInterface
{
    public function searchActive(string $keyword = '', int $limit = 20): Collection;

    public function findByIdsForUpdate(array $ids, bool $forUpdate = true): Collection;

    public function decreaseStock(int $productId, int $quantity): void;
}
