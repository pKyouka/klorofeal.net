<?php

namespace App\Modules\Warehouse\Repositories;

use App\Modules\Warehouse\Models\StockOpname;
use App\Modules\Warehouse\Models\StockOpnameItem;
use App\Modules\Warehouse\Repositories\Contracts\StockOpnameRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StockOpnameRepository implements StockOpnameRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return StockOpname::query()
            ->with('user')
            ->withCount(['items', 'items as discrepancy_items_count' => function ($query) {
                $query->where('difference', '!=', 0);
            }])
            ->latest('id')
            ->paginate($perPage);
    }

    public function findInProgressSession(): ?StockOpname
    {
        return StockOpname::query()
            ->where('status', StockOpname::STATUS_IN_PROGRESS)
            ->latest('id')
            ->first();
    }

    public function create(array $payload): StockOpname
    {
        return StockOpname::query()->create($payload);
    }

    public function insertItems(array $rows): void
    {
        StockOpnameItem::query()->insert($rows);
    }

    public function loadWithItems(StockOpname $stockOpname): StockOpname
    {
        return $stockOpname->load(['user', 'items.product']);
    }

    public function loadItemsCollection(StockOpname $stockOpname): Collection
    {
        return $stockOpname->items()->with('product')->get();
    }

    public function save(StockOpname $stockOpname): void
    {
        $stockOpname->save();
    }

    public function saveItem(StockOpnameItem $item): void
    {
        $item->save();
    }

    public function lockSessionById(int $id): ?StockOpname
    {
        return StockOpname::query()
            ->where('id', $id)
            ->lockForUpdate()
            ->first();
    }
}
