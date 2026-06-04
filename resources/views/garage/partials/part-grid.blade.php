<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach ($parts as $part)
        @php
            $sellQuote = ($partSellQuotes ?? collect())->get($part->id);
        @endphp
        <div
            class="bg-racing-800 rounded-lg border border-racing-600 overflow-hidden"
            x-data="{ sellOpen: false }"
            x-show="showEquipped || {{ $part->car_id === null ? 'true' : 'false' }}"
        >
            <div class="p-4 space-y-2">
                <h3 class="text-lg font-bold text-white">{{ $part->partModel->name }}</h3>
                <p class="text-gray-400 text-sm">
                    @unless ($hideSlot ?? false)
                        {{ ucfirst($part->slot->value) }}
                        ·
                    @endunless
                    {{ ucfirst($part->partModel->rarity->value) }}
                    · {{ __('Min class') }} {{ $part->partModel->min_car_class->value }}
                </p>
                <p class="text-xs">
                    <x-condition-meter :current="$part->condition_current" :max="$part->condition_max" class="text-xs" />
                </p>
                @if ($part->car_id !== null && $part->car)
                    <p class="text-accent-neon text-sm font-semibold">
                        {{ __('Equipped on') }} {{ $part->car->carModel->name }}
                    </p>
                    <a href="{{ route('garage.show', $part->car) }}" class="inline-block text-sm text-accent-blue hover:text-accent-neon">
                        {{ __('View car') }}
                    </a>
                @else
                    <p class="text-gray-500 text-sm">{{ __('In inventory') }}</p>
                    @if ($sellQuote)
                        @if ($sellQuote->sellable)
                            <button
                                type="button"
                                @click="sellOpen = true"
                                class="mt-2 px-3 py-1 text-xs rounded bg-racing-700 text-accent-orange border border-racing-600 hover:border-accent-orange"
                            >
                                {{ __('Sell') }} (${{ number_format($sellQuote->total) }})
                            </button>
                        @else
                            <button
                                type="button"
                                disabled
                                title="{{ $sellQuote->blockedReason }}"
                                class="mt-2 px-3 py-1 text-xs rounded bg-racing-700 text-gray-500 border border-racing-600 cursor-not-allowed"
                            >
                                {{ __('Sell') }}
                            </button>
                        @endif
                    @endif
                @endif
            </div>

            @if ($sellQuote?->sellable && $part->car_id === null)
                <div
                    x-show="sellOpen"
                    x-cloak
                    x-transition
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80"
                    @keydown.escape.window="sellOpen = false"
                >
                    <div
                        class="bg-racing-800 border border-racing-600 rounded-lg max-w-md w-full p-6 space-y-4"
                        @click.outside="sellOpen = false"
                    >
                        <h3 class="text-lg font-bold text-white">{{ __('Sell part') }}</h3>
                        @include('garage.partials.sell-quote-lines', ['sellQuote' => $sellQuote])
                        <div class="flex flex-wrap gap-2 justify-end pt-2">
                            <button
                                type="button"
                                @click="sellOpen = false"
                                class="px-4 py-2 text-sm rounded bg-racing-700 text-gray-300 border border-racing-600 hover:text-white"
                            >
                                {{ __('Cancel') }}
                            </button>
                            <form method="POST" action="{{ route('garage.parts.sell', $part) }}">
                                @csrf
                                @method('DELETE')
                                <x-primary-button class="!bg-accent-orange hover:!opacity-90">
                                    {{ __('Confirm sell') }}
                                </x-primary-button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endforeach
</div>
