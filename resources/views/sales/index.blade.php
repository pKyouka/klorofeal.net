<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="brand-badge">Sales Desk</span>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Sales Transactions</h2>
            </div>
            <a href="{{ route('sales.create') }}" class="inline-flex items-center rounded-xl border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">Open POS</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-container space-y-4">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="table-shell">
                <div class="overflow-x-auto">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th class="text-left">Invoice</th>
                                <th class="text-left">Cashier</th>
                                <th class="text-left">Payment</th>
                                <th class="text-right">Total</th>
                                <th class="text-left">Date</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sales as $sale)
                                <tr>
                                    <td class="font-semibold text-slate-900">{{ $sale->invoice_number }}</td>
                                    <td class="text-slate-600">{{ $sale->cashier?->name ?: '-' }}</td>
                                    <td class="text-slate-600 uppercase">{{ $sale->payment_method }}</td>
                                    <td class="text-right text-slate-700">{{ number_format((float) $sale->total_amount, 0, ',', '.') }}</td>
                                    <td class="text-slate-600">{{ optional($sale->sold_at)->format('d M Y H:i') }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('sales.show', $sale) }}" class="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">View Invoice</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-slate-500">No transactions found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $sales->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
