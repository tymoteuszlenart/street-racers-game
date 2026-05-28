<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-200 leading-tight">
                {{ __('PvP Races') }}
            </h2>
            <a href="{{ route('pvp.history') }}" class="text-accent-orange hover:underline text-sm">
                {{ __('Races against me') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-gray-400">
                {{ __('Fuel:') }}
                <span class="text-white font-semibold">{{ $profile->fuel_current }} / {{ $profile->fuel_max }}</span>
                · {{ __('Cost per PvP race:') }} {{ config('game.pvp.fuel_cost') }} {{ __('fuel') }}
            </p>

            <p class="text-gray-500 text-sm">
                {{ __('PvP races grant no cash, reputation, or XP. Daily limit: :cap races per opponent pair.', ['cap' => config('game.pvp.daily_pair_cap')]) }}
            </p>

            @if ($errors->any())
                <div class="bg-racing-700 border border-accent-orange text-accent-orange px-4 py-3 rounded-lg space-y-1">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="space-y-4">
                @forelse ($opponents as $opponent)
                    <div class="bg-racing-800 border border-racing-600 rounded-lg p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-white">{{ $opponent->name }}</h3>
                            @if ($opponent->playerProfile?->activeCar)
                                <p class="text-gray-400 text-sm">
                                    {{ $opponent->playerProfile->activeCar->nickname }}
                                </p>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('pvp.start', $opponent) }}">
                            @csrf
                            <input type="hidden" name="idempotency_key" value="{{ $opponentIdempotencyKeys[$opponent->id] }}">
                            <x-primary-button type="submit">
                                {{ __('Race') }}
                            </x-primary-button>
                        </form>
                    </div>
                @empty
                    <p class="text-gray-400">{{ __('No opponents with an active car are available right now.') }}</p>
                @endforelse
            </div>

            <div>
                {{ $opponents->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
