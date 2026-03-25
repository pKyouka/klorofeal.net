<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="brand-badge">Master Data</span>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Products</h2>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('products.import.form') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">Import CSV</a>
                <a href="{{ route('products.create') }}" class="inline-flex items-center rounded-xl border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">Add Product</a>
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

            @if ($errors->any())
                <div class="rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
                    <ul class="list-disc ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="surface-card space-y-5 p-5 sm:p-6">
                <div class="products-toolbar">
                    <div>
                        <h3 class="section-title">Interactive Product Table</h3>
                        <p class="section-subtitle mt-1">Cari, urutkan, dan filter produk lebih cepat langsung dari satu tabel.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1.7fr)_220px_180px_auto]">
                        <input
                            id="products-table-search"
                            type="text"
                            placeholder="Search name, SKU, or barcode"
                            class="w-full px-3 py-2.5 text-sm"
                        >

                        <select id="products-table-category" class="w-full px-3 py-2.5 text-sm">
                            <option value="">All Categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>

                        <select id="products-table-status" class="w-full px-3 py-2.5 text-sm">
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>

                        <button id="products-table-reset" type="button" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</button>
                    </div>
                </div>

                <div class="table-shell products-datatable-shell">
                    <div class="overflow-x-auto">
                        <table id="products-datatable" class="table-modern" data-source-url="{{ route('products.datatable') }}">
                            <thead>
                                <tr>
                                    <th class="text-left">SKU</th>
                                    <th class="text-left">Barcode</th>
                                    <th class="text-left">Name</th>
                                    <th class="text-left">Category</th>
                                    <th class="text-right">Cost</th>
                                    <th class="text-right">Sell</th>
                                    <th class="text-right">Stock</th>
                                    <th class="text-left">Status</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
