<?php

namespace App\Modules\Purchase\Repositories\Contracts;

use App\Modules\Purchase\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SupplierRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function all(): Collection;

    public function create(array $payload): Supplier;

    public function update(Supplier $supplier, array $payload): Supplier;

    public function delete(Supplier $supplier): void;
}
