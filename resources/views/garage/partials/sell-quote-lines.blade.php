<ul class="space-y-2 text-sm">
    @foreach ($sellQuote->lines as $line)
        <li class="flex justify-between gap-4 text-gray-300">
            <span>
                {{ $line->label }}
                <span class="text-gray-500">
                    ({{ number_format($line->conditionPercent, 0) }}% · {{ $line->refundPercent }}%)
                </span>
            </span>
            <span class="text-white font-semibold shrink-0">${{ number_format($line->refund) }}</span>
        </li>
    @endforeach
    <li class="flex justify-between gap-4 pt-2 border-t border-racing-600 text-white font-bold">
        <span>{{ __('Total') }}</span>
        <span class="text-accent-neon">${{ number_format($sellQuote->total) }}</span>
    </li>
</ul>
