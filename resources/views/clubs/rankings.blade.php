<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('Club Rankings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <p class="text-gray-500 text-sm">
                    {{ __('Clubs ranked by club points.') }}
                </p>
                <a href="{{ route('clubs.index') }}" class="text-sm text-accent-blue hover:text-accent-neon">
                    {{ __('Browse clubs') }}
                </a>
            </div>

            <div class="bg-racing-800 border border-racing-600 rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-racing-600">
                    <thead class="bg-racing-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('Rank') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('Club') }}</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('Members') }}</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('Points') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-racing-600">
                        @forelse ($clubs as $index => $club)
                            @php
                                $rank = $rankOffset + $index + 1;
                                $isUserClub = $club->id === $userClubId;
                            @endphp
                            <tr class="{{ $isUserClub ? 'bg-racing-700/60' : '' }}">
                                <td class="px-4 py-3 text-sm text-gray-300">{{ $rank }}</td>
                                <td class="px-4 py-3 text-sm font-medium {{ $isUserClub ? 'text-accent-neon' : 'text-white' }}">
                                    <a href="{{ route('clubs.show', $club) }}" class="hover:text-accent-neon">
                                        {{ $club->name }}
                                    </a>
                                    @if ($isUserClub)
                                        <span class="text-gray-500 font-normal">({{ __('your club') }})</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-300 text-right">{{ $club->members_count }}</td>
                                <td class="px-4 py-3 text-sm text-white font-semibold text-right">{{ number_format($club->points) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-400">{{ __('No clubs yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $clubs->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
