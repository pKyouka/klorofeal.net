<?php

namespace App\Modules\Products\Repositories\Contracts;

use App\Modules\Products\Models\ProductCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ProductCategoryRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function all(): Collection;

    public function create(array $data): ProductCategory;

    public function update(ProductCategory $category, array $data): ProductCategory;

    public function delete(ProductCategory $category): void;

    public function isSlugTaken(string $slug, ?int $ignoreId = null): bool;
}
