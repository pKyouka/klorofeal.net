@php
    $user = Auth::user();
    $sections = [
        [
            'title' => 'Overview',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'dashboard',
                    'icon' => 'M3 13.5l9-9 9 9M4.5 12v7.5a1.5 1.5 0 001.5 1.5h3.75v-6a1.5 1.5 0 011.5-1.5h1.5a1.5 1.5 0 011.5 1.5v6H18a1.5 1.5 0 001.5-1.5V12',
                    'active' => request()->routeIs('dashboard'),
                ],
            ],
        ],
    ];

    if ($user->hasRole('admin')) {
        $sections[] = [
            'title' => 'Master Data',
            'items' => [
                [
                    'label' => 'Categories',
                    'route' => 'product-categories.index',
                    'icon' => 'M3.75 4.5h7.5v7.5h-7.5V4.5zM12.75 4.5h7.5v7.5h-7.5V4.5zM3.75 12.75h7.5v7.5h-7.5v-7.5zM12.75 12.75h7.5v7.5h-7.5v-7.5z',
                    'active' => request()->routeIs('product-categories.*'),
                ],
                [
                    'label' => 'Products',
                    'route' => 'products.index',
                    'icon' => 'M21 7.5L12 3 3 7.5m18 0L12 12m9-4.5v9L12 21m9-4.5L12 12m0 9l-9-4.5v-9m9 13.5V12m0 0L3 7.5',
                    'active' => request()->routeIs('products.*'),
                ],
                [
                    'label' => 'Suppliers',
                    'route' => 'suppliers.index',
                    'icon' => 'M18 18.72a9.094 9.094 0 003.742-.479 3 3 0 00-4.682-2.72m.94 3.198a2.999 2.999 0 00-2.94 0m0 0a3 3 0 01-5.3-2.157m5.3 2.157v.002c0 .995-.72 1.86-1.698 1.98a9.093 9.093 0 01-3.864-.057A3 3 0 017.5 15.5m8.25-3a3 3 0 11-6 0 3 3 0 016 0zm6.75 2.25a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zM6.75 15a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z',
                    'active' => request()->routeIs('suppliers.*'),
                ],
            ],
        ];
    }

    if ($user->hasAnyRole(['admin', 'cashier'])) {
        $sections[] = [
            'title' => 'POS Module',
            'items' => [
                [
                    'label' => 'POS Terminal',
                    'route' => 'sales.create',
                    'icon' => 'M3.75 5.25A2.25 2.25 0 016 3h12a2.25 2.25 0 012.25 2.25v9A2.25 2.25 0 0118 16.5H6A2.25 2.25 0 013.75 14.25v-9zM8.25 21h7.5',
                    'active' => request()->routeIs('sales.create') || request()->routeIs('sales.products.search'),
                ],
                [
                    'label' => 'Sales History',
                    'route' => 'sales.index',
                    'icon' => 'M3 3v18h18M7.5 15l3-3 2.25 2.25L16.5 9',
                    'active' => request()->routeIs('sales.index') || request()->routeIs('sales.show'),
                ],
            ],
        ];
    }

    if ($user->hasAnyRole(['admin', 'warehouse'])) {
        $sections[] = [
            'title' => 'Inventory Module',
            'items' => [
                [
                    'label' => 'Purchases',
                    'route' => 'purchases.index',
                    'icon' => 'M8.25 18.75a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM18.75 18.75a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM3 3h2.25l2.1 10.5h9.15l2.25-7.5H6.3',
                    'active' => request()->routeIs('purchases.*'),
                ],
                [
                    'label' => 'Inventory',
                    'route' => 'inventory.index',
                    'icon' => 'M4.5 7.5L12 3l7.5 4.5M4.5 7.5V18L12 21l7.5-3V7.5M12 21V12M4.5 7.5L12 12l7.5-4.5',
                    'active' => request()->routeIs('inventory.*'),
                ],
                [
                    'label' => 'Movements',
                    'route' => 'stock-movements.index',
                    'icon' => 'M7.5 7.5h13.5M7.5 7.5l3-3m-3 3l3 3M16.5 16.5H3m13.5 0l-3-3m3 3l-3 3',
                    'active' => request()->routeIs('stock-movements.*'),
                ],
                [
                    'label' => 'Stock Opname',
                    'route' => 'stock-opnames.index',
                    'icon' => 'M9 12.75L11.25 15 15 9.75M7.5 3h9A1.5 1.5 0 0118 4.5v15A1.5 1.5 0 0116.5 21h-9A1.5 1.5 0 016 19.5v-15A1.5 1.5 0 017.5 3z',
                    'active' => request()->routeIs('stock-opnames.*'),
                ],
            ],
        ];
    }

    if ($user->hasRole('admin')) {
        $sections[] = [
            'title' => 'Reports',
            'items' => [
                [
                    'label' => 'Sales Report',
                    'route' => 'reports.sales',
                    'icon' => 'M3 3v18h18M7.5 13.5l3 3 6-6',
                    'active' => request()->routeIs('reports.sales'),
                ],
                [
                    'label' => 'Inventory Report',
                    'route' => 'reports.inventory',
                    'icon' => 'M3.75 5.25h16.5M3.75 9.75h16.5M3.75 14.25h16.5M3.75 18.75h16.5',
                    'active' => request()->routeIs('reports.inventory'),
                ],
                [
                    'label' => 'Purchase Report',
                    'route' => 'reports.purchases',
                    'icon' => 'M3 6.75l9-3 9 3-9 3-9-3zM3 6.75v10.5l9 3 9-3V6.75',
                    'active' => request()->routeIs('reports.purchases'),
                ],
            ],
        ];
    }
@endphp

<div x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
    <header class="fixed inset-x-0 top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur-xl lg:hidden">
        <div class="flex h-16 items-center justify-between px-4">
            <button @click="sidebarOpen = true" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-slate-600 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-500">
                <span class="sr-only">Open menu</span>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2">
                <x-application-logo class="h-8 w-8" />
                <span class="text-sm font-semibold uppercase tracking-[0.12em] text-teal-700">{{ config('app.name', 'Klorofeal') }}</span>
            </a>

            <a href="{{ route('profile.edit') }}" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-teal-100 text-sm font-bold text-teal-800">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </a>
        </div>
    </header>

    <aside class="sidebar-shell fixed inset-y-0 left-0 z-30 hidden w-72 flex-col border-r border-slate-200/70 backdrop-blur-xl lg:flex">
        <div class="flex h-20 items-center gap-3 border-b border-slate-200/70 px-6">
            <x-application-logo class="h-10 w-10" />
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-teal-700">{{ config('app.name', 'Klorofeal') }}</p>
                <p class="text-sm font-medium text-slate-600">Retail Command Panel</p>
            </div>
        </div>

        <nav class="sidebar-scroll flex-1 space-y-6 overflow-y-auto px-4 py-5">
            @foreach($sections as $section)
                <div class="sidebar-section-card space-y-2">
                    <p class="sidebar-section-title">{{ $section['title'] }}</p>
                    <div class="space-y-1.5">
                        @foreach($section['items'] as $item)
                            <a href="{{ route($item['route']) }}" class="{{ $item['active'] ? 'sidebar-link sidebar-link-active' : 'sidebar-link' }}">
                                <span class="sidebar-link-main">
                                    <span class="sidebar-link-icon" aria-hidden="true">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="{{ $item['icon'] }}" />
                                        </svg>
                                    </span>
                                    <span>{{ $item['label'] }}</span>
                                </span>
                                <svg class="sidebar-link-arrow h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M9 6l6 6-6 6" />
                                </svg>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        <div class="border-t border-slate-200/70 p-4">
            <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 px-4 py-3">
                <p class="text-sm font-semibold text-slate-800">{{ $user->name }}</p>
                <p class="text-xs text-slate-500">{{ $user->email }}</p>
            </div>

            <div class="mt-3 space-y-2">
                <a href="{{ route('profile.edit') }}" class="sidebar-aux-link">
                    Profile
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-xl border border-rose-300/70 bg-rose-50 px-3 py-2 text-left text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
                        Log Out
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-900/55 lg:hidden" @click="sidebarOpen = false" style="display: none;"></div>

    <aside x-show="sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="sidebar-shell fixed inset-y-0 left-0 z-50 flex w-[84vw] max-w-xs flex-col border-r border-slate-200/70 px-4 py-5 backdrop-blur-xl lg:hidden" style="display: none;">
        <div class="mb-4 flex items-center justify-between border-b border-slate-200/70 pb-3">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2">
                <x-application-logo class="h-9 w-9" />
                <span class="text-xs font-semibold uppercase tracking-[0.14em] text-teal-700">{{ config('app.name', 'Klorofeal') }}</span>
            </a>
            <button @click="sidebarOpen = false" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-slate-600">
                <span class="sr-only">Close menu</span>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <nav class="sidebar-scroll flex-1 space-y-6 overflow-y-auto pr-1">
            @foreach($sections as $section)
                <div class="sidebar-section-card space-y-2">
                    <p class="sidebar-section-title">{{ $section['title'] }}</p>
                    <div class="space-y-1.5">
                        @foreach($section['items'] as $item)
                            <a href="{{ route($item['route']) }}" @click="sidebarOpen = false" class="{{ $item['active'] ? 'sidebar-link sidebar-link-active' : 'sidebar-link' }}">
                                <span class="sidebar-link-main">
                                    <span class="sidebar-link-icon" aria-hidden="true">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="{{ $item['icon'] }}" />
                                        </svg>
                                    </span>
                                    <span>{{ $item['label'] }}</span>
                                </span>
                                <svg class="sidebar-link-arrow h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M9 6l6 6-6 6" />
                                </svg>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        <div class="mt-4 border-t border-slate-200/70 pt-4">
            <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 px-4 py-3">
                <p class="text-sm font-semibold text-slate-800">{{ $user->name }}</p>
                <p class="text-xs text-slate-500">{{ $user->email }}</p>
            </div>

            <div class="mt-3 space-y-2">
                <a href="{{ route('profile.edit') }}" class="sidebar-aux-link">
                    Profile
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-xl border border-rose-300/70 bg-rose-50 px-3 py-2 text-left text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
                        Log Out
                    </button>
                </form>
            </div>
        </div>
    </aside>
</div>
