<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="brand-badge">Sales Desk</span>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">POS Cashier</h2>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if ($lastSale)
                    <a href="{{ route('sales.receipt', ['sale' => $lastSale, 'autoprint' => 1]) }}" target="_blank" rel="noopener" class="inline-flex items-center rounded-xl border border-slate-900 bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">Print Last Receipt</a>
                @else
                    <span class="inline-flex items-center rounded-xl border border-slate-200 bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-400">Print Last Receipt</span>
                @endif

                <a href="{{ route('sales.index') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Sales History</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6" x-data="posApp()" x-init="init()">
        <div class="page-container space-y-4">
            <div class="pointer-events-none fixed right-4 top-24 z-50 flex w-full max-w-sm flex-col gap-2">
                <template x-for="toast in toasts" :key="toast.id">
                    <div class="pointer-events-auto overflow-hidden rounded-2xl border shadow-xl backdrop-blur" :class="toast.type === 'success' ? 'border-emerald-200 bg-emerald-50/95 text-emerald-900' : 'border-rose-200 bg-rose-50/95 text-rose-900'">
                        <div class="flex items-start gap-3 px-4 py-3">
                            <span class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold" :class="toast.type === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'" x-text="toast.type === 'success' ? 'OK' : '!' "></span>
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

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-5">
                <div class="xl:col-span-3">
                    <div class="surface-card overflow-hidden">
                        <div class="border-b border-slate-200 p-4">
                            <label for="search" class="mb-2 block text-sm font-semibold text-slate-700">Search Product by Name, SKU, or Barcode</label>
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                <input id="search" x-ref="searchInput" type="text" x-model="query" @input.debounce.300ms="searchProducts" @keydown.enter.prevent="handleSearchSubmit" placeholder="Type product name, SKU, or barcode..." class="w-full px-3 py-2 text-sm">
                                <button type="button" @click="openScanner" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                    Scan Barcode
                                </button>
                            </div>
                        </div>

                        <div class="max-h-[540px] overflow-y-auto">
                            <table class="table-modern">
                                <thead class="sticky top-0">
                                    <tr>
                                        <th class="text-left">SKU</th>
                                        <th class="text-left">Barcode</th>
                                        <th class="text-left">Name</th>
                                        <th class="text-right">Price</th>
                                        <th class="text-right">Stock</th>
                                        <th class="text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="product in products" :key="product.id">
                                        <tr>
                                            <td class="text-slate-700" x-text="product.sku"></td>
                                            <td class="text-slate-600" x-text="product.barcode || '-'"></td>
                                            <td class="font-semibold text-slate-900" x-text="product.name"></td>
                                            <td class="text-right text-slate-700" x-text="formatCurrency(product.sell_price)"></td>
                                            <td class="text-right" :class="product.stock > 0 ? 'text-slate-700' : 'text-orange-700'" x-text="product.stock"></td>
                                            <td class="text-right">
                                                <button type="button" class="inline-flex rounded-xl border border-teal-700 bg-teal-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-50" :disabled="product.stock < 1" @click="addToCart(product)">Add</button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="products.length === 0">
                                        <td colspan="6" class="py-5 text-center text-slate-500" x-text="query.trim().length < 2 ? 'Ketik minimal 2 karakter untuk mencari produk.' : 'Produk tidak ditemukan.'"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-2">
                    <div class="surface-card sticky top-4 overflow-hidden">
                        <form x-ref="checkoutForm" method="POST" action="{{ route('sales.store') }}" @submit="submitSale">
                            @csrf
                            <input type="hidden" name="items" :value="serializedItems">
                            <input type="hidden" name="midtrans_order_id" :value="midtransOrderId">

                            <div class="border-b border-slate-200 p-4">
                                <h3 class="text-base font-semibold text-slate-800">Cart</h3>
                            </div>

                            <div class="space-y-2 border-b border-slate-200 bg-slate-50/70 p-4 text-xs">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 font-semibold text-emerald-700">Scanner gun ready</span>
                                    <span class="text-slate-500">Siap scan tanpa klik tombol, status ditampilkan di panel kanan.</span>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full px-2.5 py-1 font-semibold" :class="scannerDeviceState === 'connected' || scannerDeviceState === 'activity' ? 'border border-emerald-200 bg-emerald-50 text-emerald-700' : (scannerDeviceState === 'unsupported' ? 'border border-amber-200 bg-amber-50 text-amber-700' : 'border border-slate-200 bg-white text-slate-700')" x-text="scannerDeviceLabel"></span>
                                    <span class="text-slate-500" x-text="scannerDeviceHint"></span>
                                </div>

                                @if ($lastSale)
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 font-semibold text-sky-700">Last receipt ready</span>
                                        <span class="text-slate-500">{{ $lastSale->invoice_number }}</span>
                                    </div>
                                @endif

                                <p x-show="scannerStatus" class="font-semibold text-teal-700" x-text="scannerStatus"></p>
                            </div>

                            <div class="max-h-[280px] overflow-y-auto px-4 py-3">
                                <template x-for="item in cart" :key="item.product_id">
                                    <div class="mb-3 rounded-xl border border-slate-200 p-3">
                                        <p class="text-sm font-semibold text-slate-900" x-text="item.name"></p>
                                        <p class="text-xs text-slate-500" x-text="item.sku"></p>
                                        <div class="mt-2 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <button type="button" class="h-7 w-7 rounded border border-slate-300 text-slate-700" @click="decrementQty(item.product_id)">-</button>
                                                <span class="min-w-8 text-center text-sm font-semibold" x-text="item.qty"></span>
                                                <button type="button" class="h-7 w-7 rounded border border-slate-300 text-slate-700" @click="incrementQty(item.product_id)">+</button>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-xs text-slate-500" x-text="formatCurrency(item.price) + ' x ' + item.qty"></p>
                                                <p class="text-sm font-semibold text-slate-900" x-text="formatCurrency(item.price * item.qty)"></p>
                                            </div>
                                        </div>
                                        <div class="mt-2 text-right">
                                            <button type="button" class="text-xs font-semibold text-orange-700 hover:text-orange-800" @click="removeItem(item.product_id)">Remove</button>
                                        </div>
                                    </div>
                                </template>
                                <p x-show="cart.length === 0" class="py-8 text-center text-sm text-slate-500">Cart is empty.</p>
                            </div>

                            <div class="space-y-3 border-t border-slate-200 p-4">
                                <div>
                                    <label for="payment_method" class="mb-1 block text-sm font-semibold text-slate-700">Payment Method</label>
                                    <select id="payment_method" name="payment_method" x-model="paymentMethod" @change="handlePaymentMethodChange" class="w-full px-3 py-2 text-sm">
                                        <option value="cash">Cash</option>
                                        <option value="transfer">Transfer</option>
                                        <option value="qris">QRIS</option>
                                    </select>
                                    <p x-show="paymentMethod === 'qris'" class="mt-1 text-xs text-slate-500" style="display: none;">Transaksi akan diverifikasi melalui Midtrans QRIS sebelum disimpan.</p>
                                </div>

                                <div>
                                    <label for="tax_amount" class="mb-1 block text-sm font-semibold text-slate-700">Tax (Optional)</label>
                                    <input id="tax_amount" type="number" min="0" step="0.01" name="tax_amount" x-model.number="taxAmount" class="w-full px-3 py-2 text-sm">
                                </div>

                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm">
                                    <div class="flex items-center justify-between py-1">
                                        <span class="text-slate-600">Subtotal</span>
                                        <span class="font-semibold text-slate-900" x-text="formatCurrency(subtotal)"></span>
                                    </div>
                                    <div class="flex items-center justify-between py-1">
                                        <span class="text-slate-600">Tax</span>
                                        <span class="font-semibold text-slate-900" x-text="formatCurrency(safeTax)"></span>
                                    </div>
                                    <div class="mt-1 flex items-center justify-between border-t border-slate-200 pt-2">
                                        <span class="text-slate-700">Grand Total</span>
                                        <span class="text-lg font-bold text-slate-900" x-text="formatCurrency(grandTotal)"></span>
                                    </div>
                                </div>

                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-teal-700 bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-50" :disabled="cart.length === 0 || qrisLoading || qrisChecking || isSubmitting">
                                    <span x-show="paymentMethod !== 'qris'" style="display: none;">Complete Transaction</span>
                                    <span x-show="paymentMethod === 'qris' && !qrisLoading && !qrisChecking" style="display: none;">Bayar dengan QRIS</span>
                                    <span x-show="qrisLoading" style="display: none;">Membuat QRIS...</span>
                                    <span x-show="qrisChecking" style="display: none;">Memeriksa pembayaran...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="qrisModalOpen" x-transition.opacity class="fixed inset-0 z-[70] flex items-center justify-center px-4" style="display: none;">
        <div class="absolute inset-0 bg-slate-900/60" @click="closeQrisModal"></div>

        <div class="relative z-10 w-full max-w-sm overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div class="border-b border-slate-200 bg-teal-50/70 px-5 py-4 text-center">
                <p class="text-xs font-semibold uppercase tracking-wider text-teal-700">Midtrans QRIS</p>
                <h3 class="mt-1 text-xl font-bold text-slate-900">Scan untuk Bayar</h3>
                <p class="mt-1 text-xs text-slate-600" x-text="qrisOrderId"></p>
            </div>

            <div class="space-y-4 p-5">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-center">
                    <img x-show="qrisQrUrl" :src="qrisQrUrl" alt="QRIS Midtrans" class="mx-auto h-60 w-60 rounded-lg border border-slate-200 bg-white p-2" style="display: none;">
                    <p x-show="!qrisQrUrl" class="text-sm text-slate-500" style="display: none;">QR Code belum tersedia.</p>
                </div>

                <p class="text-center text-xs text-slate-500" x-show="qrisExpiresAt" style="display: none;">Berlaku sampai <span class="font-semibold" x-text="qrisExpiresAt"></span></p>
                <p class="text-center text-xs text-slate-500" x-show="qrisStatusNote" style="display: none;" x-text="qrisStatusNote"></p>
                <p x-show="qrisError" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700" style="display: none;" x-text="qrisError"></p>

                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <button type="button" @click="closeQrisModal" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Tutup</button>
                    <button type="button" @click="checkQrisStatus" :disabled="qrisChecking" class="inline-flex items-center justify-center rounded-xl border border-slate-900 bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-60">
                        <span x-show="!qrisChecking">Cek Sekarang</span>
                        <span x-show="qrisChecking" style="display: none;">Checking...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="scannerOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display: none;">
        <div class="absolute inset-0 bg-slate-900/60" @click="closeScanner"></div>

        <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                <h3 class="text-sm font-semibold text-slate-800">Scan Product Barcode</h3>
                <button type="button" @click="closeScanner" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                    Close
                </button>
            </div>

            <div class="space-y-3 p-4">
                <div class="aspect-video overflow-hidden rounded-xl border border-slate-200 bg-slate-900">
                    <video x-ref="scannerVideo" class="h-full w-full object-cover" autoplay muted playsinline></video>
                </div>
                <p x-show="scannerError" class="rounded-xl border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-semibold text-orange-700" x-text="scannerError"></p>
                <p class="text-xs text-slate-500">Arahkan barcode ke kamera. Produk akan otomatis dicari lalu ditambahkan ke cart jika ditemukan.</p>
            </div>
        </div>
    </div>

    <script>
        function posApp() {
            return {
                query: '',
                products: [],
                cart: [],
                paymentMethod: 'cash',
                taxAmount: 0,
                midtransOrderId: '',
                qrisModalOpen: false,
                qrisLoading: false,
                qrisChecking: false,
                qrisOrderId: '',
                qrisQrUrl: '',
                qrisExpiresAt: '',
                qrisStatusNote: '',
                qrisError: '',
                qrisPaid: false,
                qrisAutoCheckTimer: null,
                isSubmitting: false,
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
                supportedBarcodeFormats: [],
                audioContext: null,
                hardwareScanBuffer: '',
                hardwareScanStartedAt: 0,
                hardwareScanLastKeyAt: 0,
                hardwareScanResetTimer: null,
                hardwareScanHandler: null,
                hidConnectHandler: null,
                hidDisconnectHandler: null,
                endpoint: @json(route('sales.products.search')),
                qrisCreateEndpoint: @json(route('sales.qris.create')),
                qrisStatusEndpoint: @json(route('sales.qris.status')),
                initialProducts: @json($products),

                async init() {
                    this.products = this.initialProducts;

                    await this.setupBarcodeDetector();
                    await this.setupScannerDetection();

                    this.hardwareScanHandler = (event) => this.handleHardwareScanner(event);
                    window.addEventListener('keydown', this.hardwareScanHandler, true);

                    this.$nextTick(() => {
                        this.$refs.searchInput?.focus();
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

                    this.supportedBarcodeFormats = formats;
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

                get serializedItems() {
                    return JSON.stringify(this.cart.map((item) => ({
                        product_id: item.product_id,
                        qty: item.qty,
                    })));
                },

                get subtotal() {
                    return this.cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
                },

                get safeTax() {
                    const tax = Number(this.taxAmount || 0);
                    return tax > 0 ? tax : 0;
                },

                get grandTotal() {
                    return this.subtotal + this.safeTax;
                },

                formatCurrency(value) {
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        maximumFractionDigits: 0,
                    }).format(Number(value || 0));
                },

                handlePaymentMethodChange() {
                    if (this.paymentMethod !== 'qris') {
                        this.resetQrisState();
                    }
                },

                resetQrisState() {
                    this.stopQrisAutoCheck();
                    this.midtransOrderId = '';
                    this.qrisPaid = false;
                    this.qrisOrderId = '';
                    this.qrisQrUrl = '';
                    this.qrisExpiresAt = '';
                    this.qrisStatusNote = '';
                    this.qrisError = '';
                    this.qrisModalOpen = false;
                    this.qrisLoading = false;
                    this.qrisChecking = false;
                },

                startQrisAutoCheck() {
                    this.stopQrisAutoCheck();
                    this.qrisStatusNote = 'Menunggu pembayaran... status diperbarui otomatis.';

                    this.qrisAutoCheckTimer = window.setInterval(() => {
                        this.checkQrisStatus(true);
                    }, 3500);
                },

                stopQrisAutoCheck() {
                    if (this.qrisAutoCheckTimer) {
                        window.clearInterval(this.qrisAutoCheckTimer);
                        this.qrisAutoCheckTimer = null;
                    }
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
                    const keyword = this.query.trim();

                    if (!autoAddFromScan && keyword.length < 2) {
                        this.products = [];
                        return;
                    }

                    const url = `${this.endpoint}?q=${encodeURIComponent(keyword)}`;
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
                                    const result = this.addToCart(exact);
                                    if (result.ok) {
                                        this.query = '';
                                        this.products = [];
                                        this.notifyScanSuccess(`Barcode ${scannedCode} berhasil ditambahkan ke cart.`, 'Product added');
                                    } else {
                                        this.notifyScanFailure(result.message, 'Unable to add product');
                                    }

                                    this.$nextTick(() => this.$refs.searchInput?.focus());
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
                            video: {
                                facingMode: {
                                    ideal: 'environment',
                                },
                            },
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

                addToCart(product) {
                    if (this.paymentMethod === 'qris') {
                        this.resetQrisState();
                    }

                    const existing = this.cart.find((item) => item.product_id === product.id);
                    if (existing) {
                        if (existing.qty < Number(product.stock)) {
                            existing.qty += 1;
                            return { ok: true, message: 'Quantity updated.' };
                        }

                        return { ok: false, message: `Stok ${product.name} sudah mencapai batas maksimum.` };
                    }

                    this.cart.push({
                        product_id: product.id,
                        sku: product.sku,
                        name: product.name,
                        qty: 1,
                        max_stock: Number(product.stock),
                        price: Number(product.sell_price),
                    });

                    this.$nextTick(() => this.$refs.searchInput?.focus());
                    return { ok: true, message: 'Product added.' };
                },

                incrementQty(productId) {
                    if (this.paymentMethod === 'qris') {
                        this.resetQrisState();
                    }

                    const item = this.cart.find((entry) => entry.product_id === productId);
                    if (!item) {
                        return;
                    }

                    if (item.qty < item.max_stock) {
                        item.qty += 1;
                    }
                },

                decrementQty(productId) {
                    if (this.paymentMethod === 'qris') {
                        this.resetQrisState();
                    }

                    const item = this.cart.find((entry) => entry.product_id === productId);
                    if (!item) {
                        return;
                    }

                    if (item.qty <= 1) {
                        this.removeItem(productId);
                        return;
                    }

                    item.qty -= 1;
                },

                removeItem(productId) {
                    if (this.paymentMethod === 'qris') {
                        this.resetQrisState();
                    }

                    this.cart = this.cart.filter((item) => item.product_id !== productId);
                    this.$nextTick(() => this.$refs.searchInput?.focus());
                },

                buildSalePayload() {
                    return {
                        items: this.cart.map((item) => ({
                            product_id: item.product_id,
                            qty: item.qty,
                        })),
                        payment_method: this.paymentMethod,
                        tax_amount: this.safeTax,
                    };
                },

                async startQrisPayment() {
                    if (this.qrisLoading) {
                        return;
                    }

                    this.qrisLoading = true;
                    this.qrisError = '';

                    try {
                        const response = await fetch(this.qrisCreateEndpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            },
                            body: JSON.stringify(this.buildSalePayload()),
                        });

                        const payload = await response.json();

                        if (!response.ok) {
                            const message = payload?.message || 'Gagal membuat QRIS Midtrans.';
                            throw new Error(message);
                        }

                        this.qrisOrderId = String(payload.order_id || '');
                        this.qrisQrUrl = String(payload.qr_url || '');
                        this.qrisExpiresAt = String(payload.expires_at || '');
                        this.qrisStatusNote = 'QR siap di-scan. Setelah dibayar, transaksi akan lanjut otomatis.';
                        this.qrisModalOpen = true;
                        this.midtransOrderId = '';
                        this.qrisPaid = false;
                        this.startQrisAutoCheck();
                        this.checkQrisStatus(true);

                        this.showToast('success', 'QRIS generated', 'QR Midtrans berhasil dibuat. Silakan scan untuk membayar.');
                    } catch (error) {
                        const message = error instanceof Error ? error.message : 'Gagal membuat QRIS Midtrans.';
                        this.qrisError = message;
                        this.showToast('error', 'QRIS error', message);
                    } finally {
                        this.qrisLoading = false;
                    }
                },

                async checkQrisStatus(silent = false) {
                    if (!this.qrisOrderId || this.qrisChecking) {
                        return;
                    }

                    this.qrisChecking = true;
                    this.qrisError = '';

                    try {
                        const url = `${this.qrisStatusEndpoint}?order_id=${encodeURIComponent(this.qrisOrderId)}`;
                        const response = await fetch(url, {
                            method: 'GET',
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        const payload = await response.json();

                        if (!response.ok) {
                            const message = payload?.message || 'Gagal memeriksa status pembayaran QRIS.';
                            throw new Error(message);
                        }

                        if (!payload.paid) {
                            const message = payload?.message || 'Pembayaran masih pending. Coba cek lagi.';
                            this.qrisError = message;
                            this.qrisStatusNote = 'Belum ada pembayaran masuk. Silakan scan QR lalu selesaikan pembayaran.';
                            if (!silent) {
                                this.showToast('error', 'Belum terbayar', message);
                            }
                            return;
                        }

                        this.stopQrisAutoCheck();
                        this.qrisPaid = true;
                        this.midtransOrderId = this.qrisOrderId;
                        this.qrisModalOpen = false;
                        this.qrisStatusNote = 'Pembayaran berhasil. Menyimpan transaksi...';

                        this.showToast('success', 'Pembayaran berhasil', 'Pembayaran QRIS berhasil diverifikasi. Menyimpan transaksi...');

                        this.isSubmitting = true;
                        this.$nextTick(() => this.$refs.checkoutForm?.submit());
                    } catch (error) {
                        const message = error instanceof Error ? error.message : 'Gagal memeriksa status pembayaran.';
                        this.qrisError = message;
                        if (!silent) {
                            this.showToast('error', 'Status check failed', message);
                        }
                    } finally {
                        this.qrisChecking = false;
                    }
                },

                closeQrisModal() {
                    this.stopQrisAutoCheck();
                    this.qrisModalOpen = false;
                },

                async submitSale(event) {
                    if (this.cart.length === 0) {
                        event.preventDefault();
                        return;
                    }

                    if (this.paymentMethod !== 'qris') {
                        return;
                    }

                    if (this.qrisPaid && this.midtransOrderId !== '') {
                        return;
                    }

                    if (this.qrisOrderId && this.qrisQrUrl) {
                        event.preventDefault();
                        this.qrisModalOpen = true;
                        this.startQrisAutoCheck();
                        this.checkQrisStatus(true);
                        return;
                    }

                    event.preventDefault();
                    await this.startQrisPayment();
                },
            };
        }
    </script>
</x-app-layout>
