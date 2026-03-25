<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Klorofeal') }} — Retail & POS Management</title>

        <!-- Favicon -->
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="alternate icon" href="{{ asset('favicon.ico') }}">

        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
            <div class="absolute -left-28 top-0 h-96 w-96 rounded-full bg-emerald-300/20 blur-3xl"></div>
            <div class="absolute right-0 top-8 h-96 w-96 rounded-full bg-cyan-300/20 blur-3xl"></div>
            <div class="absolute bottom-0 left-1/3 h-96 w-96 rounded-full bg-teal-200/20 blur-3xl"></div>
        </div>

        <div class="page-container py-8">
            <header class="flex items-center justify-between rounded-2xl border border-slate-200/70 bg-white/80 px-5 py-4 backdrop-blur-xl">
                <div class="flex items-center gap-3">
                    <x-application-logo class="h-10 w-10" />
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-teal-700">{{ config('app.name', 'Klorofeal') }}</p>
                        <p class="text-sm text-slate-600">Knowledge Layer for Retail and Logistics</p>
                    </div>
                </div>

                <nav class="flex items-center gap-2">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="inline-flex items-center rounded-xl border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Log in
                            </a>

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="inline-flex items-center rounded-xl border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">
                                    Register
                                </a>
                            @endif
                        @endauth
                    @endif
                </nav>
            </header>

            <main class="mt-8 grid gap-6 lg:grid-cols-2">
                <section class="surface-card p-8 lg:p-9">
                    <span class="brand-badge">Retail OS</span>
                    <h1 class="mt-4 text-4xl font-bold leading-tight text-slate-900">Satu dashboard untuk kasir, inventori, warehouse, dan laporan.</h1>
                    <p class="mt-4 text-base text-slate-600">
                        Klorofeal membantu tim operasional bergerak lebih cepat dengan alur transaksi yang rapih,
                        data stok real-time, serta reporting yang mudah dibaca.
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.12em] text-emerald-700">POS</p>
                            <p class="text-sm font-semibold text-emerald-900">Cepat dan akurat</p>
                        </div>
                        <div class="rounded-xl border border-cyan-200 bg-cyan-50 px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.12em] text-cyan-700">Inventory</p>
                            <p class="text-sm font-semibold text-cyan-900">Selalu terkontrol</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.12em] text-slate-700">Reports</p>
                            <p class="text-sm font-semibold text-slate-900">Insight siap pakai</p>
                        </div>
                    </div>

                    <div class="mt-6 rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.1em] text-slate-500">Workflow</p>
                        <p class="mt-1 text-sm text-slate-600">Purchasing -> Inventory -> Sales -> Reporting dalam satu alur data.</p>
                    </div>
                </section>

                <section class="surface-card p-8 lg:p-9">
                    <h2 class="section-title">Core Modules</h2>
                    <p class="section-subtitle mt-1">Arsitektur modular untuk operasi harian yang scalable.</p>

                    <div class="mt-5 space-y-3">
                        <div class="rounded-xl border border-slate-200 bg-white p-4 transition hover:border-teal-200 hover:bg-teal-50/40">
                            <p class="text-sm font-semibold text-slate-900">POS and Sales</p>
                            <p class="mt-1 text-sm text-slate-600">Checkout cepat, invoice otomatis, dan histori penjualan lengkap.</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-4 transition hover:border-teal-200 hover:bg-teal-50/40">
                            <p class="text-sm font-semibold text-slate-900">Purchasing and Supplier</p>
                            <p class="mt-1 text-sm text-slate-600">Pengadaan produk dan update stok masuk terintegrasi.</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-4 transition hover:border-teal-200 hover:bg-teal-50/40">
                            <p class="text-sm font-semibold text-slate-900">Inventory and Warehouse</p>
                            <p class="mt-1 text-sm text-slate-600">Stock movement, adjustment, dan stock opname dengan jejak data.</p>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
