<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('Shop') }}
        </h2>
    </x-slot>

    <div
        class="py-12"
        x-data="{
            tab: @js($initialTab),
        }"
    >
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-gray-400">
                {{ __('Buy cars and parts with cash. Cash on hand:') }}
                <span class="text-white font-semibold">${{ number_format($cash) }}</span>
                · {{ __('Level') }} {{ $playerLevel }}
            </p>

            @if (session('status') === 'part-purchased')
                <div class="bg-racing-700 border border-accent-green text-accent-green px-4 py-3 rounded-lg">
                    {{ __('Part purchased and added to your inventory.') }}
                </div>
            @endif

            @if ($errors->has('cash') || $errors->has('car_model') || $errors->has('part_model') || $errors->has('tuning'))
                <div class="bg-racing-700 border border-accent-orange text-accent-orange px-4 py-3 rounded-lg space-y-1">
                    <x-input-error :messages="$errors->get('cash')" />
                    <x-input-error :messages="$errors->get('car_model')" />
                    <x-input-error :messages="$errors->get('part_model')" />
                    <x-input-error :messages="$errors->get('tuning')" />
                </div>
            @endif

            <div class="border-b border-racing-600 overflow-x-auto">
                <nav class="flex gap-1 min-w-max" aria-label="{{ __('Shop sections') }}">
                    <button
                        type="button"
                        @click="tab = 'cars'"
                        :class="tab === 'cars' ? 'border-accent-neon text-accent-neon' : 'border-transparent text-gray-400 hover:text-gray-200'"
                        class="px-4 py-2 text-sm font-semibold border-b-2 transition whitespace-nowrap"
                    >
                        {{ __('Cars') }}
                    </button>
                    @foreach ($partSlots as $slot)
                        @php
                            $slotUnlockLevel = $slotUnlockLevels[$slot->value] ?? 1;
                            $slotUnlocked = $partsUnlocked && $playerLevel >= $slotUnlockLevel;
                        @endphp
                        <button
                            type="button"
                            @click="tab = '{{ $slot->value }}'"
                            :class="tab === '{{ $slot->value }}' ? 'border-accent-neon text-accent-neon' : 'border-transparent text-gray-400 hover:text-gray-200'"
                            class="px-4 py-2 text-sm font-semibold border-b-2 transition capitalize whitespace-nowrap"
                            @if (! $slotUnlocked) title="{{ __('Reach level :level to buy :slot parts', ['level' => $slotUnlockLevel, 'slot' => $slot->value]) }}" @endif
                        >
                            {{ $slot->value }}
                            @if (! $slotUnlocked)
                                <span class="text-gray-500 font-normal normal-case">({{ __('Lvl :level', ['level' => $slotUnlockLevel]) }})</span>
                            @endif
                        </button>
                    @endforeach
                </nav>
            </div>

            <div x-show="tab === 'cars'" x-cloak>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @forelse ($carModels as $carModel)
                        @php
                            $overlevelGap = max(0, $carModel->unlock_level - $playerLevel);
                        @endphp
                        <div class="bg-racing-800 border border-racing-600 rounded-lg p-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <x-car-image :car-model="$carModel" class="h-36 w-full object-contain" />
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <h3 class="text-xl font-bold text-white">{{ $carModel->name }}</h3>
                                        <p class="text-gray-400 text-sm">Class {{ $carModel->class->value }} · Lvl {{ $carModel->unlock_level }} · ${{ number_format($carModel->price) }}</p>
                                        @if ($overlevelGap > 0)
                                            <p class="text-accent-orange text-xs mt-1">
                                                {{ __('Overlevel purchase: -:penalty% effective stats until level :level.', ['penalty' => $overlevelGap * 10, 'level' => $carModel->unlock_level]) }}
                                            </p>
                                        @endif
                                    </div>
                                    <x-car-stats :car-model="$carModel" />
                                    <form method="POST" action="{{ route('shop.cars.purchase', $carModel) }}" class="pt-2 border-t border-racing-600">
                                        @csrf
                                        <x-primary-button>{{ __('Purchase') }}</x-primary-button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 col-span-full">{{ __('No cars available for your level yet.') }}</p>
                    @endforelse
                </div>
            </div>

            @foreach ($partSlots as $slot)
                @php
                    $slotUnlockLevel = $slotUnlockLevels[$slot->value] ?? 1;
                    $slotUnlocked = $partsUnlocked && $playerLevel >= $slotUnlockLevel;
                @endphp
                <div x-show="tab === '{{ $slot->value }}'" x-cloak>
                    @if (! $partsUnlocked)
                        <p class="text-gray-500">{{ __('Reach level :level to buy parts.', ['level' => $partsUnlockLevel]) }}</p>
                    @elseif (! $slotUnlocked)
                        <p class="text-gray-500">{{ __('Reach level :level to buy :slot parts.', ['level' => $slotUnlockLevel, 'slot' => $slot->value]) }}</p>
                    @else
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            @forelse ($partModelsBySlot->get($slot->value, collect()) as $partModel)
                                @include('game-shop.partials.part-card', ['partModel' => $partModel])
                            @empty
                                <p class="text-gray-500 col-span-full">{{ __('No :slot parts available for your level yet.', ['slot' => $slot->value]) }}</p>
                            @endforelse
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
