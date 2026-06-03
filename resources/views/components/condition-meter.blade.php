<span {{ $attributes->merge(['class' => '']) }} style="color: {{ $textColor }}">
    {{ __('Condition') }}: {{ $current }}/{{ $max }}
    <span class="text-gray-500">({{ number_format($percent, 0) }}%)</span>
</span>
