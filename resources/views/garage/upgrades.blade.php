<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-200 leading-tight">
                {{ __('Tune') }}: {{ $car->carModel->name }}
            </h2>
            <a href="{{ route('garage.show', $car) }}" class="text-sm text-accent-blue hover:text-accent-neon">{{ __('Back to car') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'part-equipped')
                <div class="bg-racing-700 border border-accent-green text-accent-green px-4 py-3 rounded-lg">
                    {{ __('Part equipped.') }}
                </div>
            @elseif (session('status') === 'part-unequipped')
                <div class="bg-racing-700 border border-accent-green text-accent-green px-4 py-3 rounded-lg">
                    {{ __('Part moved to inventory.') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-racing-700 border border-accent-orange text-accent-orange px-4 py-3 rounded-lg space-y-1">
                    @foreach ($errors->all() as $message)
                        <p>{{ $message }}</p>
                    @endforeach
                </div>
            @endif

            <div class="bg-racing-800 border border-racing-600 rounded-lg p-6">
                <h3 class="text-accent-orange font-semibold mb-4">{{ __('Effective stats') }}</h3>
                <x-effective-car-stats :base-stats="$baseStats" :effective-stats="$effectiveStats" />
            </div>

            <div class="bg-racing-800 border border-racing-600 rounded-lg p-6">
                <h3 class="text-accent-orange font-semibold mb-4">{{ __('Equipped slots') }}</h3>
                <div class="space-y-4">
                    @foreach ($slots as $slot)
                        @php
                            $equipped = $equippedBySlot->get($slot->value);
                        @endphp
                        <div class="border border-racing-600 rounded-lg p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <p class="text-gray-400 text-xs uppercase">{{ ucfirst($slot->value) }}</p>
                                @if ($equipped)
                                    <p class="text-white font-semibold">{{ $equipped->partModel->name }}</p>
                                    <p class="text-gray-500 text-sm">{{ ucfirst($equipped->partModel->rarity->value) }}</p>
                                @else
                                    <p class="text-gray-500">{{ __('Empty') }}</p>
                                @endif
                            </div>
                            @if ($equipped)
                                <form method="POST" action="{{ route('garage.upgrades.unequip', [$car, $equipped]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-secondary-button>{{ __('Unequip') }}</x-secondary-button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-racing-800 border border-racing-600 rounded-lg p-6">
                <h3 class="text-accent-orange font-semibold mb-4">{{ __('Inventory (compatible)') }}</h3>
                @if ($inventory->isEmpty())
                    <p class="text-gray-500">{{ __('No compatible parts in inventory. Visit the tuning shop to buy upgrades.') }}</p>
                    <a href="{{ route('shop.index', ['tab' => 'parts']) }}" class="inline-block mt-4 text-accent-blue hover:text-accent-neon text-sm">{{ __('Visit Shop') }}</a>
                @else
                    <div class="space-y-3">
                        @foreach ($inventory as $part)
                            <div class="border border-racing-600 rounded-lg p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div>
                                    <p class="text-white font-semibold">{{ $part->partModel->name }}</p>
                                    <p class="text-gray-400 text-sm">
                                        {{ ucfirst($part->slot->value) }}
                                        · {{ ucfirst($part->partModel->rarity->value) }}
                                        @if ($part->car_id !== null)
                                            · {{ __('On another car') }}
                                        @endif
                                    </p>
                                </div>
                                <form method="POST" action="{{ route('garage.upgrades.equip', [$car, $part]) }}">
                                    @csrf
                                    <x-primary-button>{{ __('Equip') }}</x-primary-button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
