<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('Admin — All Parts') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-gray-500 text-sm">{{ $parts->total() }} {{ __('parts total') }}</p>

            <div class="overflow-x-auto bg-racing-800 border border-racing-600 rounded-lg">
                <table class="min-w-full text-sm text-left text-gray-300">
                    <thead class="text-xs uppercase text-gray-500 border-b border-racing-600">
                        <tr>
                            <th class="px-4 py-3">{{ __('ID') }}</th>
                            <th class="px-4 py-3">{{ __('Owner') }}</th>
                            <th class="px-4 py-3">{{ __('Part') }}</th>
                            <th class="px-4 py-3">{{ __('Slot') }}</th>
                            <th class="px-4 py-3">{{ __('Equipped on') }}</th>
                            <th class="px-4 py-3">{{ __('Condition') }}</th>
                            <th class="px-4 py-3">{{ __('Level') }}</th>
                            <th class="px-4 py-3">{{ __('Added') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($parts as $part)
                            <tr class="border-b border-racing-600 last:border-0">
                                <td class="px-4 py-3 text-gray-500">{{ $part->id }}</td>
                                <td class="px-4 py-3">
                                    <span class="text-white">{{ $part->user?->name }}</span>
                                    <span class="block text-gray-500 text-xs">{{ $part->user?->email }}</span>
                                </td>
                                <td class="px-4 py-3 text-white">{{ $part->partModel?->name }}</td>
                                <td class="px-4 py-3 capitalize text-xs">{{ str_replace('_', ' ', $part->slot->value) }}</td>
                                <td class="px-4 py-3 text-xs">
                                    {{ $part->car?->carModel?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3">{{ $part->condition }}</td>
                                <td class="px-4 py-3">{{ $part->upgrade_level ?? 0 }}</td>
                                <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">{{ $part->created_at->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-gray-400">{{ __('No parts yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $parts->links() }}</div>
        </div>
    </div>
</x-app-layout>
