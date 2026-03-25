<?php

namespace App\Modules\Purchase\Repositories;

use App\Modules\Purchase\Models\Purchase;
use App\Modules\Purchase\Models\PurchaseItem;
use App\Modules\Purchase\Repositories\Contracts\PurchaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class PurchaseRepository implements PurchaseRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Purchase::query()
            ->with('supplier')
            ->latest('id')
            ->paginate($perPage);
    }

    public function generateInvoiceNumber(): string
    {
        $prefix = 'PO-' . Carbon::now()->format('Ymd') . '-';

        $latestInvoice = Purchase::query()
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $nextNumber = $latestInvoice
            ? ((int) substr($latestInvoice, -4)) + 1
            : 1;

        return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function createPurchase(array $payload): Purchase
    {
        return Purchase::query()->create($payload);
    }

    public function createPurchaseItem(array $payload): void
    {
        PurchaseItem::query()->create($payload);
    }
}
