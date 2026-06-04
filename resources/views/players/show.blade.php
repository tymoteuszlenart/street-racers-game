<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-200 leading-tight">
                {{ $player->name }}
                @if ($isSelf)
                    <span class="text-gray-500 font-normal text-base">({{ __('you') }})</span>
                @endif
            </h2>
            @if (! $isSelf && $profile->active_car_id)
                <a href="{{ route('pvp.index', ['challenge' => $player->id]) }}" class="text-accent-orange hover:underline text-sm">
                    {{ __('Challenge in PvP') }}
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'stats-allocated')
                <div class="bg-racing-700 border border-accent-green text-accent-green px-4 py-3 rounded-lg text-sm">
                    {{ __('Driver stats updated.') }}
                </div>
            @endif

            @if ($isSelf)
                <x-allocate-driver-stats :profile="$profile" :labels="$driverStatLabels" />
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-racing-800 border border-racing-600 rounded-lg p-6">
                    <p class="text-gray-500 text-xs uppercase">{{ __('Level') }}</p>
                    <p class="text-3xl font-bold text-white">{{ $profile->level }}</p>
                    @if ($levelProgress)
                        <p class="text-gray-400 text-sm mt-2">
                            {{ __(':current / :required XP to level :level', [
                                'current' => number_format($levelProgress['current']),
                                'required' => number_format($levelProgress['required']),
                                'level' => $levelProgress['next_level'],
                            ]) }}
                        </p>
                    @elseif ($isSelf && $profile->level >= config('game.player.max_level'))
                        <p class="text-gray-400 text-sm mt-2">{{ __('Max level reached') }}</p>
                    @endif
                </div>
                <div class="bg-racing-800 border border-racing-600 rounded-lg p-6">
                    <p class="text-gray-500 text-xs uppercase">{{ __('Reputation') }}</p>
                    <p class="text-3xl font-bold text-white">{{ number_format($profile->reputation) }}</p>
                    <a href="{{ route('leaderboard.index') }}" class="inline-block mt-2 text-sm text-accent-blue hover:text-accent-neon">
                        {{ __('Rankings') }}
                    </a>
                </div>
                @if ($player->clubMember?->club)
                    <div class="bg-racing-800 border border-racing-600 rounded-lg p-6">
                        <p class="text-gray-500 text-xs uppercase">{{ __('Club') }}</p>
                        <p class="text-xl font-bold text-white">
                            <a href="{{ route('clubs.show', $player->clubMember->club) }}" class="hover:text-accent-neon">
                                {{ $player->clubMember->club->name }}
                            </a>
                        </p>
                    </div>
                @endif
            </div>

            <div class="bg-racing-800 border border-racing-600 rounded-lg p-6 space-y-4">
                <div>
                    <h3 class="text-lg font-semibold text-accent-neon">{{ __('Driver stats') }}</h3>
                    <p class="text-gray-500 text-sm mt-1">
                        {{ __('Earn stat points when you level up and spend them here. Each race type favors different driver stats (Force, Reaction, Control, Technique).') }}
                    </p>
                    @if ($isSelf && $profile->unspent_stat_points > 0)
                        <p class="text-accent-neon text-sm mt-2">
                            {{ __(':count unspent stat point(s)', ['count' => $profile->unspent_stat_points]) }}
                        </p>
                    @endif
                </div>
                <x-driver-stats :stats="$profile->driverStats()" :labels="$driverStatLabels" />
            </div>

            <div class="bg-racing-800 border border-racing-600 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-accent-neon mb-4">{{ __('Active car') }}</h3>
                @if ($profile->activeCar)
                    @php $active = $profile->activeCar; @endphp
                    <div class="flex flex-col sm:flex-row gap-6 items-start">
                        <x-car-image :car-model="$active->carModel" class="h-24 w-40 object-contain" />
                        <div>
                            <p class="text-xl font-bold text-white">{{ $active->carModel->name }}</p>
                            <p class="text-gray-400">{{ __('Class') }} {{ $active->carModel->class->value }}</p>
                            <p class="text-gray-500 text-sm mt-1">
                                <x-condition-meter :current="$active->condition_current" :max="$active->condition_max" />
                            </p>
                        </div>
                    </div>
                @else
                    <p class="text-gray-400">{{ __('No active car selected.') }}</p>
                @endif
            </div>

            @if ($isSelf)
                <p class="text-gray-500 text-sm">
                    <a href="{{ route('profile.edit') }}" class="text-accent-blue hover:text-accent-neon">{{ __('Account settings') }}</a>
                    ·
                    <a href="{{ route('dashboard') }}" class="text-accent-blue hover:text-accent-neon">{{ __('Dashboard') }}</a>
                </p>
            @endif
        </div>
    </div>
</x-app-layout>
