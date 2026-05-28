<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('Premium fuel') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-gray-400">
                {{ __('Storage:') }}
                <span class="text-white font-semibold">{{ $profile?->premium_fuel_current ?? 0 }} / {{ $storageMax }}</span>
            </p>

            @if (session('status') === 'premium-fuel-claimed')
                <div class="bg-racing-700 border border-accent-green text-accent-green px-4 py-3 rounded-lg text-sm">
                    {{ __('Claimed :amount premium fuel.', ['amount' => session('premium_fuel_granted', 0)]) }}
                </div>
            @elseif (session('status') === 'premium-fuel-existing')
                <div class="bg-racing-700 border border-racing-600 text-gray-300 px-4 py-3 rounded-lg text-sm">
                    {{ __('You already claimed premium fuel today.') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-racing-700 border border-accent-orange text-accent-orange px-4 py-3 rounded-lg space-y-1">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="bg-racing-800 border border-racing-600 rounded-lg p-6 space-y-4">
                <p class="text-gray-400 text-sm">
                    {{ __('Daily claim resets at midnight (:timezone). Each claim grants :amount premium fuel (up to storage cap).', [
                        'timezone' => $timezone,
                        'amount' => $configuredAmount,
                    ]) }}
                </p>

                @if ($claimedToday)
                    <p class="text-accent-green">{{ __('Already claimed today.') }}</p>
                @elseif ($atCap)
                    <p class="text-gray-300">{{ __('Storage is full. Spend premium fuel on tournament races first.') }}</p>
                @elseif ($canClaim)
                    <form method="POST" action="{{ route('premium-fuel.claim') }}">
                        @csrf
                        <x-primary-button type="submit">{{ __('Claim daily premium fuel') }}</x-primary-button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
