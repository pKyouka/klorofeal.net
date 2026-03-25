<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="brand-badge">Procurement</span>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Purchase {{ $purchase->invoice_number }}</h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('purchases.create') }}" class="inline-flex items-center rounded-xl border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">New Purchase</a>
                <a href="{{ route('purchases.index') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-container max-w-5xl space-y-4">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="surface-card overflow-hidden">
                <div class="border-b border-slate-200 p-6">
                    <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-3">
                        <div>
                            <p class="text-slate-500">Invoice Number</p>
                            <p class="font-semibold text-slate-900">{{ $purchase->invoice_number }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Supplier</p>
                            <p class="font-semibold text-slate-900">{{ $purchase->supplier?->name ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Purchased At</p>
                            <p class="font-semibold text-slate-900">{{ optional($purchase->purchased_at)->format('d M Y H:i') }}</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="table-shell">
                        <div class="overflow-x-auto">
                            <table class="table-modern">
                                <thead>
                                    <tr>
                                        <th class="text-left">Product</th>
                                        <th class="text-right">Qty</th>
                                        <th class="text-right">Price</th>
                                        <th class="text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($purchase->items as $item)
                                        <tr>
                                            <td>
                                                <p class="font-semibold text-slate-900">{{ $item->product?->name }}</p>
                                                <p class="text-xs text-slate-500">{{ $item->product?->sku }}</p>
                                            </td>
                                            <td class="text-right text-slate-700">{{ $item->qty }}</td>
                                            <td class="text-right text-slate-700">{{ number_format((float) $item->price, 0, ',', '.') }}</td>
                                            <td class="text-right font-semibold text-slate-900">{{ number_format((float) ($item->qty * $item->price), 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end">
                        <div class="w-full max-w-sm rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
                            <div class="flex items-center justify-between py-1">
                                <span class="text-slate-600">Grand Total</span>
                                <span class="text-lg font-bold text-slate-900">{{ number_format((float) $purchase->total_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
