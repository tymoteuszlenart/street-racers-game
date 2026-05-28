<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-200 leading-tight">
                {{ __('Race History') }}
            </h2>
            <a href="{{ route('races.index') }}" class="text-accent-orange hover:underline text-sm">
                {{ __('Race now') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-gray-500 text-sm">
                {{ __('Your NPC and PvP races as challenger.') }}
                <a href="{{ route('pvp.history') }}" class="text-accent-blue hover:text-accent-neon">{{ __('PvP as defender') }}</a>
            </p>

            <div class="space-y-4">
                @forelse ($results as $result)
                    @php
                        $isPvp = $result->attempt_type === \App\Enums\RaceAttemptType::Pvp;
                        $detailRoute = $isPvp
                            ? route('pvp.show', $result)
                            : route('races.show', $result);
                        $opponentLabel = $isPvp
                            ? ($result->pvpRace?->defender?->name ?? __('Unknown player'))
                            : ($result->race?->name ?? __('NPC race'));
                    @endphp
                    <div class="bg-racing-800 border border-racing-600 rounded-lg p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-500">
                                    {{ $isPvp ? __('PvP') : __('NPC') }}
                                </p>
                                <h3 class="text-lg font-bold text-white">{{ $opponentLabel }}</h3>
                                <p class="text-gray-400 text-sm">
                                    {{ $result->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                </p>
                            </div>
                            <div class="text-right space-y-1">
                                <p class="font-semibold {{ $result->is_tie ? 'text-yellow-400' : ($result->won ? 'text-green-400' : 'text-red-400') }}">
                                    @if ($result->is_tie)
                                        {{ __('Draw') }}
                                    @elseif ($result->won)
                                        {{ __('Win') }}
                                    @else
                                        {{ __('Loss') }}
                                    @endif
                                    · {{ $result->player_score }} vs {{ $result->opponent_score }}
                                </p>
                                <a href="{{ $detailRoute }}" class="text-sm text-accent-orange hover:underline">
                                    {{ __('View result') }}
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-400">{{ __('No races yet. Head to the race list to start.') }}</p>
                @endforelse
            </div>

            <div>
                {{ $results->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
