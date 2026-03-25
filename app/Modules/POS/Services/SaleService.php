<?php

namespace App\Modules\POS\Services;

use App\Modules\Inventory\Models\StockMovement;
use App\Modules\POS\Models\Sale;
use App\Modules\POS\Repositories\Contracts\PosProductRepositoryInterface;
use App\Modules\POS\Repositories\Contracts\SaleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly PosProductRepositoryInterface $productRepository,
    ) {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->saleRepository->paginate($perPage);
    }

    public function latestSaleForUser(int $userId): ?Sale
    {
        return $this->saleRepository->latestByUser($userId);
    }

    public function searchProducts(string $keyword = '', int $limit = 20): Collection
    {
        return $this->productRepository->searchActive($keyword, $limit);
    }

    public function previewSale(array $items, float $taxAmount = 0): array
    {
        $prepared = $this->prepareSalePayload($items, $taxAmount, false);

        return [
            'subtotal' => $prepared['subtotal'],
            'tax_amount' => $prepared['tax_amount'],
            'grand_total' => $prepared['grand_total'],
        ];
    }

    public function createSale(int $userId, array $items, string $paymentMethod, float $taxAmount = 0): Sale
    {
        return DB::transaction(function () use ($userId, $items, $paymentMethod, $taxAmount) {
            $prepared = $this->prepareSalePayload($items, $taxAmount, true);
            $normalizedItems = $prepared['items'];
            $products = $prepared['products'];
            $grandTotal = $prepared['grand_total'];

            $sale = $this->saleRepository->createSale([
                'invoice_number' => $this->saleRepository->generateInvoiceNumber(),
                'user_id' => $userId,
                'payment_method' => $paymentMethod,
                'total_amount' => $grandTotal,
                'sold_at' => now(),
            ]);

            foreach ($normalizedItems as $item) {
                $this->saleRepository->createSaleItem([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                ]);

                $this->productRepository->decreaseStock($item['product_id'], $item['qty']);

                $productStock = (int) $products[$item['product_id']]->stock - $item['qty'];
                $products[$item['product_id']]->stock = $productStock;

                StockMovement::query()->create([
                    'product_id' => $item['product_id'],
                    'type' => 'out',
                    'quantity' => $item['qty'],
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                ]);
            }

            return $sale->load(['items.product', 'cashier']);
        });
    }

    private function prepareSalePayload(array $items, float $taxAmount, bool $forUpdate): array
    {
        $items = collect($items)
            ->map(fn (array $item) => [
                'product_id' => (int) ($item['product_id'] ?? 0),
                'qty' => (int) ($item['qty'] ?? 0),
            ])
            ->filter(fn (array $item) => $item['product_id'] > 0 && $item['qty'] > 0)
            ->groupBy('product_id')
            ->map(fn (Collection $group, int|string $productId) => [
                'product_id' => (int) $productId,
                'qty' => (int) $group->sum('qty'),
            ])
            ->values();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Cart is empty.',
            ]);
        }

        $productIds = $items->pluck('product_id')->unique()->values()->all();
        $products = $this->productRepository->findByIdsForUpdate($productIds, $forUpdate)->keyBy('id');

        $subtotal = 0;
        $normalizedItems = [];

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);

            if ($product === null) {
                throw ValidationException::withMessages([
                    'items' => 'One or more selected products are not found.',
                ]);
            }

            if (! $product->is_active) {
                throw ValidationException::withMessages([
                    'items' => "Product {$product->name} is inactive.",
                ]);
            }

            if ($product->stock < $item['qty']) {
                throw ValidationException::withMessages([
                    'items' => "Insufficient stock for {$product->name}. Remaining stock: {$product->stock}.",
                ]);
            }

            $linePrice = (float) $product->sell_price;
            $lineSubtotal = $linePrice * $item['qty'];
            $subtotal += $lineSubtotal;

            $normalizedItems[] = [
                'product_id' => $product->id,
                'qty' => $item['qty'],
                'price' => $linePrice,
                'subtotal' => $lineSubtotal,
            ];
        }

        $taxAmount = max(0, $taxAmount);
        $grandTotal = $subtotal + $taxAmount;

        return [
            'items' => $normalizedItems,
            'products' => $products,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'grand_total' => $grandTotal,
        ];
    }
}
