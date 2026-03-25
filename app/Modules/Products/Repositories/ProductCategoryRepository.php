<?php

namespace App\Modules\Products\Repositories;

use App\Modules\Products\Models\ProductCategory;
use App\Modules\Products\Repositories\Contracts\ProductCategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductCategoryRepository implements ProductCategoryRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return ProductCategory::query()
            ->withCount('products')
            ->latest('id')
            ->paginate($perPage);
    }

    public function all(): Collection
    {
        return ProductCategory::query()
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): ProductCategory
    {
        return ProductCategory::query()->create($data);
    }

    public function update(ProductCategory $category, array $data): ProductCategory
    {
        $category->update($data);

        return $category->refresh();
    }

    public function delete(ProductCategory $category): void
    {
        $category->delete();
    }

    public function isSlugTaken(string $slug, ?int $ignoreId = null): bool
    {
        return ProductCategory::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }
}
