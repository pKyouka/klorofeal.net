<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="brand-badge">Procurement</span>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Suppliers</h2>
            </div>
            <a href="{{ route('suppliers.create') }}" class="inline-flex items-center rounded-xl border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">Add Supplier</a>
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
                                <th class="text-left">Name</th>
                                <th class="text-left">Contact</th>
                                <th class="text-left">Phone</th>
                                <th class="text-left">Email</th>
                                <th class="text-right">Purchases</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($suppliers as $supplier)
                                <tr>
                                    <td class="font-semibold text-slate-900">{{ $supplier->name }}</td>
                                    <td class="text-slate-600">{{ $supplier->contact_person ?: '-' }}</td>
                                    <td class="text-slate-600">{{ $supplier->phone ?: '-' }}</td>
                                    <td class="text-slate-600">{{ $supplier->email ?: '-' }}</td>
                                    <td class="text-right text-slate-700">{{ $supplier->purchases_count }}</td>
                                    <td>
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('suppliers.edit', $supplier) }}" class="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Edit</a>
                                            <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" onsubmit="return confirm('Delete this supplier?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-xl border border-orange-300 px-3 py-1.5 text-xs font-semibold text-orange-700 hover:bg-orange-50">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-slate-500">No suppliers found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $suppliers->links() }}
                </div>
            </div>

                {{-- Bulk import errors --}}
                @if (session('import_result') && !empty(session('import_result')['errors']))
                    @php $bulkResult = session('import_result'); @endphp
                    <div class="surface-card p-5 space-y-2">
                        <p class="text-sm font-semibold text-orange-700">{{ count($bulkResult['errors']) }} baris gagal disimpan:</p>
                        @foreach ($bulkResult['errors'] as $err)
                            <p class="rounded-lg bg-orange-50 px-3 py-1.5 text-xs text-orange-700">{{ $err }}</p>
                        @endforeach
                    </div>
                @endif

                {{-- Quick bulk add --}}
                <div class="surface-card" x-data="bulkSupplierForm()">
                    <button type="button" @click="open = !open"
                            class="flex w-full items-center justify-between p-5 text-left">
                        <div>
                            <h3 class="section-title">Tambah Banyak Sekaligus</h3>
                            <p class="mt-0.5 text-xs text-slate-500">Isi beberapa supplier sekaligus tanpa harus satu per satu.</p>
                        </div>
                        <span class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600"
                              x-text="open ? 'Tutup ↑' : 'Buka ↓'"></span>
                    </button>

                    <div x-show="open" x-cloak class="border-t border-slate-200">
                        <form method="POST" action="{{ route('suppliers.bulk-store') }}" class="p-5 space-y-4">
                            @csrf
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">
                                            <th class="pb-2 pr-3 w-8">#</th>
                                            <th class="pb-2 pr-3 min-w-[160px]">Nama Supplier <span class="text-red-500">*</span></th>
                                            <th class="pb-2 pr-3 min-w-[140px]">Contact Person</th>
                                            <th class="pb-2 pr-3 min-w-[120px]">No. Telepon</th>
                                            <th class="pb-2 pr-3 min-w-[160px]">Email</th>
                                            <th class="pb-2 w-10"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(row, index) in rows" :key="row.id">
                                            <tr>
                                                <td class="pr-3 py-1.5 text-xs text-slate-400 align-middle" x-text="index + 1"></td>
                                                <td class="pr-3 py-1.5">
                                                    <input type="text"
                                                           :name="`rows[${index}][name]`"
                                                           x-model="row.name"
                                                           placeholder="Nama supplier..."
                                                           class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                                                </td>
                                                <td class="pr-3 py-1.5">
                                                    <input type="text"
                                                           :name="`rows[${index}][contact_person]`"
                                                           x-model="row.contact_person"
                                                           placeholder="Nama kontak"
                                                           class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                                                </td>
                                                <td class="pr-3 py-1.5">
                                                    <input type="text"
                                                           :name="`rows[${index}][phone]`"
                                                           x-model="row.phone"
                                                           placeholder="08xx..."
                                                           class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                                                </td>
                                                <td class="pr-3 py-1.5">
                                                    <input type="email"
                                                           :name="`rows[${index}][email]`"
                                                           x-model="row.email"
                                                           placeholder="email@contoh.com"
                                                           class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                                                </td>
                                                <td class="py-1.5 text-center">
                                                    <button type="button" @click="removeRow(row.id)"
                                                            x-show="rows.length > 1"
                                                            class="rounded-lg border border-orange-200 px-2 py-1.5 text-xs font-semibold text-orange-600 hover:bg-orange-50">✕</button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button" @click="addRow"
                                        class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                    + Tambah Baris
                                </button>
                                <button type="submit"
                                        class="inline-flex items-center rounded-xl border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">
                                    Simpan Semua
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>

    <script>
    function bulkSupplierForm() {
        return {
            open: false,
            nextId: 2,
            rows: [{ id: 1, name: '', contact_person: '', phone: '', email: '' }],
            addRow() {
                this.rows.push({ id: this.nextId++, name: '', contact_person: '', phone: '', email: '' });
            },
            removeRow(id) {
                if (this.rows.length > 1) {
                    this.rows = this.rows.filter(r => r.id !== id);
                }
            },
        };
    }
    </script>
</x-app-layout>
