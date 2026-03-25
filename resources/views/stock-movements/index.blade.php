<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="brand-badge">Inventory</span>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Stock Movements</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-container space-y-4">
            <div class="surface-card space-y-4 p-5 sm:p-6">
                <form method="GET" action="{{ route('stock-movements.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-5">
                    <input
                        type="text"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Search product name or SKU"
                        class="w-full px-3 py-2 text-sm md:col-span-2"
                    >

                    <select name="type" class="w-full px-3 py-2 text-sm">
                        <option value="">All Types</option>
                        <option value="in" @selected(($filters['type'] ?? '') === 'in')>In</option>
                        <option value="out" @selected(($filters['type'] ?? '') === 'out')>Out</option>
                        <option value="adjustment" @selected(($filters['type'] ?? '') === 'adjustment')>Adjustment</option>
                    </select>

                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full px-3 py-2 text-sm">
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full px-3 py-2 text-sm">

                    <div class="md:col-span-5 flex items-center gap-2">
                        <button type="submit" class="inline-flex items-center rounded-xl border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">Filter</button>
                        <a href="{{ route('stock-movements.index') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                    </div>
                </form>

                <div class="table-shell">
                    <div class="overflow-x-auto">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th class="text-left">Date</th>
                                    <th class="text-left">SKU</th>
                                    <th class="text-left">Product</th>
                                    <th class="text-left">Type</th>
                                    <th class="text-right">Quantity</th>
                                    <th class="text-left">Reference</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($movements as $movement)
                                    @php
                                        $rawQuantity = (int) $movement->quantity;
                                        $displayQuantity = $rawQuantity;
                                        if ($movement->type === 'out' && $displayQuantity > 0) {
                                            $displayQuantity *= -1;
                                        }
                                        if ($movement->type === 'in' && $displayQuantity < 0) {
                                            $displayQuantity = abs($displayQuantity);
                                        }
                                        $referenceType = (string) $movement->reference_type;
                                        $referenceLabel = str_contains($referenceType, '\\')
                                            ? class_basename($referenceType)
                                            : $referenceType;
                                    @endphp
                                    <tr>
                                        <td class="text-slate-600">{{ optional($movement->created_at)->format('d M Y H:i') }}</td>
                                        <td class="text-slate-700">{{ $movement->product?->sku ?: '-' }}</td>
                                        <td class="font-semibold text-slate-900">{{ $movement->product?->name ?: '-' }}</td>
                                        <td class="text-slate-700 uppercase">{{ $movement->type }}</td>
                                        <td class="text-right font-semibold {{ $displayQuantity >= 0 ? 'text-emerald-700' : 'text-orange-700' }}">
                                            {{ $displayQuantity >= 0 ? '+' : '' }}{{ $displayQuantity }}
                                        </td>
                                        <td class="text-slate-600">
                                            {{ $referenceLabel ?: '-' }}
                                            @if ($movement->reference_id)
                                                #{{ $movement->reference_id }}
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-6 text-center text-slate-500">No stock movement data found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-slate-200 px-5 py-4">
                        {{ $movements->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
