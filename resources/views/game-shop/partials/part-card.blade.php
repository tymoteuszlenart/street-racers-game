@props(['partModel'])

<div class="bg-racing-800 border border-racing-600 rounded-lg p-6">
    <div class="space-y-3">
        <div>
            <h3 class="text-xl font-bold text-white">{{ $partModel->name }}</h3>
            <p class="text-gray-400 text-sm">
                {{ ucfirst($partModel->rarity->value) }}
                · Lvl {{ $partModel->unlock_level }}
                · Class {{ $partModel->min_car_class->value }}
                · ${{ number_format($partModel->price) }}
            </p>
        </div>
        <dl class="grid grid-cols-2 gap-2 text-sm">
            @if ($partModel->power_bonus > 0)
                <div><dt class="text-gray-500">Power</dt><dd class="text-accent-green">{{ $partModel->power_bonus }}</dd></div>
            @endif
            @if ($partModel->acceleration_bonus > 0)
                <div><dt class="text-gray-500">Acceleration</dt><dd class="text-accent-green">{{ $partModel->acceleration_bonus }}</dd></div>
            @endif
            @if ($partModel->grip_bonus > 0)
                <div><dt class="text-gray-500">Grip</dt><dd class="text-accent-green">{{ $partModel->grip_bonus }}</dd></div>
            @endif
            @if ($partModel->handling_bonus > 0)
                <div><dt class="text-gray-500">Handling</dt><dd class="text-accent-green">{{ $partModel->handling_bonus }}</dd></div>
            @endif
        </dl>
        <form method="POST" action="{{ route('shop.parts.purchase', $partModel) }}" class="pt-2 border-t border-racing-600">
            @csrf
            <x-primary-button>{{ __('Purchase') }}</x-primary-button>
        </form>
    </div>
</div>
