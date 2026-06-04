<nav x-data="{ open: false }" class="bg-racing-800 border-b border-racing-600">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-accent-neon" />
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('garage.index')" :active="request()->routeIs('garage.*')">
                        {{ __('Garage') }}
                    </x-nav-link>
                    <x-nav-link :href="route('shop.index')" :active="request()->routeIs('shop.*') && ! request()->routeIs('premium.*')">
                        {{ __('Shop') }}
                    </x-nav-link>
                    @if ((Auth::user()->playerProfile?->level ?? 1) >= config('game.mechanic.unlock_level', 5))
                        <x-nav-link :href="route('mechanic.index')" :active="request()->routeIs('mechanic.*')">
                            {{ __('Mechanic') }}
                        </x-nav-link>
                    @endif
                    @if ((Auth::user()->playerProfile?->level ?? 1) >= config('game.clubs.unlock_level'))
                        <x-nav-link :href="route('clubs.index')" :active="request()->routeIs('clubs.*')">
                            {{ __('Club') }}
                        </x-nav-link>
                    @endif
                    <x-nav-link :href="route('races.index')" :active="request()->routeIs('races.*')">
                        {{ __('Races') }}
                    </x-nav-link>
                    <x-nav-link :href="route('pvp.index')" :active="request()->routeIs('pvp.*')">
                        {{ __('PvP') }}
                    </x-nav-link>
                    <x-nav-link :href="route('leaderboard.index')" :active="request()->routeIs('leaderboard.*')">
                        {{ __('Rankings') }}
                    </x-nav-link>
                    <x-nav-link :href="route('race-history.index')" :active="request()->routeIs('race-history.*')">
                        {{ __('History') }}
                    </x-nav-link>
                    <x-nav-link :href="route('daily-rewards.index')" :active="request()->routeIs('daily-rewards.*')">
                        {{ __('Daily') }}
                    </x-nav-link>
                    <x-nav-link :href="route('premium.index')" :active="request()->routeIs('premium.*')" variant="premium">
                        {{ __('Premium') }}
                    </x-nav-link>
                    @if ((Auth::user()->playerProfile?->level ?? 1) >= config('game.tournaments.unlock_level'))
                        <x-nav-link :href="route('premium-fuel.index')" :active="request()->routeIs('premium-fuel.*')">
                            {{ __('Premium fuel') }}
                        </x-nav-link>
                    @endif
                    <x-nav-link :href="route('players.show', Auth::user())" :active="request()->routeIs('players.show') && request()->route('user')?->is(Auth::user())">
                        {{ __('Profile') }}
                    </x-nav-link>
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-300 bg-racing-800 hover:text-accent-neon focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Account settings') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-accent-neon hover:bg-racing-700 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('garage.index')" :active="request()->routeIs('garage.*')">
                {{ __('Garage') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('shop.index')" :active="request()->routeIs('shop.*') && ! request()->routeIs('premium.*')">
                {{ __('Shop') }}
            </x-responsive-nav-link>
            @if ((Auth::user()->playerProfile?->level ?? 1) >= config('game.mechanic.unlock_level', 5))
                <x-responsive-nav-link :href="route('mechanic.index')" :active="request()->routeIs('mechanic.*')">
                    {{ __('Mechanic') }}
                </x-responsive-nav-link>
            @endif
            @if ((Auth::user()->playerProfile?->level ?? 1) >= config('game.clubs.unlock_level'))
                <x-responsive-nav-link :href="route('clubs.index')" :active="request()->routeIs('clubs.*')">
                    {{ __('Club') }}
                </x-responsive-nav-link>
            @endif
            <x-responsive-nav-link :href="route('races.index')" :active="request()->routeIs('races.*')">
                {{ __('Races') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('pvp.index')" :active="request()->routeIs('pvp.*')">
                {{ __('PvP') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('leaderboard.index')" :active="request()->routeIs('leaderboard.*')">
                {{ __('Rankings') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('race-history.index')" :active="request()->routeIs('race-history.*')">
                {{ __('History') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('daily-rewards.index')" :active="request()->routeIs('daily-rewards.*')">
                {{ __('Daily') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('premium.index')" :active="request()->routeIs('premium.*')" variant="premium">
                {{ __('Premium') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('players.show', Auth::user())" :active="request()->routeIs('players.show') && request()->route('user')?->is(Auth::user())">
                {{ __('Profile') }}
            </x-responsive-nav-link>
        </div>

        <div class="pt-4 pb-1 border-t border-racing-600">
            <div class="px-4">
                <div class="font-medium text-base text-gray-200">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Account settings') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
