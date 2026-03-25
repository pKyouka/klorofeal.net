<?php

namespace App\Modules\Warehouse\Repositories\Contracts;

use App\Modules\Warehouse\Models\StockOpname;
use App\Modules\Warehouse\Models\StockOpnameItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface StockOpnameRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findInProgressSession(): ?StockOpname;

    public function create(array $payload): StockOpname;

    public function insertItems(array $rows): void;

    public function loadWithItems(StockOpname $stockOpname): StockOpname;

    public function loadItemsCollection(StockOpname $stockOpname): Collection;

    public function save(StockOpname $stockOpname): void;

    public function saveItem(StockOpnameItem $item): void;

    public function lockSessionById(int $id): ?StockOpname;
}
