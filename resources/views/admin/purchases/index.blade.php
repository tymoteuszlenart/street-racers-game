<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('Admin — Purchases') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-gray-500 text-sm">
                {{ __('Read-only view of all payment orders for support.') }}
            </p>

            <div class="overflow-x-auto bg-racing-800 border border-racing-600 rounded-lg">
                <table class="min-w-full text-sm text-left text-gray-300">
                    <thead class="text-xs uppercase text-gray-500 border-b border-racing-600">
                        <tr>
                            <th class="px-4 py-3">{{ __('Date') }}</th>
                            <th class="px-4 py-3">{{ __('User') }}</th>
                            <th class="px-4 py-3">{{ __('Product') }}</th>
                            <th class="px-4 py-3">{{ __('Amount') }}</th>
                            <th class="px-4 py-3">{{ __('Status') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr class="border-b border-racing-600 last:border-0">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ $order->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-white">{{ $order->user?->name }}</span>
                                    <span class="block text-gray-500 text-xs">{{ $order->user?->email }}</span>
                                </td>
                                <td class="px-4 py-3">{{ $order->shopProduct?->name ?? '—' }}</td>
                                <td class="px-4 py-3">${{ number_format($order->amount_cents / 100, 2) }}</td>
                                <td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $order->status->value) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.purchases.show', $order) }}" class="text-accent-orange hover:underline">
                                        {{ __('View') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-gray-400">{{ __('No orders yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
