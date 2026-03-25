<?php

namespace App\Modules\Purchase\Repositories\Contracts;

use App\Modules\Purchase\Models\Purchase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PurchaseRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function generateInvoiceNumber(): string;

    public function createPurchase(array $payload): Purchase;

    public function createPurchaseItem(array $payload): void;
}
