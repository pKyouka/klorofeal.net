<x-app-layout>
    <x-slot name="header">
        @php($user = auth()->user())
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <span class="brand-badge">Operations Pulse</span>
                <h2 class="mt-3 text-2xl font-bold text-slate-900">Dashboard Klorofeal</h2>
                <p class="section-subtitle mt-1">Monitor transaksi, pergerakan stok, dan indikator penting dalam satu layar.</p>
            </div>

            @if ($user && $user->hasRole('admin'))
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('reports.sales') }}" class="inline-flex items-center rounded-xl border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">
                        Sales Report
                    </a>
                    <a href="{{ route('reports.inventory') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                        Inventory Report
                    </a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-container space-y-6">
            <section class="surface-card overflow-hidden">
                <div class="grid gap-0 lg:grid-cols-3">
                    <div class="border-b border-slate-200 p-6 lg:border-b-0 lg:border-r">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Total Sales Today</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">Rp {{ number_format((float) $total_sales_today, 0, ',', '.') }}</p>
                        <p class="mt-2 text-sm text-slate-500">Nilai total penjualan pada hari ini.</p>
                    </div>
                    <div class="border-b border-slate-200 p-6 lg:border-b-0 lg:border-r">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Total Products</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $total_products }}</p>
                        <p class="mt-2 text-sm text-slate-500">Jumlah produk aktif yang tercatat di katalog.</p>
                    </div>
                    <div class="p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Low Stock Alerts</p>
                        <p class="mt-2 text-3xl font-bold {{ $low_stock_count > 0 ? 'text-orange-700' : 'text-emerald-700' }}">{{ $low_stock_count }}</p>
                        <p class="mt-2 text-sm text-slate-500">Produk yang perlu segera di-restock.</p>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <div class="table-shell motion-rise">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <div>
                            <h3 class="section-title">Low Stock Items</h3>
                            <p class="section-subtitle">Daftar item di bawah batas minimum.</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th class="text-left">SKU</th>
                                    <th class="text-left">Product</th>
                                    <th class="text-right">Stock</th>
                                    <th class="text-right">Min</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($low_stock_items as $item)
                                    <tr>
                                        <td class="text-slate-700">{{ $item->sku ?: '-' }}</td>
                                        <td class="font-semibold text-slate-900">{{ $item->name ?: '-' }}</td>
                                        <td class="text-right text-orange-700">{{ $item->stock }}</td>
                                        <td class="text-right text-slate-700">{{ $item->minimum_stock }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-5 text-center text-slate-500">No low stock items.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="table-shell motion-rise">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <div>
                            <h3 class="section-title">Recent Transactions</h3>
                            <p class="section-subtitle">Penjualan terbaru dari kasir.</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th class="text-left">Invoice</th>
                                    <th class="text-left">Cashier</th>
                                    <th class="text-right">Total</th>
                                    <th class="text-left">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recent_sales as $sale)
                                    <tr>
                                        <td class="font-semibold text-slate-900">{{ $sale->invoice_number }}</td>
                                        <td class="text-slate-700">{{ $sale->cashier?->name ?: '-' }}</td>
                                        <td class="text-right text-slate-800">Rp {{ number_format((float) $sale->total_amount, 0, ',', '.') }}</td>
                                        <td class="text-slate-600">{{ optional($sale->sold_at)->format('d M Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-5 text-center text-slate-500">No recent transactions.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
