<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('Checkout cancelled') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-racing-800 border border-racing-600 rounded-lg p-6 space-y-4">
                <p class="text-gray-300">
                    {{ __('You left Stripe Checkout without completing payment. No charge was made.') }}
                </p>
                <a href="{{ route('premium.index') }}" class="inline-block text-accent-orange hover:underline text-sm">
                    {{ __('Return to Premium') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
