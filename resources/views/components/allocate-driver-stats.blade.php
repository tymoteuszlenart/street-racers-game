@props(['profile', 'labels' => []])

@if ($profile->unspent_stat_points > 0)
    <div
        x-data="{
            current: {
                power: {{ $profile->driverStats()['power'] }},
                acceleration: {{ $profile->driverStats()['acceleration'] }},
                grip: {{ $profile->driverStats()['grip'] }},
                handling: {{ $profile->driverStats()['handling'] }},
            },
            pending: {
                power: {{ old('stat_power', 0) }},
                acceleration: {{ old('stat_acceleration', 0) }},
                grip: {{ old('stat_grip', 0) }},
                handling: {{ old('stat_handling', 0) }},
            },
            unspent: {{ $profile->unspent_stat_points }},
            displayValue(stat) {
                return this.current[stat] + this.pending[stat];
            },
            totalPending() {
                return Object.values(this.pending).reduce((sum, value) => sum + value, 0);
            },
            canAdd() {
                return this.totalPending() < this.unspent;
            },
            add(stat) {
                if (this.canAdd()) {
                    this.pending[stat]++;
                }
            },
            subtract(stat) {
                if (this.pending[stat] > 0) {
                    this.pending[stat]--;
                }
            },
        }"
        class="bg-racing-700 border border-accent-neon rounded-lg p-6 space-y-4"
    >
        <div>
            <h3 class="text-lg font-semibold text-accent-neon">{{ __('Allocate stat points') }}</h3>
            <p class="text-gray-400 text-sm mt-1">
                {{ __('You have :count unspent point(s). Use + and − to assign them, then apply.', ['count' => $profile->unspent_stat_points]) }}
            </p>
            <p class="text-gray-500 text-xs mt-1">
                <span x-text="unspent - totalPending()"></span> {{ __('point(s) remaining') }}
            </p>
        </div>

        <x-input-error :messages="$errors->get('stats')" />

        <form method="POST" action="{{ route('players.stats.store') }}" class="space-y-4">
            @csrf
            @foreach (['power', 'acceleration', 'grip', 'handling'] as $key)
                @php $label = $labels[$key] ?? ucfirst($key); @endphp
                <input type="hidden" name="stat_{{ $key }}" :value="pending.{{ $key }}">
                <div class="flex items-center justify-between gap-3 bg-racing-800 border border-racing-600 rounded-lg px-4 py-3">
                    <span class="text-gray-300">{{ $label }}</span>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="subtract('{{ $key }}')"
                            :disabled="pending.{{ $key }} === 0"
                            class="inline-flex h-8 w-8 items-center justify-center rounded border font-bold text-lg leading-none transition-colors disabled:opacity-40 disabled:cursor-not-allowed border-racing-500 text-gray-400 hover:border-gray-400 hover:text-white"
                            aria-label="{{ __('Remove one pending point from :stat', ['stat' => $label]) }}"
                        >−</button>
                        <span
                            class="font-semibold tabular-nums min-w-[2ch] text-center transition-colors"
                            :class="pending.{{ $key }} > 0 ? 'text-accent-neon' : 'text-white'"
                            x-text="displayValue('{{ $key }}')"
                        ></span>
                        <button
                            type="button"
                            @click="add('{{ $key }}')"
                            :disabled="!canAdd()"
                            class="inline-flex h-8 w-8 items-center justify-center rounded border font-bold text-lg leading-none transition-colors disabled:opacity-40 disabled:cursor-not-allowed border-accent-neon text-accent-neon hover:bg-accent-neon hover:text-racing-900"
                            aria-label="{{ __('Add one point to :stat', ['stat' => $label]) }}"
                        >+</button>
                    </div>
                </div>
            @endforeach

            <button
                type="submit"
                x-bind:disabled="totalPending() === 0"
                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-40 disabled:cursor-not-allowed transition ease-in-out duration-150"
            >
                {{ __('Apply') }}
            </button>
        </form>
    </div>
@endif
