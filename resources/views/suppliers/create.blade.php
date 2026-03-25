<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="brand-badge">Procurement</span>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Create Supplier</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-container max-w-4xl">
            <div class="surface-card p-6 sm:p-7">
                <form method="POST" action="{{ route('suppliers.store') }}" class="space-y-5">
                    @include('suppliers._form')
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
