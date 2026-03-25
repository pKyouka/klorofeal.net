<?php

namespace App\Modules\POS\Repositories\Contracts;

use App\Modules\POS\Models\Sale;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SaleRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function latestByUser(int $userId): ?Sale;

    public function generateInvoiceNumber(): string;

    public function createSale(array $payload): Sale;

    public function createSaleItem(array $payload): void;
}
