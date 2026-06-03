<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach ($cars as $car)
        @php $isActive = $activeCarId === $car->id; @endphp
        <a href="{{ route('garage.show', $car) }}" class="block bg-racing-800 rounded-lg border {{ $isActive ? 'border-accent-neon ring-1 ring-accent-neon' : 'border-racing-600' }} overflow-hidden hover:border-accent-blue transition">
            <div class="p-4">
                @if ($isActive)
                    <span class="inline-block mb-2 text-xs font-bold uppercase tracking-wide text-accent-neon">{{ __('Active') }}</span>
                @endif
                <x-car-image :car-model="$car->carModel" class="h-28 w-full object-contain mb-4" />
                <h3 class="text-lg font-bold text-white">{{ $car->carModel->name }}</h3>
                <p class="text-gray-400 text-sm">{{ __('Class') }} {{ $car->carModel->class->value }}</p>
                <p class="text-sm mt-2">
                    <x-condition-meter :current="$car->condition_current" :max="$car->condition_max" class="text-sm" />
                </p>
            </div>
        </a>
    @endforeach
</div>
