@props(['carModel', 'class' => 'h-32 w-full object-contain'])

@php
    $src = $carModel->image_path ? asset($carModel->image_path) : null;
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center justify-center bg-racing-700 rounded-lg border border-racing-600 overflow-hidden']) }}>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $carModel->name }}" class="{{ $class }}">
    @else
        <span class="text-accent-silver text-sm font-medium px-4 text-center">{{ $carModel->name }}</span>
    @endif
</div>
