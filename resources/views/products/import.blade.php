<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="brand-badge">Master Data</span>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Import Produk via CSV</h2>
            </div>
            <a href="{{ route('products.index') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">← Daftar Produk</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-container max-w-3xl space-y-4">

            {{-- Import result --}}
            @if (session('import_result'))
                @php $result = session('import_result'); @endphp
                <div class="surface-card p-5 space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">✓</span>
                        <span class="font-semibold text-emerald-700">{{ $result['imported'] }} produk berhasil diimport.</span>
                    </div>
                    @if (!empty($result['errors']))
                        <div class="space-y-1">
                            <p class="text-xs font-semibold text-orange-700">{{ count($result['errors']) }} baris gagal:</p>
                            @foreach ($result['errors'] as $error)
                                <p class="rounded-lg bg-orange-50 px-3 py-1.5 text-xs text-orange-700">{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            {{-- Instructions card --}}
            <div class="surface-card p-5 sm:p-6 space-y-4">
                <h3 class="section-title">Format File CSV</h3>
                <p class="text-sm text-slate-600">Buat file CSV dengan baris pertama sebagai header, kolom berikut:</p>

                <div class="overflow-x-auto">
                    <table class="table-modern text-xs">
                        <thead>
                            <tr>
                                <th class="text-left">Kolom</th>
                                <th class="text-left">Wajib</th>
                                <th class="text-left">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="font-mono font-semibold">sku</td>
                                <td><span class="rounded bg-red-100 px-1.5 py-0.5 text-red-700 text-xs font-semibold">Wajib</span></td>
                                <td class="text-slate-600">Kode unik produk (harus unik di seluruh sistem)</td>
                            </tr>
                            <tr>
                                <td class="font-mono font-semibold">barcode</td>
                                <td><span class="text-slate-400 text-xs">Opsional</span></td>
                                <td class="text-slate-600">Barcode EAN/UPC — boleh dikosongkan</td>
                            </tr>
                            <tr>
                                <td class="font-mono font-semibold">name</td>
                                <td><span class="rounded bg-red-100 px-1.5 py-0.5 text-red-700 text-xs font-semibold">Wajib</span></td>
                                <td class="text-slate-600">Nama produk</td>
                            </tr>
                            <tr>
                                <td class="font-mono font-semibold">category</td>
                                <td><span class="text-slate-400 text-xs">Opsional</span></td>
                                <td class="text-slate-600">Nama kategori — akan dibuat otomatis bila belum ada</td>
                            </tr>
                            <tr>
                                <td class="font-mono font-semibold">cost_price</td>
                                <td><span class="rounded bg-red-100 px-1.5 py-0.5 text-red-700 text-xs font-semibold">Wajib</span></td>
                                <td class="text-slate-600">Harga beli (angka, tanpa pemisah ribuan)</td>
                            </tr>
                            <tr>
                                <td class="font-mono font-semibold">sell_price</td>
                                <td><span class="rounded bg-red-100 px-1.5 py-0.5 text-red-700 text-xs font-semibold">Wajib</span></td>
                                <td class="text-slate-600">Harga jual (angka, tanpa pemisah ribuan)</td>
                            </tr>
                            <tr>
                                <td class="font-mono font-semibold">stock</td>
                                <td><span class="rounded bg-red-100 px-1.5 py-0.5 text-red-700 text-xs font-semibold">Wajib</span></td>
                                <td class="text-slate-600">Stok awal (bilangan bulat ≥ 0)</td>
                            </tr>
                            <tr>
                                <td class="font-mono font-semibold">is_active</td>
                                <td><span class="text-slate-400 text-xs">Opsional</span></td>
                                <td class="text-slate-600">1 = aktif, 0 = non-aktif (default: 1 bila kosong)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <a href="{{ route('products.import.template') }}"
                   class="inline-flex items-center gap-1.5 rounded-xl border border-teal-600 bg-teal-50 px-4 py-2 text-sm font-semibold text-teal-700 hover:bg-teal-100">
                    ↓ Download Template CSV
                </a>
            </div>

            {{-- Upload form --}}
            <div class="surface-card p-5 sm:p-6 space-y-4">
                <h3 class="section-title">Upload File CSV</h3>

                @if ($errors->any())
                    <div class="rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
                        <ul class="list-disc ps-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('products.import') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label for="file" class="mb-1.5 block text-sm font-semibold text-slate-700">File CSV <span class="text-red-500">*</span></label>
                        <input
                            id="file"
                            name="file"
                            type="file"
                            accept=".csv,.txt"
                            required
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700
                                   file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:px-3 file:py-1
                                   file:text-sm file:font-semibold file:text-teal-700 hover:file:bg-teal-100
                                   focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        >
                        <p class="mt-1 text-xs text-slate-500">Format CSV (comma-separated). Maksimal 2&nbsp;MB.</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit"
                                class="inline-flex items-center rounded-xl border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">
                            Upload &amp; Import
                        </button>
                        <a href="{{ route('products.index') }}"
                           class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Batal
                        </a>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
