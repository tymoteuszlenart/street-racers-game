<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-200 leading-tight">
                {{ $club->name }}
            </h2>
            <a href="{{ route('clubs.rankings') }}" class="text-sm text-accent-blue hover:text-accent-neon">
                {{ __('Club rankings') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-racing-700 border border-accent-green text-accent-green px-4 py-3 rounded-lg text-sm">
                    @switch(session('status'))
                        @case('club-joined')
                            {{ __('You joined the club.') }}
                            @break
                        @case('member-kicked')
                            {{ __('Member removed.') }}
                            @break
                        @case('member-role-updated')
                            {{ __('Member role updated.') }}
                            @break
                        @case('ownership-transferred')
                            {{ __('Ownership transferred.') }}
                            @break
                    @endswitch
                </div>
            @endif

            <div class="bg-racing-800 border border-racing-600 rounded-lg p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <p class="text-gray-500 text-xs uppercase">{{ __('Points') }}</p>
                        <p class="text-2xl font-bold text-white">{{ number_format($club->points) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-xs uppercase">{{ __('Level') }}</p>
                        <p class="text-2xl font-bold text-white">{{ $club->level }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-xs uppercase">{{ __('Members') }}</p>
                        <p class="text-2xl font-bold text-white">{{ $club->members_count }}/{{ config('game.clubs.max_members') }}</p>
                    </div>
                </div>

                @if ($club->description)
                    <p class="text-gray-400 text-sm border-t border-racing-600 pt-4">{{ $club->description }}</p>
                @endif

                <div class="flex flex-wrap gap-3 pt-2">
                    @if ($currentMembership === null && ! $club->isFull())
                        <form method="POST" action="{{ route('clubs.join', $club) }}">
                            @csrf
                            <x-primary-button>{{ __('Join club') }}</x-primary-button>
                        </form>
                    @elseif ($currentMembership !== null && $currentMembership->role !== \App\Enums\ClubRole::Owner)
                        <form method="POST" action="{{ route('clubs.leave', $club) }}">
                            @csrf
                            <x-danger-button>{{ __('Leave club') }}</x-danger-button>
                        </form>
                    @endif

                    @can('delete', $club)
                        <form method="POST" action="{{ route('clubs.destroy', $club) }}" onsubmit="return confirm('{{ __('Dissolve this club? All members will be removed.') }}')">
                            @csrf
                            @method('DELETE')
                            <x-danger-button>{{ __('Dissolve club') }}</x-danger-button>
                        </form>
                    @endcan
                </div>
            </div>

            <div class="bg-racing-800 border border-racing-600 rounded-lg overflow-hidden">
                <div class="px-4 py-3 bg-racing-700 border-b border-racing-600">
                    <h3 class="text-sm font-medium text-gray-300 uppercase tracking-wider">{{ __('Members') }}</h3>
                </div>
                <table class="min-w-full divide-y divide-racing-600">
                    <thead class="bg-racing-700/50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('Player') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('Role') }}</th>
                            @if ($currentMembership?->role->canManageClub())
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-racing-600">
                        @foreach ($members as $member)
                            <tr>
                                <td class="px-4 py-3 text-sm text-white">
                                    {{ $member->user->name }}
                                    @if ($member->user_id === auth()->id())
                                        <span class="text-gray-500">({{ __('you') }})</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-300 capitalize">{{ $member->role->value }}</td>
                                @if ($currentMembership?->role->canManageClub())
                                    <td class="px-4 py-3 text-right space-x-2">
                                        @can('kick', [$club, $member])
                                            <form method="POST" action="{{ route('clubs.members.kick', [$club, $member]) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-accent-orange hover:text-accent-neon">{{ __('Kick') }}</button>
                                            </form>
                                        @endcan

                                        @can('manageRoles', $club)
                                            @if ($member->role === \App\Enums\ClubRole::Member)
                                                <form method="POST" action="{{ route('clubs.members.role', [$club, $member]) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="action" value="promote">
                                                    <button type="submit" class="text-xs text-accent-blue hover:text-accent-neon">{{ __('Promote') }}</button>
                                                </form>
                                            @elseif ($member->role === \App\Enums\ClubRole::Manager)
                                                <form method="POST" action="{{ route('clubs.members.role', [$club, $member]) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="action" value="demote">
                                                    <button type="submit" class="text-xs text-accent-blue hover:text-accent-neon">{{ __('Demote') }}</button>
                                                </form>
                                            @endif
                                        @endcan
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @can('manageRoles', $club)
                @php
                    $transferCandidates = $members->filter(
                        fn ($member) => $member->user_id !== auth()->id() && $member->role !== \App\Enums\ClubRole::Owner
                    );
                @endphp
                @if ($transferCandidates->isNotEmpty())
                    <div class="bg-racing-800 border border-racing-600 rounded-lg p-6">
                        <h3 class="text-sm font-medium text-gray-300 uppercase tracking-wider mb-4">{{ __('Transfer ownership') }}</h3>
                        <form method="POST" action="{{ route('clubs.transfer-ownership', $club) }}" class="flex flex-wrap items-end gap-4">
                            @csrf
                            <div>
                                <x-input-label for="member_id" :value="__('New owner')" />
                                <select id="member_id" name="member_id" class="mt-1 block w-full border-racing-600 bg-racing-700 text-gray-200 focus:border-accent-neon focus:ring-accent-neon rounded-md shadow-sm">
                                    @foreach ($transferCandidates as $candidate)
                                        <option value="{{ $candidate->id }}">{{ $candidate->user->name }} ({{ $candidate->role->value }})</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('member_id')" class="mt-2" />
                            </div>
                            <x-primary-button>{{ __('Transfer') }}</x-primary-button>
                        </form>
                    </div>
                @endif
            @endcan
        </div>
    </div>
</x-app-layout>
