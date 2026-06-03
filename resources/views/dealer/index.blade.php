<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('Dealer') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-gray-400">
                {{ __('Available cars for your level. Cash on hand:') }}
                <span class="text-white font-semibold">${{ number_format($cash) }}</span>
            </p>

            @if ($errors->has('cash') || $errors->has('car_model'))
                <div class="bg-racing-700 border border-accent-orange text-accent-orange px-4 py-3 rounded-lg space-y-1">
                    <x-input-error :messages="$errors->get('cash')" />
                    <x-input-error :messages="$errors->get('car_model')" />
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach ($carModels as $carModel)
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
                                <form method="POST" action="{{ route('dealer.purchase', $carModel) }}" class="pt-2 border-t border-racing-600">
                                    @csrf
                                    <x-primary-button>{{ __('Purchase') }}</x-primary-button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
