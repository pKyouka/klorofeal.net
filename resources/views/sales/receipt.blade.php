<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Receipt {{ $sale->invoice_number }}</title>
        <style>
            @page {
                size: 80mm auto;
                margin: 4mm;
            }

            :root {
                color-scheme: light;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                background: #f3f4f6;
                color: #111827;
                font-family: "Consolas", "Courier New", monospace;
                font-size: 12px;
                line-height: 1.45;
            }

            .receipt-page {
                display: flex;
                justify-content: center;
                padding: 16px;
            }

            .receipt {
                width: 80mm;
                background: #ffffff;
                padding: 10px 9px 14px;
                box-shadow: 0 10px 25px -18px rgba(15, 23, 42, 0.5);
            }

            .center {
                text-align: center;
            }

            .store-copy {
                margin-bottom: 2px;
                font-size: 10px;
                font-weight: 700;
                letter-spacing: 0.18em;
                text-transform: uppercase;
            }

            .store-name {
                font-size: 16px;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }

            .store-tagline {
                font-size: 11px;
            }

            .muted {
                color: #4b5563;
            }

            .divider {
                margin: 8px 0;
                border-top: 1px dashed #111827;
            }

            .meta,
            .totals,
            .row {
                display: flex;
                justify-content: space-between;
                gap: 8px;
            }

            .meta {
                margin-top: 3px;
            }

            .items {
                margin-top: 6px;
            }

            .item {
                padding: 5px 0;
                border-bottom: 1px dashed #d1d5db;
            }

            .item:last-child {
                border-bottom: none;
            }

            .item-name {
                font-weight: 700;
            }

            .item-sub {
                display: flex;
                justify-content: space-between;
                gap: 8px;
                color: #4b5563;
            }

            .totals {
                padding: 2px 0;
            }

            .grand-total {
                margin-top: 4px;
                padding-top: 6px;
                border-top: 1px dashed #111827;
                font-size: 14px;
                font-weight: 700;
            }

            .footer-note {
                margin-top: 8px;
                font-size: 11px;
                line-height: 1.5;
            }

            .footer-highlight {
                font-weight: 700;
                letter-spacing: 0.14em;
                text-transform: uppercase;
            }

            .actions {
                display: flex;
                justify-content: center;
                gap: 8px;
                padding: 12px 16px 4px;
            }

            .action-button {
                border: 1px solid #cbd5e1;
                border-radius: 10px;
                background: #ffffff;
                color: #0f172a;
                padding: 8px 12px;
                font: inherit;
                font-weight: 700;
                cursor: pointer;
            }

            .action-button.primary {
                background: #0f172a;
                border-color: #0f172a;
                color: #ffffff;
            }

            @media print {
                body {
                    background: #ffffff;
                }

                .receipt-page {
                    padding: 0;
                }

                .receipt {
                    width: 100%;
                    box-shadow: none;
                    padding: 0;
                }

                .actions {
                    display: none;
                }
            }
        </style>
    </head>
    <body>
        @php
            $subtotal = (float) $sale->items->sum('subtotal');
            $grandTotal = (float) $sale->total_amount;
            $tax = max(0, $grandTotal - $subtotal);
            $totalItems = (int) $sale->items->sum('qty');
        @endphp

        <div class="actions">
            <button type="button" class="action-button primary" onclick="window.print()">Print Receipt</button>
            <button type="button" class="action-button" onclick="window.close()">Close</button>
        </div>

        <div class="receipt-page">
            <div class="receipt">
                <div class="center">
                    <div class="store-copy">Minimarket</div>
                    <div class="store-name">{{ config('app.name', 'Klorofeal') }}</div>
                    <div class="store-tagline muted">Retail &amp; Grosir</div>
                    <div class="muted">Struk Belanja / Thermal Receipt</div>
                </div>

                <div class="divider"></div>

                <div class="meta">
                    <span>No. Struk</span>
                    <span>{{ $sale->invoice_number }}</span>
                </div>
                <div class="meta">
                    <span>Tanggal</span>
                    <span>{{ optional($sale->sold_at)->format('d/m/Y H:i') }}</span>
                </div>
                <div class="meta">
                    <span>Kasir</span>
                    <span>{{ $sale->cashier?->name ?: '-' }}</span>
                </div>
                <div class="meta">
                    <span>Pembayaran</span>
                    <span>{{ strtoupper($sale->payment_method) }}</span>
                </div>

                <div class="divider"></div>

                <div class="items">
                    @foreach ($sale->items as $item)
                        <div class="item">
                            <div class="item-name">{{ $item->product?->name }}</div>
                            <div class="muted">{{ $item->product?->sku }}</div>
                            <div class="item-sub">
                                <span>{{ $item->qty }} x {{ number_format((float) $item->price, 0, ',', '.') }}</span>
                                <span>{{ number_format((float) $item->subtotal, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="divider"></div>

                <div class="totals">
                    <span>Total Item</span>
                    <span>{{ number_format($totalItems, 0, ',', '.') }}</span>
                </div>
                <div class="totals">
                    <span>Subtotal</span>
                    <span>{{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="totals">
                    <span>Pajak</span>
                    <span>{{ number_format($tax, 0, ',', '.') }}</span>
                </div>
                <div class="totals grand-total">
                    <span>Total</span>
                    <span>{{ number_format($grandTotal, 0, ',', '.') }}</span>
                </div>

                <div class="divider"></div>

                <div class="center muted footer-note">
                    <div class="footer-highlight">Terima Kasih</div>
                    <div>Sudah berbelanja di {{ config('app.name', 'Klorofeal') }}</div>
                    <div>Simpan struk ini sebagai bukti pembayaran.</div>
                    <div>Barang yang sudah dibeli tidak dapat dikembalikan tanpa persetujuan toko.</div>
                </div>
            </div>
        </div>

        @if ($autoprint)
            <script>
                window.addEventListener('load', () => {
                    window.print();
                });

                window.addEventListener('afterprint', () => {
                    window.close();
                });
            </script>
        @endif
    </body>
</html>
