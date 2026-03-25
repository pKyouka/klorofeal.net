@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-xl border border-teal-300/70 bg-teal-50 px-4 py-2 text-start text-sm font-semibold text-teal-800 transition duration-150 ease-in-out'
            : 'block w-full rounded-xl border border-transparent px-4 py-2 text-start text-sm font-semibold text-slate-600 transition duration-150 ease-in-out hover:border-slate-300/60 hover:bg-white hover:text-slate-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
