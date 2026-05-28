<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('Rankings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-gray-500 text-sm">
                {{ __('Players ranked by reputation. PvP races do not change reputation in the MVP.') }}
            </p>

            @if ($currentUserGlobalRank !== null)
                <p class="text-gray-200 text-sm">
                    {{ __('Your rank: #:rank', ['rank' => number_format($currentUserGlobalRank)]) }}
                </p>
            @endif

            <div class="bg-racing-800 border border-racing-600 rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-racing-600">
                    <thead class="bg-racing-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('Rank') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('Player') }}</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('Level') }}</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('Reputation') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-racing-600">
                        @forelse ($profiles as $index => $profile)
                            @php
                                $rank = $rankOffset + $index + 1;
                                $isCurrentUser = $profile->user_id === $currentUserId;
                            @endphp
                            <tr class="{{ $isCurrentUser ? 'bg-racing-700/60' : '' }}">
                                <td class="px-4 py-3 text-sm text-gray-300">{{ $rank }}</td>
                                <td class="px-4 py-3 text-sm font-medium {{ $isCurrentUser ? 'text-accent-neon' : 'text-white' }}">
                                    {{ $profile->user->name }}
                                    @if ($isCurrentUser)
                                        <span class="text-gray-500 font-normal">({{ __('you') }})</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-300 text-right">{{ $profile->level }}</td>
                                <td class="px-4 py-3 text-sm text-white font-semibold text-right">{{ number_format($profile->reputation) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-400">{{ __('No players yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $profiles->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
