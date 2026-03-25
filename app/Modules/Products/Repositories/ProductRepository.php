<?php

namespace App\Modules\Products\Repositories;

use App\Modules\Products\Models\Product;
use App\Modules\Products\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository implements ProductRepositoryInterface
{
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $categoryId = $filters['category_id'] ?? null;

        return Product::query()
            ->with('category')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function dataTablePage(array $filters): array
    {
        $start = max(0, (int) ($filters['start'] ?? 0));
        $length = (int) ($filters['length'] ?? 12);
        $length = $length > 0 ? min($length, 100) : 12;
        $search = trim((string) ($filters['search'] ?? ''));
        $categoryId = $filters['category_id'] ?? null;
        $status = $filters['status'] ?? null;
        $orderColumn = (int) ($filters['order_column'] ?? 0);
        $orderDirection = strtolower((string) ($filters['order_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $sortableColumns = [
            0 => 'products.sku',
            1 => 'products.barcode',
            2 => 'products.name',
            3 => 'product_categories.name',
            4 => 'products.cost_price',
            5 => 'products.sell_price',
            6 => 'products.stock',
            7 => 'products.is_active',
        ];

        $orderBy = $sortableColumns[$orderColumn] ?? 'products.id';

        $baseQuery = Product::query()
            ->leftJoin('product_categories', 'product_categories.id', '=', 'products.category_id');

        $filteredQuery = (clone $baseQuery)
            ->when($categoryId !== null && $categoryId !== '', fn ($query) => $query->where('products.category_id', (int) $categoryId))
            ->when($status !== null && $status !== '', fn ($query) => $query->where('products.is_active', (int) $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery->where('products.name', 'like', "%{$search}%")
                        ->orWhere('products.sku', 'like', "%{$search}%")
                        ->orWhere('products.barcode', 'like', "%{$search}%")
                        ->orWhere('product_categories.name', 'like', "%{$search}%");
                });
            });

        $recordsTotal = Product::query()->count();
        $recordsFiltered = (clone $filteredQuery)->count('products.id');

        $rows = (clone $filteredQuery)
            ->select([
                'products.id',
                'products.sku',
                'products.barcode',
                'products.name',
                'products.cost_price',
                'products.sell_price',
                'products.stock',
                'products.is_active',
                'product_categories.name as category_name',
            ])
            ->orderBy($orderBy, $orderDirection)
            ->orderBy('products.id', 'desc')
            ->offset($start)
            ->limit($length)
            ->get();

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows->map(fn ($row) => [
                'id' => $row->id,
                'sku' => $row->sku,
                'barcode' => $row->barcode,
                'name' => $row->name,
                'category' => $row->category_name ?? '-',
                'cost_price' => (float) $row->cost_price,
                'sell_price' => (float) $row->sell_price,
                'stock' => (int) $row->stock,
                'status_label' => (int) $row->is_active === 1 ? 'Active' : 'Inactive',
                'is_active' => (int) $row->is_active === 1,
                'edit_url' => route('products.edit', $row->id),
                'delete_url' => route('products.destroy', $row->id),
            ])->all(),
        ];
    }

    public function create(array $data): Product
    {
        return Product::query()->create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->refresh();
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
