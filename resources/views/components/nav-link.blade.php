@props(['active', 'variant' => 'default'])

@php
$isPremium = $variant === 'premium';
$classes = ($active ?? false)
    ? ($isPremium
        ? 'inline-flex items-center px-1 pt-1 border-b-2 border-accent-orange text-sm font-medium leading-5 text-accent-orange focus:outline-none transition duration-150 ease-in-out'
        : 'inline-flex items-center px-1 pt-1 border-b-2 border-accent-neon text-sm font-medium leading-5 text-accent-neon focus:outline-none transition duration-150 ease-in-out')
    : ($isPremium
        ? 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-accent-orange/80 hover:text-accent-orange hover:border-accent-orange/50 focus:outline-none transition duration-150 ease-in-out'
        : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-400 hover:text-gray-200 hover:border-racing-600 focus:outline-none transition duration-150 ease-in-out');
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
