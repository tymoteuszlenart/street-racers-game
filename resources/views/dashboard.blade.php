<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-racing-800 overflow-hidden shadow-sm sm:rounded-lg border border-racing-600">
                <div class="p-6 text-gray-200">
                    <h3 class="text-2xl font-bold text-accent-neon mb-4">Welcome to Street Racers!</h3>
                    <p class="text-gray-400 mb-6">Your street racing career starts here. Get behind the wheel and dominate the streets.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="bg-racing-700 rounded-lg p-6 border border-racing-600">
                            <h4 class="text-accent-orange font-semibold text-lg mb-2">Cash</h4>
                            <p class="text-3xl font-bold text-white">${{ number_format($profile->cash ?? 0) }}</p>
                        </div>
                        <div class="bg-racing-700 rounded-lg p-6 border border-racing-600">
                            <h4 class="text-accent-blue font-semibold text-lg mb-2">Level</h4>
                            <p class="text-3xl font-bold text-white">{{ $profile->level ?? 1 }}</p>
                        </div>
                        <div class="bg-racing-700 rounded-lg p-6 border border-racing-600">
                            <h4 class="text-accent-green font-semibold text-lg mb-2">Fuel</h4>
                            <p class="text-3xl font-bold text-white">{{ $profile->fuel_current ?? 0 }}/{{ $profile->fuel_max ?? 100 }}</p>
                        </div>
                        <div class="bg-racing-700 rounded-lg p-6 border border-racing-600">
                            <h4 class="text-accent-neon font-semibold text-lg mb-2">Reputation</h4>
                            <p class="text-3xl font-bold text-white">{{ $profile->reputation ?? 0 }}</p>
                        </div>
                    </div>

                    <div class="bg-racing-700 rounded-lg p-6 border border-racing-600">
                        <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
                            <h4 class="text-accent-neon font-semibold text-lg">Active Car</h4>
                            <div class="flex gap-3 text-sm">
                                <a href="{{ route('garage.index') }}" class="text-accent-blue hover:text-accent-neon">Garage</a>
                                <a href="{{ route('dealer.index') }}" class="text-accent-blue hover:text-accent-neon">Dealer</a>
                            </div>
                        </div>
                        @if ($profile?->activeCar)
                            @php $active = $profile->activeCar; @endphp
                            <div class="flex flex-col sm:flex-row gap-6 items-start">
                                <x-car-image :car-model="$active->carModel" class="h-24 w-40 object-contain" />
                                <div>
                                    <p class="text-xl font-bold text-white">{{ $active->nickname }}</p>
                                    <p class="text-gray-400">{{ $active->carModel->name }} · Class {{ $active->carModel->class->value }}</p>
                                    <p class="text-gray-500 text-sm mt-1">{{ __('Condition') }}: {{ $active->condition_current }}/{{ $active->condition_max }}</p>
                                    <a href="{{ route('garage.show', $active) }}" class="inline-block mt-3 text-sm text-accent-blue hover:text-accent-neon">{{ __('View car') }}</a>
                                </div>
                            </div>
                        @else
                            <p class="text-gray-400">{{ __('No active car selected.') }}</p>
                            <a href="{{ route('garage.index') }}" class="inline-block mt-2 text-sm text-accent-blue hover:text-accent-neon">{{ __('Open garage') }}</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
