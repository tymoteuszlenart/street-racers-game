<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('Open Cup') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-racing-700 border border-accent-neon text-accent-neon px-4 py-3 rounded-lg">
                    {{ session('status') === 'cup-created' ? __('Open Cup created — you are in!') : __('Done.') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-racing-700 border border-accent-orange text-accent-orange px-4 py-3 rounded-lg space-y-1">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="bg-racing-800 border border-racing-600 rounded-lg p-6 space-y-3">
                <p class="text-gray-400 text-sm">
                    {{ __('Cash:') }}
                    <span class="text-white font-semibold">${{ number_format($profile->cash) }}</span>
                    ·
                    {{ __('Cups:') }}
                    <span class="text-white font-semibold">{{ number_format($profile->cups ?? 0) }}</span>
                </p>
                <p class="text-gray-400 text-sm">
                    {{ __('Entry fee: $:fee (cash only, no fuel). Join window :minutes minutes.', [
                        'fee' => number_format($entryFee),
                        'minutes' => config('game.open_cup.join_window_minutes'),
                    ]) }}
                </p>
            </div>

            @if ($activeEntry)
                <div class="bg-racing-800 border border-accent-neon rounded-lg p-6">
                    <p class="text-white font-semibold">{{ __('Your active Open Cup') }}</p>
                    <p class="text-gray-400 text-sm mt-1">
                        {{ __('Status: :status', ['status' => $activeEntry->openCup->status->value]) }}
                    </p>
                    <a href="{{ route('cups.show', $activeEntry->openCup) }}" class="inline-block mt-3 text-sm text-accent-blue hover:text-accent-neon">
                        {{ __('View cup') }}
                    </a>
                </div>
            @else
                <form method="POST" action="{{ route('cups.store') }}" class="bg-racing-800 border border-racing-600 rounded-lg p-6">
                    @csrf
                    <p class="text-gray-400 text-sm mb-4">
                        {{ __('Host a new Open Cup. You pay the entry fee and lock in your car snapshot.') }}
                    </p>
                    <x-primary-button type="submit">{{ __('Host Open Cup') }}</x-primary-button>
                </form>
            @endif

            <div class="space-y-4">
                <h3 class="text-white font-semibold">{{ __('Open for joining') }}</h3>
                @forelse ($openCups as $cup)
                    <div class="bg-racing-800 border border-racing-600 rounded-lg p-6 flex flex-wrap justify-between gap-4">
                        <div>
                            <p class="text-white font-semibold">
                                {{ __('Cup #:id', ['id' => $cup->id]) }}
                                @if ($cup->entries->contains('user_id', auth()->id()))
                                    <span class="text-accent-neon text-sm ms-2">{{ __('Joined') }}</span>
                                @endif
                            </p>
                            <p class="text-gray-400 text-sm">
                                {{ __('Host: :name · :count entrants', [
                                    'name' => $cup->host?->name ?? __('Unknown'),
                                    'count' => $cup->entries->count(),
                                ]) }}
                            </p>
                            <p class="text-gray-400 text-sm">
                                {{ __('Closes in :time', ['time' => $cup->join_ends_at->diffForHumans()]) }}
                            </p>
                        </div>
                        <a href="{{ route('cups.show', $cup) }}" class="text-sm text-accent-blue hover:text-accent-neon self-center">
                            {{ __('Details') }}
                        </a>
                    </div>
                @empty
                    <p class="text-gray-400">{{ __('No Open Cups accepting entrants right now.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
