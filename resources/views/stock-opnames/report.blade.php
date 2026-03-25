<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="brand-badge">Warehouse</span>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Stock Opname Discrepancy Report #{{ $session->id }}</h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('stock-opnames.show', $session) }}" class="inline-flex items-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back to Session</a>
                <a href="{{ route('stock-opnames.index') }}" class="inline-flex items-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">All Sessions</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-container space-y-4">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="surface-card p-6">
                <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-4">
                    <div>
                        <p class="text-slate-500">Status</p>
                        <p class="font-semibold text-slate-900">{{ str_replace('_', ' ', ucfirst($session->status)) }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Total Products Checked</p>
                        <p class="font-semibold text-slate-900">{{ $session->items->count() }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Products with Discrepancy</p>
                        <p class="font-semibold text-slate-900">{{ $discrepancyItems->count() }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Created At</p>
                        <p class="font-semibold text-slate-900">{{ optional($session->created_at)->format('d M Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <div class="table-shell">
                <div class="overflow-x-auto">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th class="text-left">SKU</th>
                                <th class="text-left">Product</th>
                                <th class="text-right">System Stock</th>
                                <th class="text-right">Physical Stock</th>
                                <th class="text-right">Difference</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($discrepancyItems as $item)
                                @php
                                    $difference = (int) $item->difference;
                                @endphp
                                <tr>
                                    <td class="text-slate-700">{{ $item->product?->sku ?: '-' }}</td>
                                    <td class="font-semibold text-slate-900">{{ $item->product?->name ?: '-' }}</td>
                                    <td class="text-right text-slate-700">{{ $item->system_stock }}</td>
                                    <td class="text-right text-slate-700">{{ $item->physical_stock }}</td>
                                    <td class="text-right font-semibold {{ $difference > 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ $difference > 0 ? '+' : '' }}{{ $difference }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-slate-500">No discrepancy found for this session.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
