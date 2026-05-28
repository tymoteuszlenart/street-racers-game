<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('Tuning Shop') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-gray-400">
                {{ __('Upgrade parts for your cars. Cash on hand:') }}
                <span class="text-white font-semibold">${{ number_format($cash) }}</span>
                · {{ __('Level') }} {{ $playerLevel }}
            </p>

            @if (session('status') === 'part-purchased')
                <div class="bg-racing-700 border border-accent-green text-accent-green px-4 py-3 rounded-lg">
                    {{ __('Part purchased and added to your inventory.') }}
                </div>
            @endif

            @if ($errors->has('cash') || $errors->has('part_model') || $errors->has('tuning'))
                <div class="bg-racing-700 border border-accent-orange text-accent-orange px-4 py-3 rounded-lg space-y-1">
                    <x-input-error :messages="$errors->get('cash')" />
                    <x-input-error :messages="$errors->get('part_model')" />
                    <x-input-error :messages="$errors->get('tuning')" />
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach ($partModels as $partModel)
                    <div class="bg-racing-800 border border-racing-600 rounded-lg p-6">
                        <div class="space-y-3">
                            <div>
                                <h3 class="text-xl font-bold text-white">{{ $partModel->name }}</h3>
                                <p class="text-gray-400 text-sm">
                                    {{ ucfirst($partModel->slot->value) }}
                                    · {{ ucfirst($partModel->rarity->value) }}
                                    · Lvl {{ $partModel->unlock_level }}+
                                    · Class {{ $partModel->min_car_class->value }}+
                                    · ${{ number_format($partModel->price) }}
                                </p>
                            </div>
                            <dl class="grid grid-cols-2 gap-2 text-sm">
                                @if ($partModel->power_bonus > 0)
                                    <div><dt class="text-gray-500">Power</dt><dd class="text-accent-green">+{{ $partModel->power_bonus }}</dd></div>
                                @endif
                                @if ($partModel->acceleration_bonus > 0)
                                    <div><dt class="text-gray-500">Acceleration</dt><dd class="text-accent-green">+{{ $partModel->acceleration_bonus }}</dd></div>
                                @endif
                                @if ($partModel->grip_bonus > 0)
                                    <div><dt class="text-gray-500">Grip</dt><dd class="text-accent-green">+{{ $partModel->grip_bonus }}</dd></div>
                                @endif
                                @if ($partModel->handling_bonus > 0)
                                    <div><dt class="text-gray-500">Handling</dt><dd class="text-accent-green">+{{ $partModel->handling_bonus }}</dd></div>
                                @endif
                            </dl>
                            <form method="POST" action="{{ route('tuning.purchase', $partModel) }}" class="pt-2 border-t border-racing-600">
                                @csrf
                                <x-primary-button>{{ __('Purchase') }}</x-primary-button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
