@props(['baseStats', 'effectiveStats'])

<dl class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
    @foreach (['power' => 'Power', 'acceleration' => 'Acceleration', 'grip' => 'Grip', 'handling' => 'Handling'] as $key => $label)
        @php
            $base = $baseStats[$key];
            $effective = $effectiveStats[$key];
            $delta = $effective - $base;
        @endphp
        <div class="bg-racing-700 rounded p-3 border border-racing-600">
            <dt class="text-gray-400">{{ $label }}</dt>
            <dd class="text-white font-semibold">
                {{ $effective }}
                @if ($delta > 0)
                    <span class="text-accent-green text-xs font-normal">(+{{ $delta }})</span>
                @elseif ($delta < 0)
                    <span class="text-accent-orange text-xs font-normal">({{ $delta }})</span>
                @endif
            </dd>
        </div>
    @endforeach
</dl>

@if (($effectiveStats['level_penalty_percent'] ?? 0) > 0)
    <p class="mt-3 text-accent-orange text-sm">
        {{ __('Overlevel penalty: -:penalty% effective stats until you reach this car model level.', ['penalty' => $effectiveStats['level_penalty_percent']]) }}
    </p>
@endif
