<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="brand-badge">Procurement Insights</span>
            <h2 class="mt-3 text-2xl font-bold text-slate-900">Purchase Report</h2>
            <p class="section-subtitle mt-1">Analisa pembelian berdasarkan periode, tren bulanan, dan histori transaksi supplier.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-container space-y-6">
            <form method="GET" action="{{ route('reports.purchases') }}" class="surface-card grid grid-cols-1 gap-3 p-4 md:grid-cols-5 md:items-end">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Date From</label>
                    <input type="date" name="date_from" value="{{ $date_from }}" class="w-full px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Date To</label>
                    <input type="date" name="date_to" value="{{ $date_to }}" class="w-full px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-3 flex flex-wrap items-end gap-2">
                    <button type="submit" class="inline-flex items-center rounded-xl border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">
                        Apply Filter
                    </button>
                    <a href="{{ route('reports.purchases') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                        Reset
                    </a>
                </div>
            </form>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="metric-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Transactions (Selected Range)</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $summary['transaction_count'] }}</p>
                </div>
                <div class="metric-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Total Purchases (Selected Range)</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">Rp {{ number_format((float) $summary['total_purchases'], 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <div class="table-shell">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h3 class="section-title">Daily Purchases</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th class="text-left">Date</th>
                                    <th class="text-right">Transactions</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($daily_purchases as $row)
                                    <tr>
                                        <td>{{ \Illuminate\Support\Carbon::parse($row->date)->format('d M Y') }}</td>
                                        <td class="text-right">{{ $row->transactions }}</td>
                                        <td class="text-right font-semibold">Rp {{ number_format((float) $row->total_amount, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-5 text-center text-slate-500">No purchase data in selected period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="table-shell">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h3 class="section-title">Monthly Purchases (Last 12 Months)</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th class="text-left">Month</th>
                                    <th class="text-right">Transactions</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($monthly_purchases as $row)
                                    <tr>
                                        <td>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $row->month)->format('M Y') }}</td>
                                        <td class="text-right">{{ $row->transactions }}</td>
                                        <td class="text-right font-semibold">Rp {{ number_format((float) $row->total_amount, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-5 text-center text-slate-500">No monthly purchase data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="table-shell">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="section-title">Purchase Transactions</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th class="text-left">Invoice</th>
                                <th class="text-left">Supplier</th>
                                <th class="text-right">Total</th>
                                <th class="text-left">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recent_purchases as $purchase)
                                <tr>
                                    <td class="font-semibold text-slate-900">{{ $purchase->invoice_number }}</td>
                                    <td class="text-slate-700">{{ $purchase->supplier?->name ?: '-' }}</td>
                                    <td class="text-right font-semibold">Rp {{ number_format((float) $purchase->total_amount, 0, ',', '.') }}</td>
                                    <td class="text-slate-600">{{ optional($purchase->purchased_at)->format('d M Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-5 text-center text-slate-500">No purchases found in selected period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $recent_purchases->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
