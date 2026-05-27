<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-racing-800 overflow-hidden shadow-sm sm:rounded-lg border border-racing-600">
                <div class="p-6 text-gray-200">
                    <h3 class="text-2xl font-bold text-accent-neon mb-4">Welcome to Street Racers!</h3>
                    <p class="text-gray-400 mb-6">Your street racing career starts here. Get behind the wheel and dominate the streets.</p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-racing-700 rounded-lg p-6 border border-racing-600">
                            <h4 class="text-accent-orange font-semibold text-lg mb-2">Cash</h4>
                            <p class="text-3xl font-bold text-white">${{ number_format(Auth::user()->playerProfile->cash ?? 0) }}</p>
                        </div>
                        <div class="bg-racing-700 rounded-lg p-6 border border-racing-600">
                            <h4 class="text-accent-blue font-semibold text-lg mb-2">Level</h4>
                            <p class="text-3xl font-bold text-white">{{ Auth::user()->playerProfile->level ?? 1 }}</p>
                        </div>
                        <div class="bg-racing-700 rounded-lg p-6 border border-racing-600">
                            <h4 class="text-accent-green font-semibold text-lg mb-2">Reputation</h4>
                            <p class="text-3xl font-bold text-white">{{ Auth::user()->playerProfile->reputation ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
