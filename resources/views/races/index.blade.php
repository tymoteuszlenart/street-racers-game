<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('Races') }}
        </h2>
    </x-slot>

    @php
        $tabValues = $raceTypes->map(fn ($type) => $type->value)->all();
        $defaultTab = in_array(request('tab'), $tabValues, true)
            ? request('tab')
            : ($tabValues[0] ?? null);
    @endphp

    <div
        class="py-12"
        x-data="{ tab: @js($defaultTab) }"
    >
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-gray-400">
                {{ __('Fuel:') }}
                <span class="text-white font-semibold">{{ $profile->fuel_current }} / {{ $profile->fuel_max }}</span>
            </p>

            @if ($errors->any())
                <div class="bg-racing-700 border border-accent-orange text-accent-orange px-4 py-3 rounded-lg space-y-1">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @if ($raceTypes->isEmpty())
                <p class="text-gray-500">{{ __('No races available at your level yet.') }}</p>
            @else
                <div class="border-b border-racing-600">
                    <nav class="flex gap-1 overflow-x-auto" aria-label="{{ __('Race types') }}">
                        @foreach ($raceTypes as $raceType)
                            <button
                                type="button"
                                @click="tab = @js($raceType->value)"
                                :class="tab === @js($raceType->value) ? 'border-accent-neon text-accent-neon' : 'border-transparent text-gray-400 hover:text-gray-200'"
                                class="px-4 py-2 text-sm font-semibold border-b-2 transition whitespace-nowrap"
                            >
                                {{ $raceType->label() }}
                            </button>
                        @endforeach
                    </nav>
                </div>

                @foreach ($raceTypes as $raceType)
                    @php
                        $typeRaces = $racesByType[$raceType->value];
                    @endphp
                    <div x-show="tab === @js($raceType->value)" x-cloak class="space-y-4">
                        <p class="text-gray-500 text-sm">
                            {{ __('Amateur, Semi-Pro, and Pro tiers available for this race type.') }}
                        </p>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            @foreach ($typeRaces->sortBy(fn ($race) => $race->resolvedTier()->sortOrder()) as $race)
                                <div class="bg-racing-800 border border-racing-600 rounded-lg p-6 space-y-4">
                                    <div>
                                        <h3 class="text-xl font-bold text-white">{{ $race->resolvedTier()->label() }}</h3>
                                        <p class="text-gray-400 text-sm mt-1">{{ $race->description }}</p>
                                        <p class="text-gray-400 text-sm mt-2">
                                            {{ __('Difficulty:') }} {{ $race->difficultyLabel() }} ·
                                            {{ __('Cost:') }} {{ $race->fuel_cost }} {{ __('fuel') }} ·
                                            {{ __('Win:') }} ${{ number_format($race->cash_reward_win) }}
                                        </p>
                                    </div>
                                    <form method="POST" action="{{ route('races.start', $race) }}">
                                        @csrf
                                        <input type="hidden" name="idempotency_key" value="{{ $raceIdempotencyKeys[$race->id] }}">
                                        <x-primary-button type="submit">
                                            {{ __('Race') }}
                                        </x-primary-button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</x-app-layout>
