<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="brand-badge">Master Data</span>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Product Categories</h2>
            </div>
            <a href="{{ route('product-categories.create') }}" class="inline-flex items-center rounded-xl border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">Add Category</a>
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
                                <th class="text-left">Slug</th>
                                <th class="text-left">Products</th>
                                <th class="text-left">Description</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $category)
                                <tr>
                                    <td class="font-semibold text-slate-900">{{ $category->name }}</td>
                                    <td class="text-slate-600">{{ $category->slug }}</td>
                                    <td class="text-slate-600">{{ $category->products_count }}</td>
                                    <td class="text-slate-600">{{ $category->description ?: '-' }}</td>
                                    <td>
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('product-categories.edit', $category) }}" class="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Edit</a>
                                            <form method="POST" action="{{ route('product-categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-xl border border-orange-300 px-3 py-1.5 text-xs font-semibold text-orange-700 hover:bg-orange-50">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-slate-500">No categories found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $categories->links() }}
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
            <div class="surface-card" x-data="bulkCategoryForm()">
                <button type="button" @click="open = !open"
                        class="flex w-full items-center justify-between p-5 text-left">
                    <div>
                        <h3 class="section-title">Tambah Banyak Sekaligus</h3>
                        <p class="mt-0.5 text-xs text-slate-500">Isi beberapa kategori sekaligus tanpa harus satu per satu.</p>
                    </div>
                    <span class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600"
                          x-text="open ? 'Tutup ↑' : 'Buka ↓'"></span>
                </button>

                <div x-show="open" x-cloak class="border-t border-slate-200">
                    <form method="POST" action="{{ route('product-categories.bulk-store') }}" class="p-5 space-y-4">
                        @csrf
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">
                                        <th class="pb-2 pr-3 w-8">#</th>
                                        <th class="pb-2 pr-3 min-w-[180px]">Nama Kategori <span class="text-red-500">*</span></th>
                                        <th class="pb-2 pr-3 min-w-[200px]">Deskripsi</th>
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
                                                       placeholder="Nama kategori..."
                                                       class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                                            </td>
                                            <td class="pr-3 py-1.5">
                                                <input type="text"
                                                       :name="`rows[${index}][description]`"
                                                       x-model="row.description"
                                                       placeholder="Deskripsi (opsional)"
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
</x-app-layout>

<script>
function bulkCategoryForm() {
    return {
        open: false,
        nextId: 2,
        rows: [{ id: 1, name: '', description: '' }],
        addRow() {
            this.rows.push({ id: this.nextId++, name: '', description: '' });
        },
        removeRow(id) {
            if (this.rows.length > 1) {
                this.rows = this.rows.filter(r => r.id !== id);
            }
        },
    };
}
</script>
