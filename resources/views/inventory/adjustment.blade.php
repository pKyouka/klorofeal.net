<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="brand-badge">Inventory</span>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Manual Stock Adjustment</h2>
            </div>
            <a href="{{ route('inventory.index') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Inventory List</a>
        </div>
    </x-slot>

    <div class="py-8" x-data="adjustmentApp()" x-init="init()">
        <div class="page-container max-w-3xl space-y-4">

            {{-- Toast notifications --}}
            <div class="pointer-events-none fixed right-4 top-24 z-50 flex w-full max-w-sm flex-col gap-2">
                <template x-for="toast in toasts" :key="toast.id">
                    <div class="pointer-events-auto overflow-hidden rounded-2xl border shadow-xl backdrop-blur"
                        :class="toast.type === 'success' ? 'border-emerald-200 bg-emerald-50/95 text-emerald-900' : 'border-rose-200 bg-rose-50/95 text-rose-900'">
                        <div class="flex items-start gap-3 px-4 py-3">
                            <span class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold"
                                :class="toast.type === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                                x-text="toast.type === 'success' ? 'OK' : '!'"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold" x-text="toast.title"></p>
                                <p class="mt-0.5 text-xs opacity-80" x-text="toast.message"></p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            @if ($errors->any())
                <div class="rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
                    <ul class="list-disc ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Step 1: Product Search --}}
            <div class="surface-card overflow-hidden" x-show="!selectedProduct">
                <div class="border-b border-slate-200 p-4">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Cari Produk (Nama, SKU, atau Barcode)</label>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <input x-ref="searchInput" type="text" x-model="query"
                            @input.debounce.300ms="searchProducts"
                            @keydown.enter.prevent="handleSearchSubmit"
                            placeholder="Ketik nama produk, SKU, atau scan barcode..."
                            class="w-full px-3 py-2 text-sm">
                        <button type="button" @click="openScanner"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 whitespace-nowrap">
                            Scan Barcode
                        </button>
                    </div>

                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 font-semibold text-emerald-700">Scanner gun ready</span>
                        <span class="text-slate-500">Barcode scanner supermarket bisa langsung scan tanpa klik tombol lagi.</span>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                        <span class="rounded-full px-2.5 py-1 font-semibold"
                            :class="scannerDeviceState === 'connected' || scannerDeviceState === 'activity'
                                ? 'border border-emerald-200 bg-emerald-50 text-emerald-700'
                                : (scannerDeviceState === 'unsupported'
                                    ? 'border border-amber-200 bg-amber-50 text-amber-700'
                                    : 'border border-slate-200 bg-slate-50 text-slate-700')"
                            x-text="scannerDeviceLabel"></span>
                        <span class="text-slate-500" x-text="scannerDeviceHint"></span>
                    </div>
                    <p x-show="scannerStatus" class="mt-2 text-xs font-semibold text-teal-700" x-text="scannerStatus"></p>
                </div>

                <div class="max-h-80 overflow-y-auto">
                    <table class="table-modern">
                        <thead class="sticky top-0">
                            <tr>
                                <th class="text-left">SKU</th>
                                <th class="text-left">Barcode</th>
                                <th class="text-left">Nama</th>
                                <th class="text-right">Stok</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="product in products" :key="product.id">
                                <tr class="cursor-pointer hover:bg-teal-50" @click="selectProduct(product)">
                                    <td class="text-slate-700" x-text="product.sku"></td>
                                    <td class="text-slate-600" x-text="product.barcode || '-'"></td>
                                    <td class="font-semibold text-slate-900" x-text="product.name"></td>
                                    <td class="text-right font-semibold" x-text="product.stock"
                                        :class="product.stock <= 0 ? 'text-rose-600' : 'text-slate-700'"></td>
                                    <td class="text-right">
                                        <button type="button"
                                            class="inline-flex rounded-xl border border-teal-700 bg-teal-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-teal-800"
                                            @click.stop="selectProduct(product)">Pilih</button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="products.length === 0">
                                <td colspan="5" class="py-5 text-center text-slate-500">Ketik minimal 2 karakter atau scan barcode untuk mencari produk.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Step 2: Adjustment Form (after product is selected) --}}
            <div class="surface-card p-6 sm:p-7" x-show="selectedProduct" x-cloak>
                {{-- Selected product summary --}}
                <div class="mb-5 flex items-center gap-4 rounded-xl border border-teal-200 bg-teal-50 p-4">
                    <div class="min-w-0 flex-1">
                        <p class="mb-0.5 text-xs font-semibold uppercase tracking-wide text-teal-700">Produk dipilih</p>
                        <p class="truncate text-base font-bold text-slate-900" x-text="selectedProduct?.name"></p>
                        <p class="text-sm text-slate-600">
                            SKU: <span x-text="selectedProduct?.sku"></span>
                            <template x-if="selectedProduct?.barcode">
                                <span>&nbsp;·&nbsp; Barcode: <span x-text="selectedProduct?.barcode"></span></span>
                            </template>
                        </p>
                        <p class="mt-1 text-sm font-semibold">
                            Stok saat ini: <span class="text-teal-700" x-text="selectedProduct?.stock"></span>
                        </p>
                    </div>
                    <button type="button" @click="clearProduct"
                        class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                        Ganti Produk
                    </button>
                </div>

                <form method="POST" action="{{ route('inventory.adjustments.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="product_id" :value="selectedProduct?.id">

                    <div>
                        <label for="adjustment_type" class="mb-1 block text-sm font-semibold text-slate-700">Tipe Penyesuaian</label>
                        <select id="adjustment_type" name="adjustment_type" x-model="adjustmentType" required class="w-full px-3 py-2 text-sm">
                            <option value="increase">Tambah Stok (Increase)</option>
                            <option value="decrease">Kurangi Stok (Decrease)</option>
                            <option value="set">Set Stok Tepat (Set Exact)</option>
                        </select>
                        @error('adjustment_type')
                            <p class="mt-1 text-xs font-semibold text-orange-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="quantity" class="mb-1 block text-sm font-semibold text-slate-700">
                            <span x-show="adjustmentType !== 'set'">Jumlah Perubahan</span>
                            <span x-show="adjustmentType === 'set'">Stok Akhir yang Diinginkan</span>
                        </label>
                        <input id="quantity" name="quantity" type="number" min="0"
                            value="{{ old('quantity', 0) }}" required class="w-full px-3 py-2 text-sm">
                        <p class="mt-1 text-xs text-slate-500">
                            <span x-show="adjustmentType === 'increase'">Stok baru = stok saat ini + jumlah ini.</span>
                            <span x-show="adjustmentType === 'decrease'">Stok baru = stok saat ini − jumlah ini.</span>
                            <span x-show="adjustmentType === 'set'">Stok akan langsung diset ke angka yang dimasukkan.</span>
                        </p>
                        @error('quantity')
                            <p class="mt-1 text-xs font-semibold text-orange-700">{{ $message }}</p>
                        @enderror
                    </div>

                    @error('product_id')
                        <p class="text-xs font-semibold text-orange-700">{{ $message }}</p>
                    @enderror

                    <div class="flex items-center gap-2 pt-2">
                        <button type="submit"
                            class="inline-flex items-center rounded-xl border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">
                            Simpan Penyesuaian
                        </button>
                        <a href="{{ route('inventory.index') }}"
                            class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Camera scanner modal --}}
    <div x-show="scannerOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display: none;">
        <div class="absolute inset-0 bg-slate-900/60" @click="closeScanner"></div>

        <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                <h3 class="text-sm font-semibold text-slate-800">Scan Barcode Produk</h3>
                <button type="button" @click="closeScanner"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                    Close
                </button>
            </div>

            <div class="space-y-3 p-4">
                <div class="aspect-video overflow-hidden rounded-xl border border-slate-200 bg-slate-900">
                    <video x-ref="scannerVideo" class="h-full w-full object-cover" autoplay muted playsinline></video>
                </div>
                <p x-show="scannerError"
                    class="rounded-xl border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-semibold text-orange-700"
                    x-text="scannerError"></p>
                <p class="text-xs text-slate-500">Arahkan barcode ke kamera. Produk akan otomatis dicari.</p>
            </div>
        </div>
    </div>

    <script>
        function adjustmentApp() {
            return {
                query: '',
                products: [],
                selectedProduct: null,
                adjustmentType: 'increase',
                toasts: [],
                scannerOpen: false,
                scannerError: '',
                scannerStatus: '',
                scannerDeviceState: 'checking',
                scannerDeviceLabel: 'Checking scanner...',
                scannerDeviceHint: '',
                scannerStream: null,
                scannerInterval: null,
                barcodeDetector: null,
                audioContext: null,
                hardwareScanBuffer: '',
                hardwareScanStartedAt: 0,
                hardwareScanLastKeyAt: 0,
                hardwareScanResetTimer: null,
                hardwareScanHandler: null,
                hidConnectHandler: null,
                hidDisconnectHandler: null,
                endpoint: @json(route('inventory.products.search')),
                preselected: @json($preselected),

                async init() {
                    if (this.preselected) {
                        this.selectedProduct = this.preselected;
                    }

                    await this.setupBarcodeDetector();
                    await this.setupScannerDetection();

                    this.hardwareScanHandler = (event) => this.handleHardwareScanner(event);
                    window.addEventListener('keydown', this.hardwareScanHandler, true);

                    this.$nextTick(() => {
                        if (!this.selectedProduct) {
                            this.$refs.searchInput?.focus();
                        }
                    });
                },

                async setupBarcodeDetector() {
                    if (!('BarcodeDetector' in window)) {
                        return;
                    }

                    const desiredFormats = ['ean_13', 'ean_8', 'code_128', 'code_39', 'code_93', 'codabar', 'upc_a', 'upc_e', 'itf', 'data_matrix', 'pdf417', 'qr_code'];
                    let formats = desiredFormats;

                    if (typeof BarcodeDetector.getSupportedFormats === 'function') {
                        try {
                            const supported = await BarcodeDetector.getSupportedFormats();
                            const intersected = desiredFormats.filter((format) => supported.includes(format));
                            if (intersected.length > 0) {
                                formats = intersected;
                            }
                        } catch (_error) {
                            // Ignore and use desired format list.
                        }
                    }

                    this.barcodeDetector = new BarcodeDetector({ formats });
                },

                async setupScannerDetection() {
                    if (!('hid' in navigator)) {
                        this.scannerDeviceState = 'unsupported';
                        this.scannerDeviceLabel = 'Keyboard scanner mode';
                        this.scannerDeviceHint = 'Browser tidak bisa verifikasi koneksi fisik scanner, tetapi scan keyboard-wedge tetap bisa dipakai.';
                        return;
                    }

                    this.hidConnectHandler = () => this.refreshScannerDeviceStatus();
                    this.hidDisconnectHandler = () => this.refreshScannerDeviceStatus();
                    navigator.hid.addEventListener('connect', this.hidConnectHandler);
                    navigator.hid.addEventListener('disconnect', this.hidDisconnectHandler);
                    await this.refreshScannerDeviceStatus();
                },

                async refreshScannerDeviceStatus() {
                    if (!('hid' in navigator)) {
                        return;
                    }

                    try {
                        const devices = await navigator.hid.getDevices();
                        const detectedScanner = devices.find((device) => this.isLikelyScannerDevice(device));

                        if (detectedScanner) {
                            this.scannerDeviceState = 'connected';
                            this.scannerDeviceLabel = 'Scanner HID detected';
                            this.scannerDeviceHint = detectedScanner.productName || 'Perangkat scanner terhubung dan siap dipakai.';
                            return;
                        }

                        this.scannerDeviceState = 'waiting';
                        this.scannerDeviceLabel = 'Waiting for scanner input';
                        this.scannerDeviceHint = 'Scanner mode keyboard biasanya tidak bisa diverifikasi langsung oleh browser sebelum ada aktivitas scan.';
                    } catch (_error) {
                        this.scannerDeviceState = 'waiting';
                        this.scannerDeviceLabel = 'Scanner ready';
                        this.scannerDeviceHint = 'Menunggu aktivitas scan barcode.';
                    }
                },

                isLikelyScannerDevice(device) {
                    const productName = String(device.productName || '').toLowerCase();
                    const usageText = Array.isArray(device.collections)
                        ? device.collections.map((collection) => `${collection.usage || ''} ${collection.usagePage || ''}`).join(' ')
                        : '';
                    const signature = `${productName} ${usageText}`;

                    return /(scanner|barcode|zebra|symbol|honeywell|datalogic|newland|cipherlab|pos)/.test(signature);
                },

                showToast(type, title, message) {
                    const id = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
                    this.toasts.push({ id, type, title, message });

                    window.setTimeout(() => {
                        this.toasts = this.toasts.filter((toast) => toast.id !== id);
                    }, 2600);
                },

                async playBeep(type) {
                    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                    if (!AudioContextClass) {
                        return;
                    }

                    if (!this.audioContext) {
                        this.audioContext = new AudioContextClass();
                    }

                    if (this.audioContext.state === 'suspended') {
                        await this.audioContext.resume();
                    }

                    const sequence = type === 'success'
                        ? [{ frequency: 1260, duration: 0.08 }, { frequency: 1680, duration: 0.1, delay: 0.09 }]
                        : [{ frequency: 260, duration: 0.12 }, { frequency: 180, duration: 0.16, delay: 0.1 }];

                    sequence.forEach((tone) => {
                        const oscillator = this.audioContext.createOscillator();
                        const gainNode = this.audioContext.createGain();
                        const startAt = this.audioContext.currentTime + (tone.delay || 0);

                        oscillator.type = type === 'success' ? 'sine' : 'triangle';
                        oscillator.frequency.setValueAtTime(tone.frequency, startAt);
                        gainNode.gain.setValueAtTime(0.0001, startAt);
                        gainNode.gain.exponentialRampToValueAtTime(0.05, startAt + 0.01);
                        gainNode.gain.exponentialRampToValueAtTime(0.0001, startAt + tone.duration);

                        oscillator.connect(gainNode);
                        gainNode.connect(this.audioContext.destination);
                        oscillator.start(startAt);
                        oscillator.stop(startAt + tone.duration + 0.02);
                    });
                },

                notifyScanSuccess(message, title = 'Barcode detected') {
                    this.setScannerStatus(message);
                    this.showToast('success', title, message);
                    this.playBeep('success');
                },

                notifyScanFailure(message, title = 'Barcode not found') {
                    this.setScannerStatus(message);
                    this.showToast('error', title, message);
                    this.playBeep('error');
                },

                handleSearchSubmit() {
                    if (this.query.trim() === '') {
                        return;
                    }

                    this.processScannedCode(this.query);
                },

                searchProducts(autoAddFromScan = false, scannedCode = '') {
                    if (!autoAddFromScan && this.query.trim().length < 2) {
                        this.products = [];
                        return;
                    }

                    const url = `${this.endpoint}?q=${encodeURIComponent(this.query)}`;
                    fetch(url)
                        .then((response) => response.json())
                        .then((payload) => {
                            this.products = Array.isArray(payload.data) ? payload.data : [];

                            if (autoAddFromScan) {
                                const target = this.normalizeCode(scannedCode);
                                const exact = this.products.find((product) => {
                                    const barcode = this.normalizeCode(product.barcode || '');
                                    const sku = this.normalizeCode(product.sku || '');

                                    return target !== '' && (barcode === target || sku === target);
                                });

                                if (exact) {
                                    this.selectProduct(exact);
                                    this.notifyScanSuccess(`Produk "${exact.name}" ditemukan.`, 'Produk dipilih');
                                    return;
                                }

                                this.notifyScanFailure(`Barcode ${scannedCode} tidak ditemukan di katalog aktif.`);
                            }
                        });
                },

                normalizeCode(value) {
                    return String(value || '').trim().toUpperCase();
                },

                setScannerStatus(message) {
                    this.scannerStatus = message;

                    window.setTimeout(() => {
                        if (this.scannerStatus === message) {
                            this.scannerStatus = '';
                        }
                    }, 2200);
                },

                processScannedCode(code) {
                    const normalized = this.normalizeCode(code);
                    if (normalized === '') {
                        return;
                    }

                    this.query = normalized;
                    this.searchProducts(true, normalized);
                },

                selectProduct(product) {
                    this.selectedProduct = product;
                    this.query = '';
                    this.products = [];
                },

                clearProduct() {
                    this.selectedProduct = null;
                    this.$nextTick(() => this.$refs.searchInput?.focus());
                },

                isEditableElement(element) {
                    if (!element) {
                        return false;
                    }

                    const tagName = element.tagName;
                    return tagName === 'INPUT' || tagName === 'TEXTAREA' || tagName === 'SELECT' || element.isContentEditable;
                },

                resetHardwareScannerBuffer() {
                    this.hardwareScanBuffer = '';
                    this.hardwareScanStartedAt = 0;
                    this.hardwareScanLastKeyAt = 0;

                    if (this.hardwareScanResetTimer) {
                        window.clearTimeout(this.hardwareScanResetTimer);
                        this.hardwareScanResetTimer = null;
                    }
                },

                isLikelyHardwareScan() {
                    if (this.hardwareScanBuffer.length < 6) {
                        return false;
                    }

                    if (!/^[0-9A-Za-z._\/-]+$/.test(this.hardwareScanBuffer)) {
                        return false;
                    }

                    return (Date.now() - this.hardwareScanStartedAt) < 1500;
                },

                handleHardwareScanner(event) {
                    if (this.scannerOpen) {
                        return;
                    }

                    if (event.ctrlKey || event.altKey || event.metaKey) {
                        this.resetHardwareScannerBuffer();
                        return;
                    }

                    const activeElement = document.activeElement;
                    const searchInput = this.$refs.searchInput;

                    if (this.isEditableElement(activeElement) && activeElement !== searchInput) {
                        this.resetHardwareScannerBuffer();
                        return;
                    }

                    if (event.key === 'Enter') {
                        if (this.isLikelyHardwareScan()) {
                            event.preventDefault();
                            this.scannerDeviceState = 'activity';
                            this.scannerDeviceLabel = 'Scanner activity detected';
                            this.scannerDeviceHint = 'Input scanner diterima dan sedang diproses.';
                            this.processScannedCode(this.hardwareScanBuffer);
                        }

                        this.resetHardwareScannerBuffer();
                        return;
                    }

                    if (!/^[0-9A-Za-z._-]$/.test(event.key)) {
                        this.resetHardwareScannerBuffer();
                        return;
                    }

                    const now = Date.now();
                    if (this.hardwareScanLastKeyAt !== 0 && (now - this.hardwareScanLastKeyAt) > 80) {
                        this.hardwareScanBuffer = '';
                        this.hardwareScanStartedAt = 0;
                    }

                    if (this.hardwareScanBuffer === '') {
                        this.hardwareScanStartedAt = now;
                    }

                    this.hardwareScanBuffer += event.key;
                    this.hardwareScanLastKeyAt = now;

                    if (this.hardwareScanResetTimer) {
                        window.clearTimeout(this.hardwareScanResetTimer);
                    }

                    this.hardwareScanResetTimer = window.setTimeout(() => {
                        this.resetHardwareScannerBuffer();
                    }, 120);
                },

                openScanner() {
                    this.scannerOpen = true;
                    this.startScanner();
                },

                closeScanner() {
                    this.stopScanner();
                    this.scannerOpen = false;
                },

                async startScanner() {
                    this.scannerError = '';

                    if (!this.barcodeDetector) {
                        this.scannerError = 'Browser belum mendukung BarcodeDetector API. Gunakan scanner USB di kolom pencarian.';
                        return;
                    }

                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                        this.scannerError = 'Kamera tidak didukung di browser ini.';
                        return;
                    }

                    try {
                        this.scannerStream = await navigator.mediaDevices.getUserMedia({
                            video: { facingMode: { ideal: 'environment' } },
                            audio: false,
                        });

                        const video = this.$refs.scannerVideo;
                        video.srcObject = this.scannerStream;
                        await video.play();

                        this.scannerInterval = window.setInterval(async () => {
                            if (!this.scannerOpen) {
                                return;
                            }

                            try {
                                const detected = await this.barcodeDetector.detect(video);
                                if (Array.isArray(detected) && detected.length > 0 && detected[0].rawValue) {
                                    this.processScannedCode(detected[0].rawValue);
                                    this.closeScanner();
                                }
                            } catch (_error) {
                                // Ignore intermittent decode errors and keep scanning.
                            }
                        }, 250);
                    } catch (_error) {
                        this.scannerError = 'Gagal mengakses kamera. Pastikan izin kamera sudah diizinkan.';
                        this.stopScanner();
                    }
                },

                stopScanner() {
                    if (this.scannerInterval) {
                        window.clearInterval(this.scannerInterval);
                        this.scannerInterval = null;
                    }

                    if (this.scannerStream) {
                        this.scannerStream.getTracks().forEach((track) => track.stop());
                        this.scannerStream = null;
                    }

                    if (this.$refs.scannerVideo) {
                        this.$refs.scannerVideo.pause();
                        this.$refs.scannerVideo.srcObject = null;
                    }
                },
            };
        }
    </script>
</x-app-layout>
