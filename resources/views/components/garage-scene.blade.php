@props([
    'carModel' => null,
    'carImageClass' => 'h-44 sm:h-56 md:h-72 w-auto max-w-[82%] object-contain drop-shadow-[0_8px_24px_rgba(0,0,0,0.85)]',
])

<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-lg border border-racing-600']) }}>
    <img
        src="{{ asset('garage.png') }}"
        alt=""
        class="absolute inset-0 w-full h-full object-cover object-center"
        role="presentation"
    />
    <div class="absolute inset-0 bg-gradient-to-t from-racing-900 via-racing-900/50 to-racing-900/20 pointer-events-none"></div>

    @if ($carModel?->image_path)
        <div class="absolute inset-x-0 bottom-[4%] sm:bottom-[6%] flex justify-center items-end px-4 pointer-events-none z-10">
            <img
                src="{{ asset($carModel->image_path) }}"
                alt="{{ $carModel->name }}"
                class="{{ $carImageClass }}"
            />
        </div>
    @endif

    @if (isset($slot) && ! $slot->isEmpty())
        <div class="relative z-20 flex flex-col justify-start p-4 sm:p-6">
            {{ $slot }}
        </div>
    @endif

    <div @class([
        'relative w-full pointer-events-none',
        'aspect-[21/9] sm:aspect-[2.5/1]' => isset($slot) && ! $slot->isEmpty(),
        'aspect-[4/3] sm:aspect-[3/2]' => ! isset($slot) || $slot->isEmpty(),
    ]) aria-hidden="true"></div>
</div>
