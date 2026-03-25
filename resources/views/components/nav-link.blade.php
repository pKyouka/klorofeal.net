@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-full border border-teal-300/70 bg-teal-50 px-3 py-1.5 text-sm font-semibold text-teal-800 shadow-sm transition duration-150 ease-in-out'
            : 'inline-flex items-center rounded-full border border-transparent px-3 py-1.5 text-sm font-semibold text-slate-600 transition duration-150 ease-in-out hover:border-slate-300/70 hover:bg-white/80 hover:text-slate-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
