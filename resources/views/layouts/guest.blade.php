<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
            <div class="absolute -left-24 top-8 h-80 w-80 rounded-full bg-emerald-300/20 blur-3xl"></div>
            <div class="absolute right-0 top-0 h-96 w-96 rounded-full bg-cyan-300/20 blur-3xl"></div>
            <div class="absolute -bottom-24 left-1/3 h-96 w-96 rounded-full bg-teal-300/15 blur-3xl"></div>
        </div>

        <div class="relative min-h-screen px-4 py-10 sm:px-6 lg:px-8">
            <div class="mx-auto grid w-full max-w-5xl gap-6 lg:grid-cols-2 lg:gap-8">
                <div class="surface-card hidden p-8 lg:flex lg:flex-col lg:justify-between">
                    <div>
                        <span class="brand-badge">Klorofeal Suite</span>
                        <h1 class="mt-4 text-3xl font-bold text-slate-900">Retail operations in one clean workspace.</h1>
                        <p class="mt-3 text-sm text-slate-600">
                            Kelola POS, inventori, warehouse, dan laporan dengan antarmuka yang lebih segar dan fokus ke produktivitas harian tim.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 text-sm text-emerald-900">
                        <p class="font-semibold">Security-first and role-aware</p>
                        <p class="mt-1 text-emerald-800/90">Setiap modul menyesuaikan akses user berdasarkan role admin, cashier, dan warehouse.</p>
                    </div>
                </div>

                <div class="surface-card w-full p-6 sm:p-8">
                    <div class="mb-6 flex items-center gap-3">
                        <a href="/" class="inline-flex">
                            <x-application-logo class="h-12 w-12" />
                        </a>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-700">{{ config('app.name', 'Klorofeal') }}</p>
                            <p class="text-sm text-slate-600">Knowledge Layer for Retail and Logistics</p>
                        </div>
                    </div>

                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
