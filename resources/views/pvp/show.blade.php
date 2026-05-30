<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('PvP Race Result') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-racing-800 border border-racing-600 rounded-lg p-6 space-y-4">
                @if (session('status') === 'pvp-existing-result')
                    <p class="text-gray-400 text-sm">{{ __('Your previous race submission already finished, so we are showing that result.') }}</p>
                @endif

                <p class="text-2xl font-bold {{ $raceResult->is_tie ? 'text-red-400' : ($raceResult->won ? 'text-green-400' : 'text-red-400') }}">
                    @if ($raceResult->is_tie)
                        {{ __('Draw — counted as a loss') }}
                    @elseif ($raceResult->won)
                        {{ __('You won!') }}
                    @else
                        {{ __('You lost') }}
                    @endif
                </p>

                @if ($raceResult->pvpRace?->defender)
                    <p class="text-gray-300">
                        {{ __('vs :name', ['name' => $raceResult->pvpRace->defender->name]) }}
                    </p>
                @endif

                <dl class="grid grid-cols-2 gap-4 text-gray-300">
                    <div>
                        <dt class="text-gray-500 text-sm">{{ __('Your score') }}</dt>
                        <dd class="text-white font-semibold">{{ $raceResult->player_score }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-sm">{{ __('Opponent score') }}</dt>
                        <dd class="text-white font-semibold">{{ $raceResult->opponent_score }}</dd>
                    </div>
                </dl>

                <x-race-score-breakdown
                    :score-breakdown="$raceResult->score_breakdown"
                    :driver-stat-labels="config('game.player.driver_stats.labels', [])"
                />

                <div class="flex flex-wrap gap-4">
                    <a href="{{ route('pvp.index') }}" class="inline-block text-accent-orange hover:underline">
                        {{ __('Back to PvP') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
