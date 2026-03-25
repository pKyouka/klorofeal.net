<?php

namespace App\Modules\Inventory\Repositories\Contracts;

use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StockMovementRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function create(array $payload): StockMovement;
}
