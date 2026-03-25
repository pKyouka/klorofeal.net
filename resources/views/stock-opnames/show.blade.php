<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="brand-badge">Warehouse</span>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Stock Opname Session #{{ $session->id }}</h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('stock-opnames.index') }}" class="inline-flex items-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back</a>
                <a href="{{ route('stock-opnames.report', $session) }}" class="inline-flex items-center rounded-xl border border-teal-300 px-4 py-2 text-sm font-semibold text-teal-700 hover:bg-teal-50">Discrepancy Report</a>
            </div>
        </div>
    </x-slot>

    @php
        $isEditable = in_array($session->status, ['draft', 'in_progress'], true);
    @endphp

    <div class="py-8">
        <div class="page-container space-y-4">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
                    <ul class="list-disc ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="surface-card p-6">
                <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-4">
                    <div>
                        <p class="text-slate-500">Created By</p>
                        <p class="font-semibold text-slate-900">{{ $session->user?->name ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Status</p>
                        <p class="font-semibold text-slate-900">{{ str_replace('_', ' ', ucfirst($session->status)) }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Total Items</p>
                        <p class="font-semibold text-slate-900">{{ $session->items->count() }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Discrepancies</p>
                        <p class="font-semibold text-slate-900">{{ $discrepancyCount }}</p>
                    </div>
                </div>
            </div>

            <div class="table-shell">
                <form method="POST" action="{{ route('stock-opnames.counts.update', $session) }}" class="space-y-4 p-5">
                    @csrf
                    @method('PUT')

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
                                @foreach ($session->items as $item)
                                    @php
                                        $difference = (int) $item->difference;
                                    @endphp
                                    <tr>
                                        <td class="text-slate-700">{{ $item->product?->sku ?: '-' }}</td>
                                        <td class="font-semibold text-slate-900">{{ $item->product?->name ?: '-' }}</td>
                                        <td class="text-right text-slate-700">{{ $item->system_stock }}</td>
                                        <td class="text-right">
                                            <input
                                                type="number"
                                                min="0"
                                                name="items[{{ $item->id }}][physical_stock]"
                                                value="{{ old("items.{$item->id}.physical_stock", $item->physical_stock) }}"
                                                @disabled(!$isEditable)
                                                class="w-24 rounded-xl border-slate-300 text-right text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 disabled:bg-slate-100"
                                            >
                                        </td>
                                        <td class="text-right font-semibold {{ $difference === 0 ? 'text-slate-700' : ($difference > 0 ? 'text-emerald-700' : 'text-rose-700') }}">
                                            {{ $difference > 0 ? '+' : '' }}{{ $difference }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($isEditable)
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="submit" class="inline-flex items-center rounded-xl border border-slate-800 bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Save Physical Counts</button>
                        </div>
                    @endif
                </form>
            </div>

            @if ($isEditable)
                <form method="POST" action="{{ route('stock-opnames.complete', $session) }}" onsubmit="return confirm('Complete this session and apply discrepancies to inventory stock?');">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-xl border border-emerald-700 bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Complete Session</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
