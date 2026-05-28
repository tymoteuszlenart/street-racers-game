<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-200 leading-tight">
                {{ __('Club tournament') }} — {{ $club->name }}
            </h2>
            <a href="{{ route('clubs.show', $club) }}" class="text-sm text-accent-blue hover:text-accent-neon">
                {{ __('Back to club') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-racing-800 border border-racing-600 rounded-lg p-6 space-y-3">
                <p class="text-gray-400 text-sm">{{ __('Season') }}: {{ $tournament->season_key }}</p>
                <p class="text-gray-400 text-sm">
                    {{ __('Ends') }}: {{ $tournament->ends_at->timezone(config('app.timezone'))->format('M j, Y g:i A T') }}
                </p>
                <p class="text-gray-400 text-sm">
                    {{ __('Club points') }}: <span class="text-white font-semibold">{{ number_format($club->points) }}</span>
                </p>
                @if ($profile)
                    <p class="text-gray-400 text-sm">
                        {{ __('Premium fuel') }}:
                        <span class="text-white font-semibold">{{ $profile->premium_fuel_current }} / {{ $storageMax }}</span>
                    </p>
                @endif
            </div>

            @if (! $isMember)
                <p class="text-gray-400">{{ __('Join this club to enter tournament races.') }}</p>
            @else
                <p class="text-gray-300">
                    {{ __('Attempts this season: :count / :max', ['count' => $attemptCount, 'max' => $maxAttempts]) }}
                </p>

                @if ($errors->any())
                    <div class="bg-racing-700 border border-accent-orange text-accent-orange px-4 py-3 rounded-lg space-y-1">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                @if ($attemptCount < $maxAttempts)
                    <form method="POST" action="{{ route('clubs.tournament.races.store', $club) }}" class="bg-racing-800 border border-racing-600 rounded-lg p-6">
                        @csrf
                        <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
                        <p class="text-gray-400 text-sm mb-4">
                            {{ __('Costs :cost premium fuel per race. Only your best :count scores count toward club points.', [
                                'cost' => config('game.premium_fuel.tournament_entry_cost'),
                                'count' => config('game.tournaments.counted_attempts_per_player'),
                            ]) }}
                        </p>
                        <x-primary-button type="submit">{{ __('Start tournament race') }}</x-primary-button>
                    </form>
                @endif

                @if ($countedEntries->isNotEmpty())
                    <div class="bg-racing-800 border border-racing-600 rounded-lg p-6">
                        <h3 class="text-white font-semibold mb-3">{{ __('Counted scores (best :n)', ['n' => config('game.tournaments.counted_attempts_per_player')]) }}</h3>
                        <ul class="text-gray-300 text-sm space-y-1">
                            @foreach ($countedEntries as $entry)
                                <li>{{ __(':points pts', ['points' => $entry->points]) }} — {{ $entry->created_at->format('M j g:i A') }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endif

            <a href="{{ route('tournament-rewards.index') }}" class="text-sm text-accent-blue hover:text-accent-neon">
                {{ __('Tournament reward history') }}
            </a>
        </div>
    </div>
</x-app-layout>
