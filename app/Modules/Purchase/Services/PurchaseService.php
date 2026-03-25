<?php

namespace App\Modules\Purchase\Services;

use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Purchase\Repositories\Contracts\PurchaseProductRepositoryInterface;
use App\Modules\Purchase\Repositories\Contracts\PurchaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(
        private readonly PurchaseRepositoryInterface $purchaseRepository,
        private readonly PurchaseProductRepositoryInterface $productRepository,
    ) {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->purchaseRepository->paginate($perPage);
    }

    public function searchProducts(string $keyword = '', int $limit = 20): Collection
    {
        return $this->productRepository->searchActive($keyword, $limit);
    }

    public function createPurchase(int $supplierId, array $items): Purchase
    {
        return DB::transaction(function () use ($supplierId, $items) {
            $items = collect($items)
                ->map(fn (array $item) => [
                    'product_id' => (int) ($item['product_id'] ?? 0),
                    'qty' => (int) ($item['qty'] ?? 0),
                    'price' => isset($item['price']) ? (float) $item['price'] : null,
                ])
                ->filter(fn (array $item) => $item['product_id'] > 0 && $item['qty'] > 0)
                ->groupBy('product_id')
                ->map(function (Collection $group, int|string $productId) {
                    $lastPrice = $group->last()['price'] ?? null;

                    return [
                        'product_id' => (int) $productId,
                        'qty' => (int) $group->sum('qty'),
                        'price' => $lastPrice !== null ? (float) $lastPrice : null,
                    ];
                })
                ->values();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Purchase items cannot be empty.',
                ]);
            }

            $productIds = $items->pluck('product_id')->unique()->values()->all();
            $products = $this->productRepository->findByIdsForUpdate($productIds)->keyBy('id');

            $totalAmount = 0;
            $normalizedItems = [];

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);

                if ($product === null) {
                    throw ValidationException::withMessages([
                        'items' => 'One or more selected products are not found.',
                    ]);
                }

                $linePrice = $item['price'] !== null
                    ? (float) $item['price']
                    : (float) $product->cost_price;

                $lineSubtotal = $linePrice * $item['qty'];
                $totalAmount += $lineSubtotal;

                $normalizedItems[] = [
                    'product_id' => $product->id,
                    'qty' => $item['qty'],
                    'price' => $linePrice,
                ];
            }

            $purchase = $this->purchaseRepository->createPurchase([
                'supplier_id' => $supplierId,
                'invoice_number' => $this->purchaseRepository->generateInvoiceNumber(),
                'total_amount' => $totalAmount,
                'purchased_at' => now(),
            ]);

            foreach ($normalizedItems as $item) {
                $this->purchaseRepository->createPurchaseItem([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                ]);

                $this->productRepository->increaseStock($item['product_id'], $item['qty']);

                $productStock = (int) $products[$item['product_id']]->stock + $item['qty'];
                $products[$item['product_id']]->stock = $productStock;

                StockMovement::query()->create([
                    'product_id' => $item['product_id'],
                    'type' => 'in',
                    'quantity' => $item['qty'],
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                ]);
            }

            return $purchase->load(['items.product', 'supplier']);
        });
    }
}
