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

                <p class="text-2xl font-bold {{ $raceResult->is_tie ? 'text-red-400' : ($raceResult->won ? 'text-green-400' : 'text-red-400') }}">
                    @if ($raceResult->is_tie)
                        {{ __('Draw — counted as a loss') }}
                    @elseif ($raceResult->won)
                        {{ __('You won!') }}
                    @else
                        {{ __('You lost') }}
                    @endif
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

                @if ($rewards)
                    <div class="border-t border-racing-600 pt-4">
                        <h3 class="text-sm font-medium text-gray-400 uppercase tracking-wider mb-3">{{ __('Rewards earned') }}</h3>
                        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-gray-300">
                            <div>
                                <dt class="text-gray-500 text-sm">{{ __('Cash') }}</dt>
                                <dd class="text-white font-semibold">+${{ number_format($rewards['cash']) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 text-sm">{{ __('Reputation') }}</dt>
                                <dd class="text-white font-semibold">+{{ number_format($rewards['reputation']) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 text-sm">{{ __('Experience') }}</dt>
                                <dd class="text-white font-semibold">+{{ number_format($rewards['experience']) }} {{ __('XP') }}</dd>
                            </div>
                        </dl>
                    </div>
                @endif

                <a href="{{ route('races.index') }}" class="inline-block text-accent-orange hover:underline">
                    {{ __('Back to races') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
