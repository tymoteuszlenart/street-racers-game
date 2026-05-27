<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-200 leading-tight">
                {{ $car->nickname }}
            </h2>
            <a href="{{ route('garage.index') }}" class="text-sm text-accent-blue hover:text-accent-neon">{{ __('Back to Garage') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'active-car-set')
                <div class="bg-racing-700 border border-accent-green text-accent-green px-4 py-3 rounded-lg">
                    {{ __('This car is now your active car.') }}
                </div>
            @elseif (session('status') === 'car-purchased')
                <div class="bg-racing-700 border border-accent-green text-accent-green px-4 py-3 rounded-lg">
                    {{ __('Car purchased successfully.') }}
                </div>
            @endif

            <div class="bg-racing-800 border border-racing-600 rounded-lg overflow-hidden">
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <x-car-image :car-model="$car->carModel" class="h-48 w-full object-contain" />
                        @if ($isActive)
                            <p class="mt-3 text-accent-neon font-semibold text-sm uppercase">{{ __('Active car') }}</p>
                        @else
                            <form method="POST" action="{{ route('garage.active', $car) }}" class="mt-4">
                                @csrf
                                @method('PATCH')
                                <x-primary-button>{{ __('Set as active') }}</x-primary-button>
                            </form>
                        @endif
                    </div>
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-2xl font-bold text-white">{{ $car->nickname }}</h3>
                            <p class="text-gray-400">{{ $car->carModel->name }} · Class {{ $car->carModel->class->value }} · {{ ucfirst($car->carModel->rarity) }}</p>
                        </div>
                        <div class="text-sm text-gray-400 space-y-1">
                            <p>{{ __('Condition') }}: <span class="text-white">{{ $car->condition_current }}/{{ $car->condition_max }}</span></p>
                            <p>{{ __('Acquired') }}: <span class="text-white">{{ ucfirst($car->acquired_via->value) }}</span></p>
                            @if ($car->purchase_price !== null)
                                <p>{{ __('Purchase price') }}: <span class="text-white">${{ number_format($car->purchase_price) }}</span></p>
                            @endif
                        </div>
                        <div class="pt-4 border-t border-racing-600 space-y-2">
                            <p class="text-gray-500 text-xs uppercase tracking-wide">{{ __('Coming later') }}</p>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" disabled class="px-3 py-1 text-xs rounded bg-racing-700 text-gray-500 border border-racing-600 cursor-not-allowed">{{ __('Repair') }}</button>
                                <button type="button" disabled class="px-3 py-1 text-xs rounded bg-racing-700 text-gray-500 border border-racing-600 cursor-not-allowed">{{ __('Tune') }}</button>
                                <button type="button" disabled class="px-3 py-1 text-xs rounded bg-racing-700 text-gray-500 border border-racing-600 cursor-not-allowed">{{ __('Sell') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 pb-6">
                    <h4 class="text-accent-orange font-semibold mb-3">{{ __('Stats') }}</h4>
                    <x-car-stats :car-model="$car->carModel" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
