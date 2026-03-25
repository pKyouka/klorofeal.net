<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Repositories\Contracts\InventoryStockRepositoryInterface;
use App\Modules\Inventory\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(
        private readonly InventoryStockRepositoryInterface $stockRepository,
        private readonly StockMovementRepositoryInterface $movementRepository,
    ) {
    }

    public function paginateStocks(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->stockRepository->paginate($filters, $perPage);
    }

    public function paginateMovements(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->movementRepository->paginate($filters, $perPage);
    }

    public function adjustmentProducts(): Collection
    {
        return $this->stockRepository->adjustmentProducts();
    }

    public function searchAdjustmentProducts(string $keyword): Collection
    {
        return $this->stockRepository->searchAdjustmentProducts($keyword);
    }

    public function findAdjustmentProductById(int $id): ?object
    {
        return $this->stockRepository->findAdjustmentProductById($id);
    }

    public function adjustStock(array $payload, int $userId): void
    {
        DB::transaction(function () use ($payload, $userId) {
            $productId = (int) $payload['product_id'];
            $adjustmentType = (string) $payload['adjustment_type'];
            $quantity = (int) $payload['quantity'];

            $product = $this->stockRepository->findProductForUpdate($productId);

            if ($product === null) {
                throw ValidationException::withMessages([
                    'product_id' => 'Selected product is not found.',
                ]);
            }

            $currentStock = (int) $product->stock;

            if ($adjustmentType === 'increase') {
                $newStock = $currentStock + $quantity;
                $movementQuantity = $quantity;
            } elseif ($adjustmentType === 'decrease') {
                if ($currentStock < $quantity) {
                    throw ValidationException::withMessages([
                        'quantity' => "Adjustment exceeds current stock ({$currentStock}).",
                    ]);
                }

                $newStock = $currentStock - $quantity;
                $movementQuantity = -$quantity;
            } else {
                $newStock = $quantity;
                $movementQuantity = $newStock - $currentStock;
            }

            if ($movementQuantity === 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'No stock change detected.',
                ]);
            }

            $product->stock = $newStock;
            $this->stockRepository->saveProduct($product);

            $this->movementRepository->create([
                'product_id' => $product->id,
                'type' => 'adjustment',
                'quantity' => $movementQuantity,
                'reference_type' => 'manual_adjustment',
                'reference_id' => $userId,
            ]);
        });
    }
}
