@props(['scoreBreakdown', 'driverStatLabels' => []])

@php
    $player = $scoreBreakdown['player'] ?? null;
    $opponent = $scoreBreakdown['opponent'] ?? null;
@endphp

@if ($player || $opponent)
    <div class="border-t border-racing-600 pt-4 space-y-4">
        <h3 class="text-sm font-medium text-gray-400 uppercase tracking-wider">{{ __('Score breakdown') }}</h3>

        <div class="grid grid-cols-1 {{ $player && $opponent ? 'lg:grid-cols-2' : '' }} gap-4">
            @if ($player)
                <x-race-score-side-breakdown
                    :title="__('You')"
                    :breakdown="$player"
                    :driver-stat-labels="$driverStatLabels"
                />
            @endif
            @if ($opponent)
                <x-race-score-side-breakdown
                    :title="__('Opponent')"
                    :breakdown="$opponent"
                    :driver-stat-labels="$driverStatLabels"
                />
            @endif
        </div>
    </div>
@endif
