<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('Garage') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-racing-700 border border-accent-green text-accent-green px-4 py-3 rounded-lg">
                    {{ __('Changes saved.') }}
                </div>
            @endif

            <div class="flex flex-wrap gap-4 justify-between items-center">
                <p class="text-gray-400">{{ __('Your owned cars. The active car is used for races.') }}</p>
                <a href="{{ route('dealer.index') }}" class="inline-flex items-center px-4 py-2 bg-accent-orange text-white rounded-md font-semibold text-sm hover:opacity-90">
                    {{ __('Visit Dealer') }}
                </a>
            </div>

            @if ($cars->isEmpty())
                <div class="bg-racing-800 border border-racing-600 rounded-lg p-8 text-center text-gray-400">
                    {{ __('No cars in your garage yet.') }}
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($cars as $car)
                        @php $isActive = $activeCarId === $car->id; @endphp
                        <a href="{{ route('garage.show', $car) }}" class="block bg-racing-800 rounded-lg border {{ $isActive ? 'border-accent-neon ring-1 ring-accent-neon' : 'border-racing-600' }} overflow-hidden hover:border-accent-blue transition">
                            <div class="p-4">
                                @if ($isActive)
                                    <span class="inline-block mb-2 text-xs font-bold uppercase tracking-wide text-accent-neon">{{ __('Active') }}</span>
                                @endif
                                <x-car-image :car-model="$car->carModel" class="h-28 w-full object-contain mb-4" />
                                <h3 class="text-lg font-bold text-white">{{ $car->nickname }}</h3>
                                <p class="text-gray-400 text-sm">{{ $car->carModel->name }} · Class {{ $car->carModel->class->value }}</p>
                                <p class="text-gray-500 text-sm mt-2">{{ __('Condition') }}: {{ $car->condition_current }}/{{ $car->condition_max }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
