<?php

namespace App\Modules\Purchase\Services;

use App\Modules\Purchase\Models\Supplier;
use App\Modules\Purchase\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SupplierService
{
    public function __construct(
        private readonly SupplierRepositoryInterface $supplierRepository,
    ) {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->supplierRepository->paginate($perPage);
    }

    public function all(): Collection
    {
        return $this->supplierRepository->all();
    }

    public function create(array $payload): Supplier
    {
        return $this->supplierRepository->create($payload);
    }

    public function update(Supplier $supplier, array $payload): Supplier
    {
        return $this->supplierRepository->update($supplier, $payload);
    }

    public function delete(Supplier $supplier): void
    {
        if ($supplier->purchases()->exists()) {
            throw ValidationException::withMessages([
                'supplier' => 'Cannot delete supplier that already has purchase transactions.',
            ]);
        }

        $this->supplierRepository->delete($supplier);
    }

    /**
     * Bulk-create suppliers from an array of rows.
     * Each row must have at least 'name'.
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
                $this->supplierRepository->create([
                    'name' => $name,
                    'contact_person' => trim($row['contact_person'] ?? '') ?: null,
                    'phone' => trim($row['phone'] ?? '') ?: null,
                    'email' => trim($row['email'] ?? '') ?: null,
                    'address' => null,
                ]);
                $imported++;
            } catch (\Exception) {
                $errors[] = 'Baris ' . ($i + 1) . " ({$name}): Gagal disimpan (nama mungkin sudah ada).";
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
    }
}
