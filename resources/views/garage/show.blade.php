<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-200 leading-tight">
                {{ $car->carModel->name }}
            </h2>
            <a href="{{ route('garage.index') }}" class="text-sm text-accent-blue hover:text-accent-neon">{{ __('Back to Garage') }}</a>
        </div>
    </x-slot>

    <div class="py-12" x-data="{ sellOpen: false }">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'active-car-set')
                <div class="bg-racing-700 border border-accent-green text-accent-green px-4 py-3 rounded-lg">
                    {{ __('This car is now your active car.') }}
                </div>
            @elseif (session('status') === 'car-purchased')
                <div class="bg-racing-700 border border-accent-green text-accent-green px-4 py-3 rounded-lg">
                    {{ __('Car purchased successfully.') }}
                </div>
            @elseif (session('status') === 'car-sold')
                <div class="bg-racing-700 border border-accent-green text-accent-green px-4 py-3 rounded-lg">
                    {{ __('Sold for $:amount.', ['amount' => number_format(session('sold_amount'))]) }}
                </div>
            @endif

            <div class="bg-racing-800 border border-racing-600 rounded-lg overflow-hidden">
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <x-garage-scene :car-model="$car->carModel" car-image-class="h-36 sm:h-44 w-auto max-w-full object-contain drop-shadow-[0_8px_24px_rgba(0,0,0,0.85)]" />
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
                            <h3 class="text-2xl font-bold text-white">{{ $car->carModel->name }}</h3>
                            <p class="text-gray-400">{{ __('Class') }} {{ $car->carModel->class->value }} · {{ ucfirst($car->carModel->rarity) }}</p>
                        </div>
                        <div class="text-sm text-gray-400 space-y-1">
                            <p class="text-sm">
                                <x-condition-meter :current="$car->condition_current" :max="$car->condition_max" />
                            </p>
                            <p>{{ __('Acquired') }}: <span class="text-white">{{ ucfirst($car->acquired_via->value) }}</span></p>
                            @if ($car->purchase_price !== null)
                                <p>{{ __('Purchase price') }}: <span class="text-white">${{ number_format($car->purchase_price) }}</span></p>
                            @endif
                        </div>
                        <div class="pt-4 border-t border-racing-600 space-y-2">
                            <div class="flex flex-wrap gap-2">
                                @if ($tuningUnlocked)
                                    @if ($car->condition_current < $car->condition_max)
                                        <a href="{{ route('mechanic.index', ['tab' => 'repair']) }}" class="px-3 py-1 text-xs rounded bg-racing-700 text-accent-orange border border-racing-600 hover:border-accent-orange">{{ __('Repair') }}</a>
                                    @else
                                        <button type="button" disabled class="px-3 py-1 text-xs rounded bg-racing-700 text-gray-500 border border-racing-600 cursor-not-allowed" title="{{ __('Full condition') }}">{{ __('Repair') }}</button>
                                    @endif
                                @else
                                    <button type="button" disabled title="{{ __('Reach level 5') }}" class="px-3 py-1 text-xs rounded bg-racing-700 text-gray-500 border border-racing-600 cursor-not-allowed">{{ __('Repair') }} (Lvl 5)</button>
                                @endif
                                @if ($tuningUnlocked)
                                    <a href="{{ route('garage.upgrades', $car) }}" class="px-3 py-1 text-xs rounded bg-racing-700 text-accent-neon border border-racing-600 hover:border-accent-neon">{{ __('Tune') }}</a>
                                @else
                                    <button type="button" disabled title="{{ __('Reach level 5') }}" class="px-3 py-1 text-xs rounded bg-racing-700 text-gray-500 border border-racing-600 cursor-not-allowed">{{ __('Tune') }} (Lvl 5)</button>
                                @endif
                                @if ($sellQuote->sellable)
                                    <button
                                        type="button"
                                        @click="sellOpen = true"
                                        class="px-3 py-1 text-xs rounded bg-racing-700 text-accent-orange border border-racing-600 hover:border-accent-orange"
                                    >
                                        {{ __('Sell') }}
                                    </button>
                                @else
                                    <button
                                        type="button"
                                        disabled
                                        title="{{ $sellQuote->blockedReason }}"
                                        class="px-3 py-1 text-xs rounded bg-racing-700 text-gray-500 border border-racing-600 cursor-not-allowed"
                                    >
                                        {{ __('Sell') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 pb-6 space-y-6">
                    <div>
                        <h4 class="text-accent-orange font-semibold mb-3">{{ __('Effective stats') }}</h4>
                        <x-effective-car-stats :base-stats="$baseStats" :effective-stats="$effectiveStats" />
                    </div>
                    <div>
                        <h4 class="text-gray-500 text-xs uppercase tracking-wide mb-3">{{ __('Base model stats') }}</h4>
                        <x-car-stats :car-model="$car->carModel" />
                    </div>
                </div>
            </div>

            <div
                x-show="sellOpen"
                x-cloak
                x-transition
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80"
                @keydown.escape.window="sellOpen = false"
            >
                <div
                    class="bg-racing-800 border border-racing-600 rounded-lg max-w-md w-full p-6 space-y-4"
                    @click.outside="sellOpen = false"
                >
                    <h3 class="text-lg font-bold text-white">{{ __('Sell car') }}</h3>
                    <p class="text-gray-400 text-sm">
                        {{ __('You will receive cash for this car and any equipped parts.') }}
                    </p>
                    @include('garage.partials.sell-quote-lines', ['sellQuote' => $sellQuote])
                    <div class="flex flex-wrap gap-2 justify-end pt-2">
                        <button
                            type="button"
                            @click="sellOpen = false"
                            class="px-4 py-2 text-sm rounded bg-racing-700 text-gray-300 border border-racing-600 hover:text-white"
                        >
                            {{ __('Cancel') }}
                        </button>
                        <form method="POST" action="{{ route('garage.cars.sell', $car) }}">
                            @csrf
                            @method('DELETE')
                            <x-primary-button class="!bg-accent-orange hover:!opacity-90">
                                {{ __('Confirm sell') }}
                            </x-primary-button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
