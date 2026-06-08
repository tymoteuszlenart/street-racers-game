@props([
    'nickname',
    'cash',
    'cups',
    'fuelCurrent',
    'fuelMax',
    'premiumFuelCurrent',
    'premiumFuelMax',
    'level',
    'progress' => null,
    'percent' => 0,
    'maxLevel' => 100,
])

@php
    $percent = max(0, min(100, $percent));
    $statClass = 'flex shrink-0 items-center gap-1 border-r border-racing-600 px-2.5 text-[11px] font-semibold whitespace-nowrap';
@endphp

<div
    data-testid="player-hud"
    class="fixed top-0 inset-x-0 z-[60] flex h-6 items-stretch overflow-x-auto border-b border-racing-600 bg-racing-900 shadow-[0_2px_12px_rgba(0,0,0,0.45)] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
>
    <div class="{{ $statClass }} max-w-[9rem] min-w-0 bg-racing-800" title="{{ __('Player') }}">
        <span class="truncate text-accent-neon">{{ $nickname }}</span>
    </div>

    <div class="{{ $statClass }} text-accent-green" title="{{ __('Cash') }}">
        <span aria-hidden="true">$</span>
        <span>{{ number_format($cash) }}</span>
    </div>

    <div class="{{ $statClass }} text-accent-orange" title="{{ __('Cups') }}">
        <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M5 2a1 1 0 00-1 1v1a1 1 0 001 1h1v9a3 3 0 006 0V5h1a1 1 0 001-1V3a1 1 0 00-1-1H5zm3 13a1 1 0 102 0 1 1 0 00-2 0z" />
        </svg>
        <span>{{ number_format($cups) }}</span>
    </div>

    <div class="{{ $statClass }} text-white" title="{{ __('Fuel') }}">
        <svg class="h-3.5 w-3.5 shrink-0 text-accent-orange" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M6 3h8v2h2v2h-1.5l-1 12.5A2.5 2.5 0 0 1 11 22H9a2.5 2.5 0 0 1-2.5-2.5L5.5 7H4V5h2V3zm2 2v1h6V5H8zm-.5 4 1 11.5a.5.5 0 0 0 .5.5h2a.5.5 0 0 0 .5-.5L12.5 9h-5zM17 5h2v2h-2V5zm0 4h2v10h-2V9z" />
        </svg>
        <span>{{ $fuelCurrent }}/{{ $fuelMax }}</span>
    </div>

    <div class="{{ $statClass }} text-accent-blue" title="{{ __('Premium fuel') }}">
        <svg class="h-3.5 w-3.5 shrink-0 text-accent-neon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
        </svg>
        <span>{{ $premiumFuelCurrent }}/{{ $premiumFuelMax }}</span>
    </div>

    <div
        class="relative flex min-w-[8rem] flex-1 items-stretch bg-racing-700"
        role="progressbar"
        aria-valuenow="{{ $progress ? $progress['current'] : $maxLevel }}"
        aria-valuemin="0"
        aria-valuemax="{{ $progress ? $progress['required'] : $maxLevel }}"
        aria-label="{{ $progress
            ? __('Level :level — :current of :required XP', [
                'level' => $level,
                'current' => number_format($progress['current']),
                'required' => number_format($progress['required']),
            ])
            : __('Level :level — max level reached', ['level' => $level]) }}"
    >
        <div class="flex shrink-0 items-center justify-center border-r border-racing-600 bg-racing-800 px-2">
            <span class="text-[10px] font-bold uppercase tracking-wider text-accent-orange">
                {{ __('Lvl') }} {{ $level }}
            </span>
        </div>

        <div class="relative min-w-0 flex-1">
            <div
                class="absolute inset-y-0 left-0 bg-gradient-to-r from-accent-blue via-accent-neon to-accent-blue shadow-[inset_0_0_8px_rgba(34,211,238,0.35)] transition-[width] duration-500 ease-out"
                style="width: {{ $percent }}%"
            ></div>

            <div class="pointer-events-none absolute inset-0 flex items-center justify-center px-2">
                @if ($progress)
                    <span class="truncate text-[10px] font-semibold text-white drop-shadow-[0_1px_2px_rgba(0,0,0,0.9)]">
                        {{ number_format($progress['current']) }} / {{ number_format($progress['required']) }} {{ __('XP') }}
                    </span>
                @else
                    <span class="truncate text-[10px] font-semibold uppercase tracking-wide text-gray-400">
                        {{ __('Max level') }}
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>
