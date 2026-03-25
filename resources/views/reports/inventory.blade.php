<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="brand-badge">Stock Intelligence</span>
            <h2 class="mt-3 text-2xl font-bold text-slate-900">Inventory Report</h2>
            <p class="section-subtitle mt-1">Lihat status stok kritikal dan kondisi keseluruhan persediaan produk.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-container space-y-6">

            {{-- Summary stat cards --}}
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div class="surface-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Produk Tracked</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($stats['total_tracked']) }}</p>
                </div>
                <div class="surface-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Stok Aman</p>
                    <p class="mt-1 text-3xl font-bold text-emerald-700">{{ number_format($stats['safe_stock_count']) }}</p>
                </div>
                <div class="surface-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Low Stock</p>
                    <p class="mt-1 text-3xl font-bold text-amber-700">{{ number_format($stats['low_stock_count']) }}</p>
                </div>
                <div class="surface-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-rose-600">Habis (0)</p>
                    <p class="mt-1 text-3xl font-bold text-rose-700">{{ number_format($stats['out_of_stock_count']) }}</p>
                </div>
            </div>

            <div class="table-shell">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="section-title">Low Stock Alert</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th class="text-left">SKU</th>
                                <th class="text-left">Product</th>
                                <th class="text-left">Category</th>
                                <th class="text-right">Stock</th>
                                <th class="text-right">Minimum</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($low_stock_report as $item)
                                <tr>
                                    <td class="text-slate-700">{{ $item->sku ?: '-' }}</td>
                                    <td class="font-semibold text-slate-900">{{ $item->name ?: '-' }}</td>
                                    <td class="text-slate-700">{{ $item->category?->name ?: '-' }}</td>
                                    <td class="text-right text-orange-700">{{ $item->stock }}</td>
                                    <td class="text-right text-slate-700">{{ $item->minimum_stock }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-5 text-center text-slate-500">No low stock alerts.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="table-shell">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="section-title">Stock Report</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th class="text-left">SKU</th>
                                <th class="text-left">Product</th>
                                <th class="text-left">Category</th>
                                <th class="text-right">Current Stock</th>
                                <th class="text-right">Minimum Stock</th>
                                <th class="text-left">Status</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stock_report as $stock)
                                @php
                                    $isOut = (int) $stock->stock === 0;
                                    $isLow = !$isOut && (int) $stock->stock <= (int) $stock->minimum_stock;
                                @endphp
                                <tr>
                                    <td class="text-slate-700">{{ $stock->sku ?: '-' }}</td>
                                    <td class="font-semibold text-slate-900">{{ $stock->name ?: '-' }}</td>
                                    <td class="text-slate-700">{{ $stock->category?->name ?: '-' }}</td>
                                    <td class="text-right font-semibold {{ $isOut ? 'text-rose-600' : ($isLow ? 'text-amber-600' : 'text-slate-700') }}">{{ $stock->stock }}</td>
                                    <td class="text-right text-slate-700">{{ $stock->minimum_stock }}</td>
                                    <td>
                                        @if ($isOut)
                                            <span class="status-pill status-pill-low">Out of Stock</span>
                                        @elseif ($isLow)
                                            <span class="status-pill status-pill-low">Low</span>
                                        @else
                                            <span class="status-pill status-pill-safe">Safe</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('inventory.adjustments.create', ['product_id' => $stock->id]) }}"
                                            class="inline-flex rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            Adjust
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-5 text-center text-slate-500">No stock records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $stock_report->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
