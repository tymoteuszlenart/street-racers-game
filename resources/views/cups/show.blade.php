<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-200 leading-tight">
                {{ __('Open Cup #:id', ['id' => $cup->id]) }}
            </h2>
            <a href="{{ route('cups.index') }}" class="text-sm text-accent-blue hover:text-accent-neon">
                {{ __('All cups') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-racing-700 border border-accent-neon text-accent-neon px-4 py-3 rounded-lg">
                    @if (session('status') === 'cup-joined')
                        {{ __('You joined this Open Cup!') }}
                    @elseif (session('status') === 'cup-created')
                        {{ __('Open Cup created — you are in!') }}
                    @else
                        {{ session('status') }}
                    @endif
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-racing-700 border border-accent-orange text-accent-orange px-4 py-3 rounded-lg space-y-1">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="bg-racing-800 border border-racing-600 rounded-lg p-6 space-y-2">
                <p class="text-gray-400 text-sm">
                    {{ __('Status:') }}
                    <span class="text-white font-semibold">{{ ucfirst($cup->status->value) }}</span>
                </p>
                <p class="text-gray-400 text-sm">
                    {{ __('Entry fee:') }}
                    <span class="text-white font-semibold">${{ number_format($entryFee) }}</span>
                </p>
                <p class="text-gray-400 text-sm">
                    {{ __('Entrants: :count / :max', [
                        'count' => $cup->entries->count(),
                        'max' => config('game.open_cup.max_entrants'),
                    ]) }}
                </p>
                @if ($joinClosesInSeconds !== null && ! $viewerEntry)
                    <p class="text-accent-neon text-sm">
                        {{ __('Join closes in :minutes min', ['minutes' => max(1, (int) ceil($joinClosesInSeconds / 60))]) }}
                    </p>
                @endif
            </div>

            @if ($viewerEntry)
                <div class="bg-racing-800 border border-accent-neon rounded-lg p-6 space-y-2">
                    <p class="text-white font-semibold">{{ __("You're in") }}</p>
                    <p class="text-gray-400 text-sm">
                        {{ __('Car: :name', ['name' => $viewerEntry->car_snapshot['car_name'] ?? __('Unknown')]) }}
                    </p>
                    @if ($cup->status->value === 'completed')
                        <p class="text-gray-300 text-sm">
                            {{ __('Solo wins: :wins', ['wins' => $viewerEntry->solo_wins]) }}
                        </p>
                        @if ($viewerEntry->placement)
                            <p class="text-gray-300 text-sm">{{ __('Placement: #:place', ['place' => $viewerEntry->placement]) }}</p>
                        @endif
                    @endif
                </div>
            @elseif ($canJoin)
                <form method="POST" action="{{ route('cups.join', $cup) }}" class="bg-racing-800 border border-racing-600 rounded-lg p-6">
                    @csrf
                    <p class="text-gray-400 text-sm mb-4">
                        {{ __('Pay $:fee to join. Your current car snapshot is saved for all races.', ['fee' => number_format($entryFee)]) }}
                    </p>
                    <x-primary-button type="submit">{{ __('Join Open Cup') }}</x-primary-button>
                </form>
            @elseif ($cup->status->value === 'open')
                <p class="text-gray-400">{{ __('This cup is full or no longer accepting entrants.') }}</p>
            @endif

            @if ($cup->status->value === 'completed')
                <div class="bg-racing-800 border border-racing-600 rounded-lg p-6 space-y-3">
                    <h3 class="text-white font-semibold">{{ __('Results') }}</h3>
                    @if ($cup->championEntry)
                        <p class="text-gray-300 text-sm">
                            {{ __('Champion: :name', ['name' => $cup->championEntry->display_name]) }}
                        </p>
                    @else
                        <p class="text-gray-400 text-sm">{{ __('No champion (tie or bracket elimination).') }}</p>
                    @endif
                    <ul class="text-gray-300 text-sm space-y-1">
                        @foreach ($cup->entries as $entry)
                            <li>
                                {{ $entry->display_name }}
                                @if ($entry->placement)
                                    — {{ __('#:place', ['place' => $entry->placement]) }}
                                @endif
                                @if ($entry->solo_wins > 0)
                                    — {{ __(':wins solo wins', ['wins' => $entry->solo_wins]) }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @elseif (in_array($cup->status->value, ['settling', 'running'], true))
                <p class="text-gray-400">{{ __('Races are being resolved…') }}</p>
            @endif

            <div class="bg-racing-800 border border-racing-600 rounded-lg p-6">
                <h3 class="text-white font-semibold mb-3">{{ __('Entrants') }}</h3>
                <ul class="text-gray-300 text-sm space-y-1">
                    @foreach ($cup->entries as $entry)
                        <li>{{ $entry->display_name }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
