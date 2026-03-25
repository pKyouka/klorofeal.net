<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="brand-badge">Inventory</span>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Inventory</h2>
            </div>
            <a href="{{ route('inventory.adjustments.create') }}" class="inline-flex items-center rounded-xl border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">Manual Adjustment</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-container space-y-4">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="surface-card space-y-4 p-5 sm:p-6">
                <form method="GET" action="{{ route('inventory.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                    <input
                        type="text"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Search product name or SKU"
                        class="w-full px-3 py-2 text-sm md:col-span-2"
                    >

                    <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700">
                        <input type="checkbox" name="low_stock" value="1" @checked($filters['low_stock'] ?? false) class="rounded border-slate-300 text-teal-700 shadow-sm focus:ring-teal-500">
                        Low stock only
                    </label>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="inline-flex items-center rounded-xl border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">Filter</button>
                        <a href="{{ route('inventory.index') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                    </div>
                </form>

                <div class="table-shell">
                    <div class="overflow-x-auto">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th class="text-left">SKU</th>
                                    <th class="text-left">Product</th>
                                    <th class="text-left">Category</th>
                                    <th class="text-left">Barcode</th>
                                    <th class="text-right">Current Stock</th>
                                    <th class="text-right">Minimum</th>
                                    <th class="text-left">Status</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($stocks as $product)
                                    @php
                                        $isLow = (int) $product->stock <= (int) $product->minimum_stock;
                                        $isOut = (int) $product->stock === 0;
                                    @endphp
                                    <tr>
                                        <td class="text-slate-700">{{ $product->sku ?: '-' }}</td>
                                        <td class="font-semibold text-slate-900">{{ $product->name ?: '-' }}</td>
                                        <td class="text-slate-600">{{ $product->category?->name ?: '-' }}</td>
                                        <td class="font-mono text-xs text-slate-500">{{ $product->barcode ?: '-' }}</td>
                                        <td class="text-right font-semibold {{ $isOut ? 'text-rose-600' : ($isLow ? 'text-amber-600' : 'text-slate-700') }}">{{ $product->stock }}</td>
                                        <td class="text-right text-slate-700">{{ $product->minimum_stock }}</td>
                                        <td>
                                            @if ($isOut)
                                                <span class="status-pill status-pill-low">Out of Stock</span>
                                            @elseif ($isLow)
                                                <span class="status-pill status-pill-low">Low Stock</span>
                                            @else
                                                <span class="status-pill status-pill-safe">Safe</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('inventory.adjustments.create', ['product_id' => $product->id]) }}"
                                                class="inline-flex rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                Adjust
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="py-6 text-center text-slate-500">No inventory data found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-slate-200 px-5 py-4">
                        {{ $stocks->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
