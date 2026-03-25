<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="brand-badge">Account</span>
            <h2 class="mt-2 text-2xl font-bold text-slate-900 leading-tight">{{ __('Profile Settings') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-container space-y-5">
            <div class="surface-card p-5 sm:p-7">
                <div class="max-w-2xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="surface-card p-5 sm:p-7">
                <div class="max-w-2xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="surface-card p-5 sm:p-7">
                <div class="max-w-2xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
