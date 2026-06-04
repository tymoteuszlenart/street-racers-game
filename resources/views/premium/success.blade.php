<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('Payment received') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-racing-800 border border-racing-600 rounded-lg p-6 space-y-4">
                <p class="text-green-400">
                    {{ __('Thanks! Stripe has accepted your payment.') }}
                </p>
                <p class="text-gray-400 text-sm">
                    {{ __('Your fuel or premium fuel will appear after our server confirms the payment (usually within a few seconds). Refresh your dashboard if it does not update right away.') }}
                </p>
                <a href="{{ route('premium.index') }}" class="inline-block text-accent-orange hover:underline text-sm">
                    {{ __('Back to Premium') }}
                </a>
                <a href="{{ route('dashboard') }}" class="inline-block text-accent-neon hover:underline text-sm ms-4">
                    {{ __('Dashboard') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
