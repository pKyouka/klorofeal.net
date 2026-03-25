<?php

namespace App\Modules\Inventory\Repositories;

use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StockMovementRepository implements StockMovementRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $type = trim((string) ($filters['type'] ?? ''));
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));

        return StockMovement::query()
            ->with('product')
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('product', function ($productQuery) use ($search) {
                    $productQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $payload): StockMovement
    {
        return StockMovement::query()->create($payload);
    }
}
