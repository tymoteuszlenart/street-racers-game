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

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach ($carModels as $carModel)
                    <div class="bg-racing-800 border border-racing-600 rounded-lg p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-car-image :car-model="$carModel" class="h-36 w-full object-contain" />
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <h3 class="text-xl font-bold text-white">{{ $carModel->name }}</h3>
                                    <p class="text-gray-400 text-sm">Class {{ $carModel->class->value }} · Lvl {{ $carModel->unlock_level }}+ · ${{ number_format($carModel->price) }}</p>
                                    @if ($carModel->starter)
                                        <p class="text-accent-neon text-xs mt-1">{{ __('Starter model — assigned on registration') }}</p>
                                    @endif
                                </div>
                                <x-car-stats :car-model="$carModel" />
                                @if (! $carModel->starter)
                                    <form method="POST" action="{{ route('dealer.purchase', $carModel) }}" class="space-y-3 pt-2 border-t border-racing-600">
                                        @csrf
                                        <div>
                                            <x-input-label for="nickname-{{ $carModel->id }}" :value="__('Nickname')" />
                                            <x-text-input id="nickname-{{ $carModel->id }}" name="nickname" type="text" class="mt-1 block w-full" required maxlength="64" placeholder="{{ __('My street machine') }}" />
                                            <x-input-error :messages="$errors->get('nickname')" class="mt-2" />
                                            <x-input-error :messages="$errors->get('cash')" class="mt-2" />
                                            <x-input-error :messages="$errors->get('car_model')" class="mt-2" />
                                        </div>
                                        <x-primary-button>{{ __('Purchase') }}</x-primary-button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
