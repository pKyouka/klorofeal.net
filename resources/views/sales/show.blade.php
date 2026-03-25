<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="brand-badge">Sales Desk</span>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Invoice {{ $sale->invoice_number }}</h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('sales.receipt', ['sale' => $sale, 'autoprint' => 1]) }}" target="_blank" rel="noopener" class="inline-flex items-center rounded-xl border border-slate-900 bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">Print Thermal</a>
                <a href="{{ route('sales.create') }}" class="inline-flex items-center rounded-xl border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">New Sale</a>
                <a href="{{ route('sales.index') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-container max-w-5xl space-y-4">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>

                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                    Thermal receipt sudah siap. Anda bisa print sekarang dari dialog konfirmasi, atau gunakan tombol <span class="font-semibold text-slate-900">Print Thermal</span> kapan saja.
                </div>
            @endif

            @php
                $subtotal = (float) $sale->items->sum('subtotal');
                $grandTotal = (float) $sale->total_amount;
                $tax = max(0, $grandTotal - $subtotal);
                $receiptUrl = route('sales.receipt', ['sale' => $sale, 'autoprint' => 1]);
                $grandTotalFormatted = number_format($grandTotal, 0, ',', '.');
                $paymentMethodLabel = strtoupper((string) $sale->payment_method);
            @endphp

            <div class="surface-card overflow-hidden">
                <div class="border-b border-slate-200 p-6">
                    <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-3">
                        <div>
                            <p class="text-slate-500">Invoice Number</p>
                            <p class="font-semibold text-slate-900">{{ $sale->invoice_number }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Cashier</p>
                            <p class="font-semibold text-slate-900">{{ $sale->cashier?->name ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Payment Method</p>
                            <p class="font-semibold uppercase text-slate-900">{{ $sale->payment_method }}</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="table-shell">
                        <div class="overflow-x-auto">
                            <table class="table-modern">
                                <thead>
                                    <tr>
                                        <th class="text-left">Product</th>
                                        <th class="text-right">Qty</th>
                                        <th class="text-right">Price</th>
                                        <th class="text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sale->items as $item)
                                        <tr>
                                            <td>
                                                <p class="font-semibold text-slate-900">{{ $item->product?->name }}</p>
                                                <p class="text-xs text-slate-500">{{ $item->product?->sku }}</p>
                                            </td>
                                            <td class="text-right text-slate-700">{{ $item->qty }}</td>
                                            <td class="text-right text-slate-700">{{ number_format((float) $item->price, 0, ',', '.') }}</td>
                                            <td class="text-right font-semibold text-slate-900">{{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end">
                        <div class="w-full max-w-sm rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
                            <div class="flex items-center justify-between py-1">
                                <span class="text-slate-600">Subtotal</span>
                                <span class="font-semibold text-slate-900">{{ number_format($subtotal, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex items-center justify-between py-1">
                                <span class="text-slate-600">Tax</span>
                                <span class="font-semibold text-slate-900">{{ number_format($tax, 0, ',', '.') }}</span>
                            </div>
                            <div class="mt-1 flex items-center justify-between border-t border-slate-200 pt-2">
                                <span class="text-slate-700">Grand Total</span>
                                <span class="text-lg font-bold text-slate-900">{{ number_format($grandTotal, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @if (session('success'))
        <script>
            (() => {
                const receiptData = {
                    invoiceNumber: @json($sale->invoice_number),
                    totalAmount: @json($grandTotalFormatted),
                    paymentMethod: @json($paymentMethodLabel),
                    receiptUrl: @json($receiptUrl),
                };

                const escapeHtml = (value) => String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');

                const renderReceiptPrompt = () => {
                    const overlay = document.createElement('div');
                    overlay.style.cssText = 'position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;opacity:0;transition:opacity .18s ease;';

                    const backdrop = document.createElement('div');
                    backdrop.style.cssText = 'position:absolute;inset:0;background:rgba(2,6,23,.5);backdrop-filter:blur(2px);';

                    const dialog = document.createElement('div');
                    dialog.style.cssText = 'position:relative;width:min(92vw,430px);border:1px solid #e2e8f0;border-radius:18px;background:#fff;box-shadow:0 25px 70px -30px rgba(15,23,42,.7);overflow:hidden;opacity:0;transform:translateY(12px) scale(.97);transition:transform .22s ease,opacity .22s ease;';
                    dialog.innerHTML = `
                        <div style="padding:24px 24px 22px;text-align:center;background:linear-gradient(135deg,#ecfeff 0%,#f0fdfa 55%,#ffffff 100%);">
                            <p style="margin:0;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#0f766e;">Receipt Ready</p>
                            <h3 style="margin:10px 0 0;font-size:44px;line-height:1.05;font-weight:800;color:#0f172a;">Cetak Struk?</h3>
                            <p style="margin:14px 0 0;font-size:15px;line-height:1.6;color:#475569;">Transaksi ${escapeHtml(receiptData.invoiceNumber)} sudah selesai. Cetak struk sekarang?</p>
                        </div>
                        <div style="padding:20px 24px 26px;">
                            <div style="border:1px solid #e2e8f0;background:#f8fafc;border-radius:14px;padding:14px 16px;">
                                <div style="display:flex;align-items:center;justify-content:space-between;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:#64748b;">
                                    <span>Total</span>
                                    <span>Pembayaran</span>
                                </div>
                                <div style="margin-top:8px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
                                    <span style="font-size:38px;line-height:1.05;font-weight:800;color:#0f172a;">Rp ${escapeHtml(receiptData.totalAmount)}</span>
                                    <span style="font-size:27px;line-height:1.05;font-weight:800;text-transform:uppercase;color:#0f172a;">${escapeHtml(receiptData.paymentMethod)}</span>
                                </div>
                            </div>
                            <div style="margin-top:18px;display:grid;grid-template-columns:1fr;gap:10px;padding-bottom:6px;">
                                <button type="button" data-action="close" style="width:100%;display:inline-flex;align-items:center;justify-content:center;border-radius:12px;border:1px solid #cbd5e1;background:#fff;padding:12px 14px;font-size:15px;font-weight:700;color:#334155;cursor:pointer;">Nanti Saja</button>
                                <button type="button" data-action="print" style="width:100%;display:inline-flex;align-items:center;justify-content:center;border-radius:12px;border:1px solid #0f172a;background:#0f172a;padding:12px 14px;font-size:15px;font-weight:700;color:#fff;cursor:pointer;">Print Receipt</button>
                            </div>
                        </div>
                    `;

                    const previousOverflow = document.body.style.overflow;

                    const cleanup = () => {
                        document.removeEventListener('keydown', onEscKey);
                        document.body.style.overflow = previousOverflow;
                        overlay.remove();
                    };

                    const closeDialog = () => {
                        overlay.style.opacity = '0';
                        dialog.style.opacity = '0';
                        dialog.style.transform = 'translateY(12px) scale(.97)';
                        window.setTimeout(cleanup, 190);
                    };

                    const onEscKey = (event) => {
                        if (event.key === 'Escape') {
                            closeDialog();
                        }
                    };

                    backdrop.addEventListener('click', closeDialog);
                    dialog.querySelector('[data-action="close"]')?.addEventListener('click', closeDialog);
                    dialog.querySelector('[data-action="print"]')?.addEventListener('click', () => {
                        window.open(receiptData.receiptUrl, '_blank', 'noopener,width=480,height=800');
                        closeDialog();
                    });

                    overlay.appendChild(backdrop);
                    overlay.appendChild(dialog);
                    document.body.appendChild(overlay);
                    document.body.style.overflow = 'hidden';
                    document.addEventListener('keydown', onEscKey);

                    window.requestAnimationFrame(() => {
                        overlay.style.opacity = '1';
                        dialog.style.opacity = '1';
                        dialog.style.transform = 'translateY(0) scale(1)';
                    });
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', renderReceiptPrompt, { once: true });
                } else {
                    renderReceiptPrompt();
                }
            })();
        </script>
    @endif
</x-app-layout>
