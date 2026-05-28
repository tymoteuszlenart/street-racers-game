<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-200 leading-tight">
                {{ __('Clubs') }}
            </h2>
            @if ($membership === null)
                <a href="{{ route('clubs.create') }}" class="text-sm text-accent-neon hover:underline">
                    {{ __('Create a club') }}
                </a>
            @else
                <a href="{{ route('clubs.show', $membership->club) }}" class="text-sm text-accent-blue hover:text-accent-neon">
                    {{ __('Your club: :name', ['name' => $membership->club->name]) }}
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <p class="text-gray-400 text-sm">
                    {{ __('Browse open clubs and join a crew. One club per player.') }}
                </p>
                <a href="{{ route('clubs.rankings') }}" class="text-sm text-accent-blue hover:text-accent-neon">
                    {{ __('Club rankings') }}
                </a>
            </div>

            @if (session('status'))
                <div class="bg-racing-700 border border-accent-green text-accent-green px-4 py-3 rounded-lg text-sm">
                    @switch(session('status'))
                        @case('club-created')
                            {{ __('Club created successfully.') }}
                            @break
                        @case('club-joined')
                            {{ __('You joined the club.') }}
                            @break
                        @case('club-left')
                            {{ __('You left the club.') }}
                            @break
                        @case('club-dissolved')
                            {{ __('Club dissolved.') }}
                            @break
                    @endswitch
                </div>
            @endif

            <div class="bg-racing-800 border border-racing-600 rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-racing-600">
                    <thead class="bg-racing-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('Club') }}</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('Members') }}</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('Points') }}</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-racing-600">
                        @forelse ($clubs as $club)
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('clubs.show', $club) }}" class="text-white font-medium hover:text-accent-neon">
                                        {{ $club->name }}
                                    </a>
                                    @if ($club->description)
                                        <p class="text-gray-500 text-xs mt-1 line-clamp-1">{{ $club->description }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-300 text-right">{{ $club->members_count }}/{{ config('game.clubs.max_members') }}</td>
                                <td class="px-4 py-3 text-sm text-white font-semibold text-right">{{ number_format($club->points) }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if ($membership === null && ! $club->isFull())
                                        <form method="POST" action="{{ route('clubs.join', $club) }}" class="inline">
                                            @csrf
                                            <x-primary-button class="text-xs">{{ __('Join') }}</x-primary-button>
                                        </form>
                                    @elseif ($membership?->club_id === $club->id)
                                        <span class="text-accent-neon text-sm">{{ __('Member') }}</span>
                                    @elseif ($club->isFull())
                                        <span class="text-gray-500 text-sm">{{ __('Full') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-400">{{ __('No clubs yet. Be the first to create one!') }}</td>
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
