<?php

namespace App\Modules\Products\Services;

use App\Modules\Products\Models\ProductCategory;
use App\Modules\Products\Repositories\Contracts\ProductCategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductCategoryService
{
    public function __construct(
        private readonly ProductCategoryRepositoryInterface $categoryRepository,
    ) {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->categoryRepository->paginate($perPage);
    }

    public function all(): Collection
    {
        return $this->categoryRepository->all();
    }

    public function create(array $payload): ProductCategory
    {
        $payload['slug'] = $this->makeUniqueSlug($payload['name']);

        return $this->categoryRepository->create($payload);
    }

    public function update(ProductCategory $category, array $payload): ProductCategory
    {
        $payload['slug'] = $this->makeUniqueSlug($payload['name'], $category->id);

        return $this->categoryRepository->update($category, $payload);
    }

    public function delete(ProductCategory $category): void
    {
        if ($category->products()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'Cannot delete category that still has products.',
            ]);
        }

        $this->categoryRepository->delete($category);
    }

    private function makeUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $candidate = $baseSlug;
        $counter = 2;

        while ($this->categoryRepository->isSlugTaken($candidate, $ignoreId)) {
            $candidate = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $candidate;
    }

    /**
     * Bulk-create categories from an array of rows.
     * Each row must have at least 'name'. 'description' is optional.
     * Returns ['imported' => int, 'errors' => string[]].
     */
    public function bulkCreate(array $rows): array
    {
        $imported = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            $name = trim($row['name'] ?? '');
            if ($name === '') {
                continue;
            }

            try {
                $this->create([
                    'name' => $name,
                    'description' => trim($row['description'] ?? ''),
                ]);
                $imported++;
            } catch (\Exception) {
                $errors[] = 'Baris ' . ($i + 1) . " ({$name}): Gagal disimpan (nama mungkin sudah ada).";
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
    }
}
