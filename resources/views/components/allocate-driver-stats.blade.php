@props(['profile', 'labels' => []])

@if ($profile->unspent_stat_points > 0)
    <div class="bg-racing-700 border border-accent-neon rounded-lg p-6 space-y-4">
        <div>
            <h3 class="text-lg font-semibold text-accent-neon">{{ __('Allocate stat points') }}</h3>
            <p class="text-gray-400 text-sm mt-1">
                {{ __('You have :count unspent point(s). Distribute them across your driver stats.', ['count' => $profile->unspent_stat_points]) }}
            </p>
        </div>

        <form method="POST" action="{{ route('players.stats.store') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach (['power', 'acceleration', 'grip', 'handling'] as $key)
                    @php $column = 'stat_'.$key; @endphp
                    <div>
                        <x-input-label :for="$column" :value="($labels[$key] ?? ucfirst($key)).' (+'.__('points').')'" />
                        <x-text-input
                            :id="$column"
                            class="block mt-1 w-full"
                            type="number"
                            name="{{ $column }}"
                            min="0"
                            :max="$profile->unspent_stat_points"
                            :value="old($column, 0)"
                            required
                        />
                        <p class="text-gray-500 text-xs mt-1">
                            {{ __('Current') }}: {{ $profile->driverStats()[$key] }}
                        </p>
                        <x-input-error :messages="$errors->get($column)" class="mt-2" />
                    </div>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('stats')" class="mt-2" />
            <x-primary-button>{{ __('Allocate points') }}</x-primary-button>
        </form>
    </div>
@endif
