<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="brand-badge">Master Data</span>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Edit Product</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-container max-w-4xl" x-data="productEditForm()">
            <div class="surface-card p-6 sm:p-7">
                <form method="POST" action="{{ route('products.update', $product) }}" class="space-y-5">
                    @method('PUT')
                    @include('products._form')
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    function productEditForm() {
        return {
            generateEndpoint: @json(route('products.generate-sku')),
            skuGenerating: false,
            async generate(forceOverwrite = true) {
                if (this.skuGenerating) return;
                this.skuGenerating = true;
                const skuEl = this.$refs.skuInput ?? document.getElementById('sku');
                if (!forceOverwrite && skuEl && skuEl.value.trim() !== '') {
                    this.skuGenerating = false;
                    return;
                }
                const categoryId = (this.$refs.categorySelect ?? document.getElementById('category_id'))?.value ?? '';
                const name = (this.$refs.nameInput ?? document.getElementById('name'))?.value ?? '';
                try {
                    const url = `${this.generateEndpoint}?category_id=${encodeURIComponent(categoryId)}&name=${encodeURIComponent(name)}`;
                    const res = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    const data = await res.json();
                    if (data.sku && skuEl) {
                        skuEl.value = data.sku;
                    }
                } catch (_) {}
                this.skuGenerating = false;
            },
        };
    }
</script>
