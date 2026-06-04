<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-200 leading-tight">
                {{ __('Purchase History') }}
            </h2>
            <a href="{{ route('premium.index') }}" class="text-accent-orange hover:underline text-sm">
                {{ __('Back to Premium') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-gray-500 text-sm">
                {{ __('Your Stripe shop orders. Rewards appear after payment is confirmed.') }}
            </p>

            <div class="space-y-4">
                @forelse ($orders as $order)
                    <div class="bg-racing-800 border border-racing-600 rounded-lg p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div>
                                <h3 class="text-lg font-bold text-white">
                                    {{ $order->shopProduct?->name ?? __('Unknown product') }}
                                </h3>
                                <p class="text-gray-400 text-sm">
                                    {{ $order->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                </p>
                            </div>
                            <div class="text-right space-y-1">
                                <p class="text-white font-semibold">
                                    ${{ number_format($order->amount_cents / 100, 2) }}
                                </p>
                                <p class="text-sm capitalize text-gray-400">
                                    {{ str_replace('_', ' ', $order->status->value) }}
                                </p>
                                @if ($summary = $order->grantedSummary())
                                    <p class="text-sm text-accent-neon">{{ $summary }}</p>
                                @elseif ($order->isFulfilled())
                                    <p class="text-sm text-gray-500">{{ __('Fulfilled') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-400">{{ __('No purchases yet.') }}</p>
                @endforelse
            </div>

            <div>
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
