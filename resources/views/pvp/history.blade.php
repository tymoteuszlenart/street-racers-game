<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-200 leading-tight">
                {{ __('PvP History') }}
            </h2>
            <a href="{{ route('pvp.index') }}" class="text-accent-orange hover:underline text-sm">
                {{ __('Find opponents') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-gray-500 text-sm">
                {{ __('Read-only list of PvP races other players ran against your active car.') }}
            </p>

            <div class="space-y-4">
                @forelse ($races as $pvpRace)
                    @php
                        $result = $pvpRace->raceResult;
                        $challengerWon = $result?->won ?? false;
                    @endphp
                    <div class="bg-racing-800 border border-racing-600 rounded-lg p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div>
                                <h3 class="text-lg font-bold text-white">
                                    {{ $pvpRace->challenger->name }}
                                </h3>
                                <p class="text-gray-400 text-sm">
                                    {{ $pvpRace->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                </p>
                            </div>
                            @if ($result)
                                <p class="font-semibold {{ $challengerWon ? 'text-red-400' : 'text-green-400' }}">
                                    @if ($result->is_tie)
                                        {{ __('Draw (they lost)') }}
                                    @elseif ($challengerWon)
                                        {{ __('They won') }}
                                    @else
                                        {{ __('You won') }}
                                    @endif
                                    · {{ $result->player_score }} vs {{ $result->opponent_score }}
                                </p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-gray-400">{{ __('No one has raced against you yet.') }}</p>
                @endforelse
            </div>

            <div>
                {{ $races->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
