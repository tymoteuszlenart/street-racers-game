@props(['title', 'breakdown', 'driverStatLabels' => []])

@php
    $driverStats = $breakdown['driver_stats'] ?? [];
    $driverBonus = $breakdown['driver_bonus'] ?? $breakdown['driver_level_bonus'] ?? null;
@endphp

<div class="bg-racing-700 rounded-lg p-4 border border-racing-600 space-y-3 text-sm">
    <h4 class="text-gray-300 font-semibold">{{ $title }}</h4>

    <dl class="space-y-2 text-gray-300">
        @isset($breakdown['base'])
            <div class="flex justify-between gap-4">
                <dt class="text-gray-500">{{ __('Car base') }}</dt>
                <dd class="text-white font-medium">{{ $breakdown['base'] }}</dd>
            </div>
        @endisset
        @if ($driverBonus !== null)
            <div class="flex justify-between gap-4">
                <dt class="text-gray-500">{{ __('Driver bonus') }}</dt>
                <dd class="text-accent-green font-medium">+{{ $driverBonus }}</dd>
            </div>
        @endif
        @isset($breakdown['random_adjustment'])
            <div class="flex justify-between gap-4">
                <dt class="text-gray-500">{{ __('Luck') }}</dt>
                <dd class="text-white font-medium">
                    {{ ($breakdown['random_adjustment'] ?? 0) >= 0 ? '+' : '' }}{{ $breakdown['random_adjustment'] }}
                </dd>
            </div>
        @endisset
        @isset($breakdown['condition_penalty'])
            @if (($breakdown['condition_penalty'] ?? 0) > 0)
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">{{ __('Condition penalty') }}</dt>
                    <dd class="text-red-400 font-medium">−{{ $breakdown['condition_penalty'] }}</dd>
                </div>
            @endif
        @endisset
    </dl>

    @if (! empty($driverStats))
        <div class="border-t border-racing-600 pt-3">
            <p class="text-gray-500 text-xs uppercase tracking-wider mb-2">{{ __('Driver stats used') }}</p>
            <dl class="grid grid-cols-2 gap-2">
                @foreach (['power', 'acceleration', 'grip', 'handling'] as $key)
                    @if (isset($driverStats[$key]))
                        <div>
                            <dt class="text-gray-500 text-xs">{{ $driverStatLabels[$key] ?? ucfirst($key) }}</dt>
                            <dd class="text-white font-semibold">{{ $driverStats[$key] }}</dd>
                        </div>
                    @endif
                @endforeach
            </dl>
        </div>
    @endif
</div>
