<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            {{ __('Create Club') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-racing-800 border border-racing-600 rounded-lg p-6 space-y-6">
                <p class="text-gray-400 text-sm">
                    {{ __('Start your own crew. You will become the club owner.') }}
                </p>

                <form method="POST" action="{{ route('clubs.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="name" :value="__('Club name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required
                            minlength="{{ config('game.clubs.name_min_length') }}"
                            maxlength="{{ config('game.clubs.name_max_length') }}"
                            value="{{ old('name') }}" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="description" :value="__('Description (optional)')" />
                        <textarea id="description" name="description" rows="4"
                            class="mt-1 block w-full border-racing-600 bg-racing-700 text-gray-200 focus:border-accent-neon focus:ring-accent-neon rounded-md shadow-sm"
                            maxlength="500">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="flex gap-3">
                        <x-primary-button>{{ __('Create club') }}</x-primary-button>
                        <a href="{{ route('clubs.index') }}" class="inline-flex items-center px-4 py-2 text-sm text-gray-400 hover:text-accent-neon">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
