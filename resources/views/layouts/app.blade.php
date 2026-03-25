<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($pageTitle) ? $pageTitle . ' — ' . config('app.name', 'Klorofeal') : config('app.name', 'Klorofeal') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="alternate icon" href="{{ asset('favicon.ico') }}">

        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
            <div class="absolute -left-20 top-10 h-72 w-72 rounded-full bg-emerald-300/20 blur-3xl"></div>
            <div class="absolute right-0 top-0 h-96 w-96 rounded-full bg-cyan-300/20 blur-3xl"></div>
            <div class="absolute bottom-0 left-1/3 h-80 w-80 rounded-full bg-teal-300/15 blur-3xl"></div>
        </div>

        <div class="min-h-screen pb-12 lg:pl-72">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="page-container pt-24 lg:pt-6">
                    <div class="surface-card px-6 py-5 motion-rise">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="pt-2 {{ isset($header) ? '' : 'pt-24 lg:pt-6' }}">
                @auth
                    @if (session('status'))
                        <div class="page-container mt-4">
                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/90 px-4 py-3 text-sm font-semibold text-emerald-800 shadow-sm">
                                {{ session('status') }}
                            </div>
                        </div>
                    @endif
                @endauth

                {{ $slot }}
            </main>
        </div>
    </body>
</html>
