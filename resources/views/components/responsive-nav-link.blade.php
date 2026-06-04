@props(['active', 'variant' => 'default'])

@php
$isPremium = $variant === 'premium';
$classes = ($active ?? false)
    ? ($isPremium
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-accent-orange text-start text-base font-medium text-accent-orange bg-racing-700 focus:outline-none transition duration-150 ease-in-out'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-accent-neon text-start text-base font-medium text-accent-neon bg-racing-700 focus:outline-none transition duration-150 ease-in-out')
    : ($isPremium
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-accent-orange/80 hover:text-accent-orange hover:bg-racing-700 hover:border-accent-orange/50 focus:outline-none transition duration-150 ease-in-out'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-400 hover:text-gray-200 hover:bg-racing-700 hover:border-racing-600 focus:outline-none transition duration-150 ease-in-out');
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
