<?php

namespace App\Modules\Warehouse\Services;

use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Warehouse\Models\StockOpname;
use App\Modules\Warehouse\Repositories\Contracts\StockOpnameInventoryRepositoryInterface;
use App\Modules\Warehouse\Repositories\Contracts\StockOpnameRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockOpnameService
{
    public function __construct(
        private readonly StockOpnameRepositoryInterface $stockOpnameRepository,
        private readonly StockOpnameInventoryRepositoryInterface $inventoryRepository,
    ) {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->stockOpnameRepository->paginate($perPage);
    }

    public function startSession(int $userId): StockOpname
    {
        return DB::transaction(function () use ($userId) {
            $existingSession = $this->stockOpnameRepository->findInProgressSession();

            if ($existingSession !== null) {
                throw ValidationException::withMessages([
                    'stock_opname' => 'There is already an in-progress stock opname session.',
                ]);
            }

            $session = $this->stockOpnameRepository->create([
                'user_id' => $userId,
                'status' => StockOpname::STATUS_IN_PROGRESS,
            ]);

            $products = $this->inventoryRepository->allProductsForSession();
            $now = now();

            $rows = $products->map(fn ($product) => [
                'stock_opname_id' => $session->id,
                'product_id' => $product->id,
                'system_stock' => (int) $product->stock,
                'physical_stock' => (int) $product->stock,
                'difference' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            if ($rows !== []) {
                $this->stockOpnameRepository->insertItems($rows);
            }

            return $this->stockOpnameRepository->loadWithItems($session);
        });
    }

    public function getSession(StockOpname $stockOpname): StockOpname
    {
        return $this->stockOpnameRepository->loadWithItems($stockOpname);
    }

    public function updateCounts(StockOpname $stockOpname, array $items): StockOpname
    {
        if (in_array($stockOpname->status, [StockOpname::STATUS_COMPLETED, StockOpname::STATUS_CANCELLED], true)) {
            throw ValidationException::withMessages([
                'stock_opname' => 'This stock opname session can no longer be updated.',
            ]);
        }

        DB::transaction(function () use ($stockOpname, $items) {
            $sessionItems = $this->stockOpnameRepository->loadItemsCollection($stockOpname)->keyBy('id');

            foreach ($items as $itemId => $payload) {
                $key = (int) $itemId;
                $sessionItem = $sessionItems->get($key);

                if ($sessionItem === null) {
                    continue;
                }

                $physicalStock = (int) ($payload['physical_stock'] ?? 0);
                $difference = $physicalStock - (int) $sessionItem->system_stock;

                $sessionItem->physical_stock = $physicalStock;
                $sessionItem->difference = $difference;

                $this->stockOpnameRepository->saveItem($sessionItem);
            }
        });

        return $this->stockOpnameRepository->loadWithItems($stockOpname->fresh());
    }

    public function completeSession(StockOpname $stockOpname): StockOpname
    {
        return DB::transaction(function () use ($stockOpname) {
            $lockedSession = $this->stockOpnameRepository->lockSessionById($stockOpname->id);

            if ($lockedSession === null) {
                throw ValidationException::withMessages([
                    'stock_opname' => 'Stock opname session not found.',
                ]);
            }

            if (in_array($lockedSession->status, [StockOpname::STATUS_COMPLETED, StockOpname::STATUS_CANCELLED], true)) {
                throw ValidationException::withMessages([
                    'stock_opname' => 'This stock opname session has already been finalized.',
                ]);
            }

            $items = $this->stockOpnameRepository->loadItemsCollection($lockedSession);
            $productIds = $items->pluck('product_id')->unique()->values()->all();

            $products = $this->inventoryRepository->findProductsForUpdate($productIds);

            foreach ($items as $item) {
                if ((int) $item->difference === 0) {
                    continue;
                }

                $product = $products->get($item->product_id);
                if ($product === null) {
                    continue;
                }

                $product->stock = (int) $item->physical_stock;
                $this->inventoryRepository->saveProduct($product);

                StockMovement::query()->create([
                    'product_id' => $item->product_id,
                    'type' => 'adjustment',
                    'quantity' => (int) $item->difference,
                    'reference_type' => StockOpname::class,
                    'reference_id' => $lockedSession->id,
                ]);
            }

            $lockedSession->status = StockOpname::STATUS_COMPLETED;
            $this->stockOpnameRepository->save($lockedSession);

            return $this->stockOpnameRepository->loadWithItems($lockedSession->fresh());
        });
    }

    public function discrepancyItems(StockOpname $stockOpname): Collection
    {
        return $this->stockOpnameRepository
            ->loadItemsCollection($stockOpname)
            ->where('difference', '!=', 0)
            ->values();
    }
}
