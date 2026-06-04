<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('Mechanic') }}
        </h2>
    </x-slot>

    <div
        class="py-12"
        x-data="{
            tab: @js(in_array(request('tab'), ['repair'], true) ? 'repair' : 'upgrade'),
        }"
    >
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-gray-400">
                {{ __('Tune parts up to +:max and repair worn cars and parts. Cash on hand:', ['max' => $maxUpgradeLevel]) }}
                <span class="text-white font-semibold">${{ number_format($cash) }}</span>
            </p>

            @if (session('status') === 'part-upgraded')
                <div class="bg-racing-700 border border-accent-green text-accent-green px-4 py-3 rounded-lg">
                    {{ __('Part tuned successfully.') }}
                </div>
            @elseif (session('status') === 'car-repaired')
                <div class="bg-racing-700 border border-accent-green text-accent-green px-4 py-3 rounded-lg">
                    {{ __('Car repaired.') }}
                </div>
            @elseif (session('status') === 'part-repaired')
                <div class="bg-racing-700 border border-accent-green text-accent-green px-4 py-3 rounded-lg">
                    {{ __('Part repaired.') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-racing-700 border border-accent-orange text-accent-orange px-4 py-3 rounded-lg space-y-1">
                    @foreach ($errors->all() as $message)
                        <p>{{ $message }}</p>
                    @endforeach
                </div>
            @endif

            <div class="border-b border-racing-600">
                <nav class="flex gap-1" aria-label="{{ __('Mechanic sections') }}">
                    <button
                        type="button"
                        @click="tab = 'upgrade'"
                        :class="tab === 'upgrade' ? 'border-accent-neon text-accent-neon' : 'border-transparent text-gray-400 hover:text-gray-200'"
                        class="px-4 py-2 text-sm font-semibold border-b-2 transition"
                    >
                        {{ __('Tune') }}
                    </button>
                    <button
                        type="button"
                        @click="tab = 'repair'"
                        :class="tab === 'repair' ? 'border-accent-neon text-accent-neon' : 'border-transparent text-gray-400 hover:text-gray-200'"
                        class="px-4 py-2 text-sm font-semibold border-b-2 transition"
                    >
                        {{ __('Repair') }}
                    </button>
                </nav>
            </div>

            <div x-show="tab === 'upgrade'" x-cloak class="space-y-4">
                @if ($parts->isEmpty())
                    <p class="text-gray-500">{{ __('No parts in your inventory. Buy parts at the Shop.') }}</p>
                    <a href="{{ route('shop.index', ['tab' => 'parts']) }}" class="inline-block text-accent-blue hover:text-accent-neon text-sm">{{ __('Visit Shop') }}</a>
                @else
                    @foreach ($parts as $part)
                        @php
                            $upgradeCost = $upgradeCosts[$part->id] ?? 0;
                            $atMax = $part->upgrade_level >= $maxUpgradeLevel;
                        @endphp
                        <div class="bg-racing-800 border border-racing-600 rounded-lg p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <p class="text-white font-semibold">{{ $part->partModel->name }}</p>
                                <p class="text-gray-400 text-sm">
                                    {{ ucfirst($part->slot->value) }}
                                    · {{ __('Tune') }} +{{ $part->upgrade_level }}
                                    @if ($part->car_id !== null)
                                        · {{ __('On') }} {{ $part->car?->carModel?->name }}
                                    @else
                                        · {{ __('In inventory') }}
                                    @endif
                                </p>
                                <p class="text-xs mt-1">
                                    <x-condition-meter :current="$part->condition_current" :max="$part->condition_max" class="text-xs" />
                                </p>
                            </div>
                            @if ($atMax)
                                <span class="text-accent-green text-sm font-semibold">{{ __('Max tune') }}</span>
                            @else
                                <form method="POST" action="{{ route('mechanic.parts.upgrade', $part) }}">
                                    @csrf
                                    <x-primary-button>
                                        {{ __('Tune +:level ($:cost)', ['level' => $part->upgrade_level + 1, 'cost' => number_format($upgradeCost)]) }}
                                    </x-primary-button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>

            <div x-show="tab === 'repair'" x-cloak class="space-y-8">
                <section>
                    <h3 class="text-accent-orange font-semibold mb-4">{{ __('Cars') }}</h3>
                    @if ($cars->isEmpty())
                        <p class="text-gray-500">{{ __('No cars in your garage.') }}</p>
                    @else
                        <div class="space-y-3">
                            @foreach ($cars as $car)
                                @php $repairCost = $carRepairCosts[$car->id] ?? 0; @endphp
                                <div class="bg-racing-800 border border-racing-600 rounded-lg p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div>
                                        <p class="text-white font-semibold">{{ $car->carModel->name }}</p>
                                        <p class="text-sm">
                                            <x-condition-meter :current="$car->condition_current" :max="$car->condition_max" class="text-sm" />
                                        </p>
                                    </div>
                                    @if ($repairCost <= 0)
                                        <span class="text-accent-green text-sm">{{ __('No repair needed') }}</span>
                                    @else
                                        <form method="POST" action="{{ route('mechanic.cars.repair', $car) }}">
                                            @csrf
                                            <x-secondary-button>{{ __('Repair ($:cost)', ['cost' => number_format($repairCost)]) }}</x-secondary-button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section>
                    <h3 class="text-accent-orange font-semibold mb-4">{{ __('Parts') }}</h3>
                    @if ($parts->isEmpty())
                        <p class="text-gray-500">{{ __('No parts to repair.') }}</p>
                    @else
                        <div class="space-y-3">
                            @foreach ($parts as $part)
                                @php $repairCost = $partRepairCosts[$part->id] ?? 0; @endphp
                                <div class="bg-racing-800 border border-racing-600 rounded-lg p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div>
                                        <p class="text-white font-semibold">{{ $part->partModel->name }}</p>
                                        <p class="text-gray-400 text-sm">
                                            {{ ucfirst($part->slot->value) }}
                                            ·
                                            <x-condition-meter :current="$part->condition_current" :max="$part->condition_max" class="text-sm inline" />
                                        </p>
                                    </div>
                                    @if ($repairCost <= 0)
                                        <span class="text-accent-green text-sm">{{ __('No repair needed') }}</span>
                                    @else
                                        <form method="POST" action="{{ route('mechanic.parts.repair', $part) }}">
                                            @csrf
                                            <x-secondary-button>{{ __('Repair ($:cost)', ['cost' => number_format($repairCost)]) }}</x-secondary-button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
