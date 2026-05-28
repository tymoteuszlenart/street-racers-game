<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-200 leading-tight">
                {{ __('Admin — Order #:id', ['id' => $order->id]) }}
            </h2>
            <a href="{{ route('admin.purchases.index') }}" class="text-accent-orange hover:underline text-sm">
                {{ __('All purchases') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <dl class="bg-racing-800 border border-racing-600 rounded-lg divide-y divide-racing-600 text-sm">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 px-6 py-4">
                    <dt class="text-gray-500">{{ __('Order UUID') }}</dt>
                    <dd class="sm:col-span-2 text-white font-mono text-xs break-all">{{ $order->uuid }}</dd>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 px-6 py-4">
                    <dt class="text-gray-500">{{ __('User') }}</dt>
                    <dd class="sm:col-span-2 text-white">
                        {{ $order->user?->name }} ({{ $order->user?->email }})
                    </dd>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 px-6 py-4">
                    <dt class="text-gray-500">{{ __('Product') }}</dt>
                    <dd class="sm:col-span-2 text-white">{{ $order->shopProduct?->name ?? '—' }}</dd>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 px-6 py-4">
                    <dt class="text-gray-500">{{ __('Amount') }}</dt>
                    <dd class="sm:col-span-2 text-white">${{ number_format($order->amount_cents / 100, 2) }}</dd>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 px-6 py-4">
                    <dt class="text-gray-500">{{ __('Status') }}</dt>
                    <dd class="sm:col-span-2 text-white capitalize">{{ str_replace('_', ' ', $order->status->value) }}</dd>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 px-6 py-4">
                    <dt class="text-gray-500">{{ __('Checkout session') }}</dt>
                    <dd class="sm:col-span-2 text-white font-mono text-xs break-all">{{ $order->provider_checkout_session_id ?? '—' }}</dd>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 px-6 py-4">
                    <dt class="text-gray-500">{{ __('Payment intent') }}</dt>
                    <dd class="sm:col-span-2 text-white font-mono text-xs break-all">{{ $order->provider_payment_intent_id ?? '—' }}</dd>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 px-6 py-4">
                    <dt class="text-gray-500">{{ __('Stripe event') }}</dt>
                    <dd class="sm:col-span-2 text-white font-mono text-xs break-all">{{ $order->provider_event_id ?? '—' }}</dd>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 px-6 py-4">
                    <dt class="text-gray-500">{{ __('Granted') }}</dt>
                    <dd class="sm:col-span-2 text-accent-neon">{{ $order->grantedSummary() ?? '—' }}</dd>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 px-6 py-4">
                    <dt class="text-gray-500">{{ __('Created') }}</dt>
                    <dd class="sm:col-span-2 text-white">
                        {{ $order->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A T') }}
                    </dd>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 px-6 py-4">
                    <dt class="text-gray-500">{{ __('Fulfilled') }}</dt>
                    <dd class="sm:col-span-2 text-white">
                        @if ($order->fulfilled_at)
                            {{ $order->fulfilled_at->timezone(config('app.timezone'))->format('M j, Y g:i A T') }}
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 px-6 py-4">
                    <dt class="text-gray-500">{{ __('Updated') }}</dt>
                    <dd class="sm:col-span-2 text-white">
                        {{ $order->updated_at->timezone(config('app.timezone'))->format('M j, Y g:i A T') }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</x-app-layout>
