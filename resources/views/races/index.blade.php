<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('Races') }}
        </h2>
    </x-slot>

    <div class="py-12">
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

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach ($races as $race)
                    <div class="bg-racing-800 border border-racing-600 rounded-lg p-6 space-y-4">
                        <div>
                            <h3 class="text-xl font-bold text-white">{{ $race->name }}</h3>
                            <p class="text-gray-400 text-sm">{{ $race->description }}</p>
                            <p class="text-gray-400 text-sm mt-1">
                                {{ __('Cost:') }} {{ $race->fuel_cost }} {{ __('fuel') }} ·
                                {{ __('Win:') }} ${{ number_format($race->cash_reward_win) }}
                            </p>
                        </div>
                        <form method="POST" action="{{ route('races.start', $race) }}">
                            @csrf
                            <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $raceIdempotencyKeys[$race->id] ?? '') }}">
                            <x-primary-button type="submit">
                                {{ __('Race') }}
                            </x-primary-button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
