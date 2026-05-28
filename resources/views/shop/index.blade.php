<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('Shop') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="bg-racing-700 border border-accent-orange text-accent-orange px-4 py-3 rounded-lg space-y-1">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="bg-racing-800 border border-racing-600 rounded-lg p-6">
                <p class="text-gray-400 text-sm mb-6">
                    {{ __('Buy fuel packs with Stripe Checkout. Rewards are applied after payment is confirmed by our server — not when you land on the success page.') }}
                </p>

                @if ($profile !== null)
                    <dl class="grid grid-cols-2 gap-4 text-gray-300 mb-6">
                        <div>
                            <dt class="text-gray-500 text-sm">{{ __('Regular fuel') }}</dt>
                            <dd class="text-white font-semibold">{{ $profile->fuel_current }} / {{ $profile->fuel_max }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 text-sm">{{ __('Premium fuel') }}</dt>
                            <dd class="text-white font-semibold">{{ $profile->premium_fuel_current }} / {{ min($profile->premium_fuel_max, $paidPremiumMax) }}</dd>
                        </div>
                    </dl>
                @endif

                <div class="space-y-4">
                    @foreach ($products as $product)
                        @php
                            $isPremium = $product->type === \App\Enums\ShopProductType::PremiumFuel;
                            $blocked = $isPremium && $premiumAtCap;
                        @endphp
                        <div class="bg-racing-700 border border-racing-600 rounded-lg p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-accent-neon">{{ $product->name }}</h3>
                                <p class="text-gray-400 text-sm mt-1">{{ $product->description }}</p>
                                <p class="text-white font-semibold mt-2">
                                    ${{ number_format($product->price_cents / 100, 2) }}
                                </p>
                            </div>
                            <div class="shrink-0">
                                @if ($blocked)
                                    <p class="text-accent-orange text-sm mb-2">{{ __('Premium storage full') }}</p>
                                    <x-primary-button disabled>
                                        {{ __('Buy') }}
                                    </x-primary-button>
                                @else
                                    <form method="POST" action="{{ route('shop.checkout', $product) }}">
                                        @csrf
                                        <x-primary-button>
                                            {{ __('Buy with Stripe') }}
                                        </x-primary-button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <a href="{{ route('dashboard') }}" class="inline-block mt-6 text-accent-orange hover:underline text-sm">
                    {{ __('Back to dashboard') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
