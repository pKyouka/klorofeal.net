<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="brand-badge">Master Data</span>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Create Product</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-container max-w-4xl" x-data="productCreateScanner()" x-init="init()">
            <div class="surface-card p-6 sm:p-7">
                <form method="POST" action="{{ route('products.store') }}" class="space-y-5">
                    @include('products._form', ['scannerMode' => true])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    function productCreateScanner() {
        return {
            lookupEndpoint: @json(route('products.lookup-open-food-facts')),
            generateEndpoint: @json(route('products.generate-sku')),
            skuGenerating: false,
            lookupState: 'idle',
            lookupMessage: '',
            lookupProduct: null,
            lastLookupBarcode: '',
            hardwareScanBuffer: '',
            hardwareScanStartedAt: 0,
            hardwareScanLastKeyAt: 0,
            hardwareScanResetTimer: null,
            hardwareScanHandler: null,

            async init() {
                this.hardwareScanHandler = (event) => this.handleHardwareScanner(event);
                window.addEventListener('keydown', this.hardwareScanHandler, true);

                this.$nextTick(() => {
                    this.focusBarcode();
                });
            },

            focusBarcode() {
                this.$refs.barcodeInput?.focus();
                this.$refs.barcodeInput?.select();
            },

            handleBarcodeTyping() {
                // SKU is generated from name + category, not copied from barcode.
            },

            completeBarcodeEntry() {
                const currentValue = this.$refs.barcodeInput?.value || '';
                if (String(currentValue).trim() === '') {
                    return;
                }

                this.applyScannedBarcode(currentValue, 'input barcode');
            },

            applyScannedBarcode(rawValue, source = 'scanner') {
                const normalized = String(rawValue || '').trim();
                if (normalized === '') {
                    return;
                }

                if (this.$refs.barcodeInput) {
                    this.$refs.barcodeInput.value = normalized;
                    this.$refs.barcodeInput.dispatchEvent(new Event('input', { bubbles: true }));
                }

                this.lookupOpenFoodFacts(normalized, source);
            },

            async lookupOpenFoodFacts(barcode, source = 'scanner') {
                if (!barcode || barcode.length < 6) {
                    this.lookupState = 'idle';
                    this.lookupProduct = null;
                    this.lookupMessage = '';
                    this.$refs.nameInput?.focus();
                    return;
                }

                if (this.lastLookupBarcode === barcode && this.lookupState === 'success') {
                    this.$refs.nameInput?.focus();
                    return;
                }

                this.lookupState = 'loading';
                this.lookupMessage = `Mencari produk ${barcode} dari OpenFoodFacts...`;
                this.lookupProduct = null;

                try {
                    const url = `${this.lookupEndpoint}?barcode=${encodeURIComponent(barcode)}`;
                    const response = await fetch(url, {
                        method: 'GET',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const payload = await response.json();

                    if (!response.ok) {
                        this.lookupState = 'error';
                        this.lookupMessage = response.status === 422
                            ? (payload.message || 'Barcode tidak valid.')
                            : 'Layanan OpenFoodFacts sedang lambat. Lanjut isi manual.';
                        this.lastLookupBarcode = barcode;
                        this.$refs.nameInput?.focus();
                        return;
                    }

                    if (!payload.found) {
                        this.lookupState = 'error';
                        this.lookupMessage = payload.message || 'Produk tidak ditemukan di OpenFoodFacts.';
                        this.lastLookupBarcode = barcode;
                        this.$refs.nameInput?.focus();
                        return;
                    }

                    this.lookupState = 'success';
                    this.lookupProduct = payload.product || null;
                    this.lastLookupBarcode = barcode;
                    this.lookupMessage = `Produk cocok dari OpenFoodFacts (${source}). Harga tetap isi dari internal.`;

                    if (this.$refs.nameInput && this.$refs.nameInput.value.trim() === '' && this.lookupProduct?.name) {
                        this.$refs.nameInput.value = this.lookupProduct.name;
                        this.$refs.nameInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }

                    if (this.$refs.categorySelect && String(this.$refs.categorySelect.value || '').trim() === '' && this.lookupProduct?.suggested_category_id) {
                        this.$refs.categorySelect.value = String(this.lookupProduct.suggested_category_id);
                        this.$refs.categorySelect.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    // Auto-generate SKU from name + category if the field is still empty
                    this.generate(false);

                    this.$nextTick(() => {
                        if (this.$refs.costPriceInput && String(this.$refs.costPriceInput.value || '').trim() === '0') {
                            this.$refs.costPriceInput.focus();
                            this.$refs.costPriceInput.select();
                            return;
                        }

                        this.$refs.nameInput?.focus();
                    });
                } catch (_error) {
                    this.lookupState = 'error';
                    this.lookupMessage = 'Tidak bisa mengambil data produk dari OpenFoodFacts. Lanjut isi manual.';
                    this.lastLookupBarcode = barcode;
                    this.$refs.nameInput?.focus();
                }
            },

            resetHardwareScanBuffer() {
                this.hardwareScanBuffer = '';
                this.hardwareScanStartedAt = 0;
                this.hardwareScanLastKeyAt = 0;

                if (this.hardwareScanResetTimer) {
                    clearTimeout(this.hardwareScanResetTimer);
                    this.hardwareScanResetTimer = null;
                }
            },

            handleHardwareScanner(event) {
                if (event.ctrlKey || event.metaKey || event.altKey) {
                    return;
                }

                const activeElement = document.activeElement;
                const activeIsBarcode = activeElement === this.$refs.barcodeInput;
                const activeIsInput = activeElement && ['INPUT', 'TEXTAREA', 'SELECT'].includes(activeElement.tagName);

                if (activeIsBarcode) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        this.completeBarcodeEntry();
                    }
                    return;
                }

                if (activeIsInput) {
                    return;
                }

                const now = Date.now();
                const isPrintable = event.key.length === 1 && /[0-9A-Za-z._\-/]/.test(event.key);

                if (isPrintable) {
                    if (!this.hardwareScanStartedAt || now - this.hardwareScanLastKeyAt > 90) {
                        this.hardwareScanBuffer = '';
                        this.hardwareScanStartedAt = now;
                    }

                    this.hardwareScanBuffer += event.key;
                    this.hardwareScanLastKeyAt = now;

                    if (this.hardwareScanResetTimer) {
                        clearTimeout(this.hardwareScanResetTimer);
                    }

                    this.hardwareScanResetTimer = setTimeout(() => this.resetHardwareScanBuffer(), 180);
                    event.preventDefault();
                    event.stopPropagation();
                    return;
                }

                if (event.key === 'Enter' && this.hardwareScanBuffer.length >= 6) {
                    const duration = now - this.hardwareScanStartedAt;
                    const scanned = this.hardwareScanBuffer;
                    this.resetHardwareScanBuffer();

                    if (duration <= 1200) {
                        event.preventDefault();
                        event.stopPropagation();
                        this.applyScannedBarcode(scanned, 'hardware scanner');
                    }
                    return;
                }

                this.resetHardwareScanBuffer();
            },

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
