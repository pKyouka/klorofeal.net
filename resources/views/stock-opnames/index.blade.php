<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="brand-badge">Warehouse</span>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Stock Opname Sessions</h2>
            </div>
            <form method="POST" action="{{ route('stock-opnames.start') }}" onsubmit="return confirm('Start a new stock opname session?');">
                @csrf
                <button type="submit" class="inline-flex items-center rounded-xl border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">Start Session</button>
            </form>
        </div>
    </x-slot>

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

            <div class="table-shell">
                <div class="overflow-x-auto">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th class="text-left">Session ID</th>
                                <th class="text-left">Created By</th>
                                <th class="text-left">Status</th>
                                <th class="text-right">Items</th>
                                <th class="text-right">Discrepancies</th>
                                <th class="text-left">Created At</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sessions as $session)
                                <tr>
                                    <td class="font-semibold text-slate-900">#{{ $session->id }}</td>
                                    <td class="text-slate-700">{{ $session->user?->name ?: '-' }}</td>
                                    <td>
                                        @if ($session->status === 'in_progress')
                                            <span class="status-pill bg-amber-100 text-amber-700">In Progress</span>
                                        @elseif ($session->status === 'completed')
                                            <span class="status-pill status-pill-safe">Completed</span>
                                        @elseif ($session->status === 'cancelled')
                                            <span class="status-pill status-pill-low">Cancelled</span>
                                        @else
                                            <span class="status-pill bg-slate-200 text-slate-700">Draft</span>
                                        @endif
                                    </td>
                                    <td class="text-right text-slate-700">{{ $session->items_count }}</td>
                                    <td class="text-right text-slate-700">{{ $session->discrepancy_items_count }}</td>
                                    <td class="text-slate-600">{{ optional($session->created_at)->format('d M Y H:i') }}</td>
                                    <td>
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('stock-opnames.show', $session) }}" class="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Open</a>
                                            <a href="{{ route('stock-opnames.report', $session) }}" class="rounded-xl border border-teal-300 px-3 py-1.5 text-xs font-semibold text-teal-700 hover:bg-teal-50">Report</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-6 text-center text-slate-500">No stock opname sessions found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $sessions->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
