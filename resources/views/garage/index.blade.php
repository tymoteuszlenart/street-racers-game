<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('Garage') }}
        </h2>
    </x-slot>

    <div
        class="py-12"
        x-data="{
            mainTab: window.location.hash === '#parts' ? 'parts' : 'cars',
            carClass: 'all',
            partSlot: 'all',
            showEquipped: false,
        }"
    >
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @php
                $activeCar = $activeCarId ? $cars->firstWhere('id', $activeCarId) : null;
            @endphp

            @if (session('status') === 'car-sold')
                <div class="bg-racing-700 border border-accent-green text-accent-green px-4 py-3 rounded-lg">
                    @if ((int) session('sold_part_count') > 0)
                        {{ __('Sold car and :count parts for $:amount.', ['count' => session('sold_part_count'), 'amount' => number_format(session('sold_amount'))]) }}
                    @else
                        {{ __('Sold car for $:amount.', ['amount' => number_format(session('sold_amount'))]) }}
                    @endif
                </div>
            @elseif (session('status') === 'part-sold')
                <div class="bg-racing-700 border border-accent-green text-accent-green px-4 py-3 rounded-lg">
                    {{ __('Sold :name for $:amount.', ['name' => session('sold_label'), 'amount' => number_format(session('sold_amount'))]) }}
                </div>
            @endif

            <x-garage-scene :car-model="$activeCar?->carModel">
                <div class="flex flex-wrap gap-4 justify-between items-start">
                    <div>
                        <p class="text-gray-300 text-sm sm:text-base max-w-xl">
                            {{ __('Your cars and parts inventory. The active car is used for races.') }}
                        </p>
                        @if ($activeCar)
                            <p class="mt-2 text-white font-semibold">
                                {{ __('Active') }}: {{ $activeCar->carModel->name }}
                            </p>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2 shrink-0">
                        <a href="{{ route('shop.index') }}" class="inline-flex items-center px-4 py-2 bg-accent-orange text-white rounded-md font-semibold text-sm hover:opacity-90">
                            {{ __('Visit Shop') }}
                        </a>
                        @if ($playerLevel >= config('game.mechanic.unlock_level', 5))
                            <a href="{{ route('mechanic.index') }}" class="inline-flex items-center px-4 py-2 bg-racing-700 text-accent-neon border border-racing-600 rounded-md font-semibold text-sm hover:border-accent-neon">
                                {{ __('Mechanic') }}
                            </a>
                        @endif
                    </div>
                </div>
            </x-garage-scene>

            <div class="border-b border-racing-600">
                <nav class="flex gap-1" aria-label="{{ __('Garage sections') }}">
                    <button
                        type="button"
                        @click="mainTab = 'cars'"
                        :class="mainTab === 'cars' ? 'border-accent-neon text-accent-neon' : 'border-transparent text-gray-400 hover:text-gray-200'"
                        class="px-4 py-2 text-sm font-semibold border-b-2 transition"
                    >
                        {{ __('Cars') }}
                        <span class="text-gray-500 font-normal">({{ $cars->count() }})</span>
                    </button>
                    <button
                        type="button"
                        @click="mainTab = 'parts'"
                        :class="mainTab === 'parts' ? 'border-accent-neon text-accent-neon' : 'border-transparent text-gray-400 hover:text-gray-200'"
                        class="px-4 py-2 text-sm font-semibold border-b-2 transition"
                    >
                        {{ __('Parts') }}
                        <span class="text-gray-500 font-normal">(<span x-text="showEquipped ? {{ $parts->count() }} : {{ $inventoryParts->count() }}"></span>)</span>
                    </button>
                </nav>
            </div>

            {{-- Cars --}}
            <div x-show="mainTab === 'cars'" x-cloak>
                <div class="flex flex-wrap gap-2 mb-6">
                    <button
                        type="button"
                        @click="carClass = 'all'"
                        :class="carClass === 'all' ? 'bg-accent-neon text-racing-900' : 'bg-racing-800 text-gray-300 border border-racing-600 hover:border-accent-blue'"
                        class="px-3 py-1 rounded-md text-xs font-bold uppercase tracking-wide transition"
                    >
                        {{ __('All') }}
                    </button>
                    @foreach ($carClasses as $class)
                        @php $classCount = $carsByClass->get($class->value, collect())->count(); @endphp
                        <button
                            type="button"
                            @click="carClass = '{{ $class->value }}'"
                            :class="carClass === '{{ $class->value }}' ? 'bg-accent-neon text-racing-900' : 'bg-racing-800 text-gray-300 border border-racing-600 hover:border-accent-blue'"
                            class="px-3 py-1 rounded-md text-xs font-bold uppercase tracking-wide transition"
                        >
                            {{ $class->value }}
                            <span class="font-normal opacity-80">({{ $classCount }})</span>
                        </button>
                    @endforeach
                </div>

                <div x-show="carClass === 'all'">
                    @if ($cars->isEmpty())
                        <div class="bg-racing-800 border border-racing-600 rounded-lg p-8 text-center text-gray-400">
                            {{ __('No cars in your garage yet.') }}
                            <a href="{{ route('shop.index') }}" class="block mt-4 text-accent-blue hover:text-accent-neon">{{ __('Visit Shop') }}</a>
                        </div>
                    @else
                        @include('garage.partials.car-grid', ['cars' => $cars, 'activeCarId' => $activeCarId])
                    @endif
                </div>

                @foreach ($carClasses as $class)
                    @php $classCars = $carsByClass->get($class->value, collect()); @endphp
                    <div x-show="carClass === '{{ $class->value }}'" x-cloak>
                        @if ($classCars->isEmpty())
                            <div class="bg-racing-800 border border-racing-600 rounded-lg p-8 text-center text-gray-400">
                                {{ __('No Class :class cars yet.', ['class' => $class->value]) }}
                            </div>
                        @else
                            @include('garage.partials.car-grid', ['cars' => $classCars, 'activeCarId' => $activeCarId])
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Parts --}}
            <div x-show="mainTab === 'parts'" x-cloak>
                <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input
                            type="checkbox"
                            x-model="showEquipped"
                            class="rounded border-racing-600 bg-racing-800 text-accent-neon focus:ring-accent-neon focus:ring-offset-racing-900"
                        />
                        <span class="text-sm text-gray-300">{{ __('Show equipped parts') }}</span>
                    </label>
                    @if ($parts->isNotEmpty() && $inventoryParts->count() < $parts->count())
                        <p class="text-xs text-gray-500">
                            {{ __(':equipped equipped · :spare spare', [
                                'equipped' => $parts->count() - $inventoryParts->count(),
                                'spare' => $inventoryParts->count(),
                            ]) }}
                        </p>
                    @endif
                </div>

                <div class="flex flex-wrap gap-2 mb-6">
                    <button
                        type="button"
                        @click="partSlot = 'all'"
                        :class="partSlot === 'all' ? 'bg-accent-neon text-racing-900' : 'bg-racing-800 text-gray-300 border border-racing-600 hover:border-accent-blue'"
                        class="px-3 py-1 rounded-md text-xs font-bold uppercase tracking-wide transition"
                    >
                        {{ __('All') }}
                        <span class="font-normal opacity-80">(<span x-text="showEquipped ? {{ $parts->count() }} : {{ $inventoryParts->count() }}"></span>)</span>
                    </button>
                    @foreach ($partSlots as $slot)
                        @php
                            $slotCount = $partsBySlot->get($slot->value, collect())->count();
                            $slotInventoryCount = $inventoryPartsBySlot->get($slot->value, collect())->count();
                        @endphp
                        <button
                            type="button"
                            @click="partSlot = '{{ $slot->value }}'"
                            :class="partSlot === '{{ $slot->value }}' ? 'bg-accent-neon text-racing-900' : 'bg-racing-800 text-gray-300 border border-racing-600 hover:border-accent-blue'"
                            class="px-3 py-1 rounded-md text-xs font-bold capitalize tracking-wide transition"
                        >
                            {{ $slot->value }}
                            <span class="font-normal opacity-80">(<span x-text="showEquipped ? {{ $slotCount }} : {{ $slotInventoryCount }}"></span>)</span>
                        </button>
                    @endforeach
                </div>

                @if ($parts->isEmpty())
                    <div class="bg-racing-800 border border-racing-600 rounded-lg p-8 text-center text-gray-400">
                        {{ __('No parts in your inventory yet.') }}
                        <a href="{{ route('shop.index', ['tab' => 'parts']) }}" class="block mt-4 text-accent-blue hover:text-accent-neon">{{ __('Visit Shop — Parts') }}</a>
                    </div>
                @else
                    <div x-show="partSlot === 'all'">
                        <div
                            x-show="!showEquipped && {{ $inventoryParts->isEmpty() ? 'true' : 'false' }}"
                            class="bg-racing-800 border border-racing-600 rounded-lg p-8 text-center text-gray-400"
                        >
                            @if ($inventoryParts->isEmpty())
                                {{ __('All your parts are equipped on cars.') }}
                                <button type="button" @click="showEquipped = true" class="block mt-4 mx-auto text-accent-blue hover:text-accent-neon text-sm">
                                    {{ __('Show equipped parts') }}
                                </button>
                            @endif
                        </div>
                        <div x-show="showEquipped || {{ $inventoryParts->isNotEmpty() ? 'true' : 'false' }}">
                            @include('garage.partials.part-grid', ['parts' => $parts])
                        </div>
                    </div>

                    @foreach ($partSlots as $slot)
                        @php
                            $slotParts = $partsBySlot->get($slot->value, collect());
                            $slotInventory = $inventoryPartsBySlot->get($slot->value, collect());
                        @endphp
                        <div x-show="partSlot === '{{ $slot->value }}'" x-cloak>
                            <div
                                x-show="!showEquipped && {{ $slotInventory->isEmpty() ? 'true' : 'false' }}"
                                class="bg-racing-800 border border-racing-600 rounded-lg p-8 text-center text-gray-400"
                            >
                                @if ($slotParts->isEmpty())
                                    {{ __('No :slot parts yet.', ['slot' => $slot->value]) }}
                                @else
                                    {{ __('No spare :slot parts. All are equipped on cars.', ['slot' => $slot->value]) }}
                                    <button type="button" @click="showEquipped = true" class="block mt-4 mx-auto text-accent-blue hover:text-accent-neon text-sm">
                                        {{ __('Show equipped parts') }}
                                    </button>
                                @endif
                            </div>
                            <div x-show="showEquipped || {{ $slotInventory->isNotEmpty() ? 'true' : 'false' }}">
                                @include('garage.partials.part-grid', ['parts' => $slotParts, 'hideSlot' => true])
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
