<?php

namespace App\Modules\POS\Repositories;

use App\Modules\POS\Models\Sale;
use App\Modules\POS\Models\SaleItem;
use App\Modules\POS\Repositories\Contracts\SaleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class SaleRepository implements SaleRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Sale::query()
            ->with('cashier')
            ->latest('id')
            ->paginate($perPage);
    }

    public function latestByUser(int $userId): ?Sale
    {
        return Sale::query()
            ->with('cashier')
            ->where('user_id', $userId)
            ->latest('sold_at')
            ->latest('id')
            ->first();
    }

    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . Carbon::now()->format('Ymd') . '-';

        $latestInvoice = Sale::query()
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $nextNumber = $latestInvoice
            ? ((int) substr($latestInvoice, -4)) + 1
            : 1;

        return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function createSale(array $payload): Sale
    {
        return Sale::query()->create($payload);
    }

    public function createSaleItem(array $payload): void
    {
        SaleItem::query()->create($payload);
    }
}
