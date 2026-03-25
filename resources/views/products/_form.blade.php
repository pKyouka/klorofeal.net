@php($scannerMode = $scannerMode ?? false)

@csrf

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div class="md:col-span-2">
        <label for="barcode" class="mb-1 block text-sm font-semibold text-slate-700">Barcode @if (! $scannerMode)<span class="text-slate-400">(Optional)</span>@endif</label>
        <input
            id="barcode"
            name="barcode"
            type="text"
            value="{{ old('barcode', $product->barcode ?? '') }}"
            placeholder="Scan atau ketik barcode di sini..."
            class="w-full px-3 py-2 text-sm"
            @if ($scannerMode)
                x-ref="barcodeInput"
                @input="handleBarcodeTyping($event.target.value)"
                @keydown.enter.prevent="completeBarcodeEntry()"
                autofocus
            @endif
        >
        @if ($scannerMode)
            <p class="mt-2 text-xs text-slate-500">Scan dulu di barcode, lalu lanjut isi nama dan data produk lain.</p>

            <div x-show="lookupState === 'loading'" class="mt-2 rounded-xl border border-cyan-200 bg-cyan-50 px-3 py-2 text-xs font-semibold text-cyan-700" style="display: none;" x-text="lookupMessage"></div>
            <div x-show="lookupState === 'error' && lookupMessage" class="mt-2 rounded-xl border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-semibold text-orange-700" style="display: none;" x-text="lookupMessage"></div>

            <div x-show="lookupState === 'success' && lookupProduct" class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50/70 p-3" style="display: none;">
                <div class="flex items-start gap-3">
                    <img x-show="lookupProduct.image_url" :src="lookupProduct.image_url" alt="OpenFoodFacts product" class="h-14 w-14 rounded-lg border border-emerald-200 bg-white object-cover" style="display: none;">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold uppercase tracking-[0.1em] text-emerald-700">OpenFoodFacts Match</p>
                        <p class="mt-1 text-sm font-semibold text-emerald-900" x-text="lookupProduct.name || 'Nama produk tidak tersedia'"></p>
                        <p class="mt-1 text-xs text-emerald-800" x-show="lookupProduct.brand" style="display: none;">Brand: <span class="font-semibold" x-text="lookupProduct.brand"></span></p>
                        <p class="mt-1 text-xs text-emerald-800" x-show="lookupProduct.quantity" style="display: none;">Netto: <span class="font-semibold" x-text="lookupProduct.quantity"></span></p>
                        <p class="mt-1 text-xs text-emerald-800" x-show="lookupProduct.suggested_category_name" style="display: none;">Kategori lokal: <span class="font-semibold" x-text="lookupProduct.suggested_category_name"></span></p>
                        <p class="mt-1 text-xs font-semibold text-emerald-700" x-text="lookupMessage"></p>
                    </div>
                </div>
            </div>
        @endif
        @error('barcode')
            <p class="mt-1 text-xs font-semibold text-orange-700">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="sku" class="mb-1 block text-sm font-semibold text-slate-700">SKU</label>
        <div class="flex gap-2">
            <input
                id="sku"
                name="sku"
                type="text"
                value="{{ old('sku', $product->sku ?? '') }}"
                required
                placeholder="Generate otomatis atau ketik manual"
                class="w-full px-3 py-2 text-sm"
                x-ref="skuInput"
            >
            <button
                type="button"
                @click="generate()"
                :disabled="skuGenerating"
                title="Generate SKU dari nama dan kategori"
                class="flex-shrink-0 inline-flex items-center rounded-xl border border-teal-600 bg-teal-600 px-3 py-2 text-xs font-semibold text-white hover:bg-teal-700 disabled:opacity-60 transition"
            >
                <span x-show="!skuGenerating">Generate</span>
                <span x-show="skuGenerating" style="display:none">...</span>
            </button>
        </div>
        @error('sku')
            <p class="mt-1 text-xs font-semibold text-orange-700">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="name" class="mb-1 block text-sm font-semibold text-slate-700">Product Name</label>
        <input id="name" name="name" type="text" value="{{ old('name', $product->name ?? '') }}" required class="w-full px-3 py-2 text-sm" @if ($scannerMode) x-ref="nameInput" @endif>
        @error('name')
            <p class="mt-1 text-xs font-semibold text-orange-700">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="category_id" class="mb-1 block text-sm font-semibold text-slate-700">Category</label>
        <select id="category_id" name="category_id" class="w-full px-3 py-2 text-sm" @if ($scannerMode) x-ref="categorySelect" @endif>
            <option value="">No Category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id ?? '') === (string) $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        @error('category_id')
            <p class="mt-1 text-xs font-semibold text-orange-700">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="stock" class="mb-1 block text-sm font-semibold text-slate-700">Stock</label>
        <input id="stock" name="stock" type="number" min="0" value="{{ old('stock', $product->stock ?? 0) }}" required class="w-full px-3 py-2 text-sm">
        @error('stock')
            <p class="mt-1 text-xs font-semibold text-orange-700">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="cost_price" class="mb-1 block text-sm font-semibold text-slate-700">Cost Price</label>
        <input id="cost_price" name="cost_price" type="number" step="0.01" min="0" value="{{ old('cost_price', $product->cost_price ?? 0) }}" required class="w-full px-3 py-2 text-sm" @if ($scannerMode) x-ref="costPriceInput" @endif>
        @error('cost_price')
            <p class="mt-1 text-xs font-semibold text-orange-700">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="sell_price" class="mb-1 block text-sm font-semibold text-slate-700">Sell Price</label>
        <input id="sell_price" name="sell_price" type="number" step="0.01" min="0" value="{{ old('sell_price', $product->sell_price ?? 0) }}" required class="w-full px-3 py-2 text-sm">
        @error('sell_price')
            <p class="mt-1 text-xs font-semibold text-orange-700">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-4">
    <input type="hidden" name="is_active" value="0">
    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', isset($product) ? $product->is_active : true)) class="rounded border-slate-300 text-teal-700 shadow-sm focus:ring-teal-500">
        Active Product
    </label>
</div>

<div class="mt-6 flex items-center gap-2">
    <button type="submit" class="inline-flex items-center rounded-xl border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">Save</button>
    <a href="{{ route('products.index') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
</div>
