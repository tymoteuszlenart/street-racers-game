<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('Tournament rewards') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($lastClosedSeason)
                <p class="text-gray-400 text-sm">
                    {{ __('Last closed season: :key', ['key' => $lastClosedSeason->season_key]) }}
                </p>
            @endif

            @if ($grants->isEmpty())
                <p class="text-gray-400">{{ __('No tournament rewards received yet.') }}</p>
            @else
                <div class="bg-racing-800 border border-racing-600 rounded-lg divide-y divide-racing-600">
                    @foreach ($grants as $grant)
                        <div class="p-4 text-gray-300 text-sm">
                            <p class="text-white font-medium">{{ $grant->tournament?->season_key }}</p>
                            <p class="text-gray-500">{{ $grant->created_at->format('M j, Y g:i A') }}</p>
                            <p class="mt-1">
                                @if (($grant->granted_payload['cash'] ?? 0) > 0)
                                    ${{ number_format($grant->granted_payload['cash']) }}
                                @endif
                                @if (($grant->granted_payload['premium_fuel'] ?? 0) > 0)
                                    · {{ $grant->granted_payload['premium_fuel'] }} {{ __('premium fuel') }}
                                @endif
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
