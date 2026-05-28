<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('Race Result') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-racing-800 border border-racing-600 rounded-lg p-6 space-y-4">
                @if (session('status') === 'race-existing-result')
                    <p class="text-gray-400 text-sm">{{ __('Your previous race submission already finished, so we are showing that result.') }}</p>
                @endif

                <p class="text-2xl font-bold {{ $raceResult->won ? 'text-green-400' : 'text-red-400' }}">
                    {{ $raceResult->won ? __('You won!') : __('You lost') }}
                </p>

                @if ($raceResult->race)
                    <p class="text-gray-300">{{ $raceResult->race->name }}</p>
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

                <a href="{{ route('races.index') }}" class="inline-block text-accent-orange hover:underline">
                    {{ __('Back to races') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
