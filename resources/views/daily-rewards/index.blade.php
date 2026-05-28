<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('Daily Rewards') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-racing-800 border border-racing-600 rounded-lg p-6 space-y-6">
                @if (session('status') === 'daily-reward-claimed')
                    <p class="text-green-400 text-sm">
                        {{ __('Daily fuel claimed!') }}
                        {{ __('+:amount fuel added.', ['amount' => session('fuel_granted')]) }}
                    </p>
                @elseif (session('status') === 'daily-reward-existing')
                    <p class="text-gray-400 text-sm">
                        {{ __('You already claimed today\'s reward.') }}
                    </p>
                @endif

                @if ($errors->has('fuel'))
                    <div class="bg-racing-700 border border-accent-orange text-accent-orange px-4 py-3 rounded-lg">
                        <p>{{ $errors->first('fuel') }}</p>
                    </div>
                @endif

                <div>
                    <h3 class="text-xl font-bold text-accent-neon mb-2">{{ __('Login bonus') }}</h3>
                    <p class="text-gray-400">
                        {{ __('Claim :fuel fuel once per day when you have room in your tank. Resets at midnight (:timezone).', [
                            'fuel' => $configuredFuel,
                            'timezone' => $timezone,
                        ]) }}
                    </p>
                </div>

                <dl class="grid grid-cols-2 gap-4 text-gray-300">
                    <div>
                        <dt class="text-gray-500 text-sm">{{ __('Current fuel') }}</dt>
                        <dd class="text-white font-semibold">{{ $profile->fuel_current }} / {{ $profile->fuel_max }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-sm">{{ __('Today\'s status') }}</dt>
                        <dd class="text-white font-semibold">
                            @if ($claimedToday)
                                {{ __('Claimed') }}
                            @elseif ($tankFull)
                                {{ __('Tank full') }}
                            @else
                                {{ __('Available') }}
                            @endif
                        </dd>
                    </div>
                </dl>

                @if ($claimedToday)
                    <p class="text-gray-500 text-sm">{{ __('Come back after midnight to claim again.') }}</p>
                @elseif ($tankFull)
                    <div class="bg-racing-700 border border-accent-orange rounded-lg px-4 py-3 text-accent-orange text-sm space-y-2">
                        <p class="font-semibold">{{ __('Fuel tank is full') }}</p>
                        <p>{{ __('Spend some fuel on races first so you have room for today\'s :fuel fuel bonus.', ['fuel' => $configuredFuel]) }}</p>
                        <a href="{{ route('races.index') }}" class="inline-block text-accent-neon hover:underline">
                            {{ __('Go to races') }}
                        </a>
                    </div>
                    <x-primary-button disabled>
                        {{ __('Claim :fuel fuel', ['fuel' => $configuredFuel]) }}
                    </x-primary-button>
                @else
                    <form method="POST" action="{{ route('daily-rewards.claim') }}">
                        @csrf
                        <x-primary-button>
                            {{ __('Claim :fuel fuel', ['fuel' => $configuredFuel]) }}
                        </x-primary-button>
                    </form>
                @endif

                <a href="{{ route('dashboard') }}" class="inline-block text-accent-orange hover:underline text-sm">
                    {{ __('Back to dashboard') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
