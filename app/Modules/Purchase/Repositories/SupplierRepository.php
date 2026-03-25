<?php

namespace App\Modules\Purchase\Repositories;

use App\Modules\Purchase\Models\Supplier;
use App\Modules\Purchase\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SupplierRepository implements SupplierRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Supplier::query()
            ->withCount('purchases')
            ->latest('id')
            ->paginate($perPage);
    }

    public function all(): Collection
    {
        return Supplier::query()
            ->orderBy('name')
            ->get();
    }

    public function create(array $payload): Supplier
    {
        return Supplier::query()->create($payload);
    }

    public function update(Supplier $supplier, array $payload): Supplier
    {
        $supplier->update($payload);

        return $supplier->refresh();
    }

    public function delete(Supplier $supplier): void
    {
        $supplier->delete();
    }
}
