<header class="sticky top-0 z-40 border-b border-outline-200 bg-white">
    <div class="max-w-screen-2xl mx-auto flex h-14 items-center gap-x-3 px-4 sm:px-6">
        {{-- Mobile sidebar toggle --}}
        <button type="button"
                @click="sidebarOpen = ! sidebarOpen"
                class="-ms-1.5 inline-flex items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-washed-100 hover:text-gray-700 lg:hidden">
            <span class="sr-only">Buka menu</span>
            <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
            </svg>
        </button>

        {{-- Brand --}}
        <a href="{{ route('dashboard') }}" class="flex items-center gap-x-2.5">
            <x-application-mark class="block h-9 w-auto" />
            <span class="hidden sm:block">
                <span class="block font-heading text-sm font-semibold leading-tight text-gray-900">{{ config('app.name', 'MySPP Integration') }}</span>
                <span class="block text-[11px] leading-tight text-gray-500">Suruhanjaya Perkhidmatan Pendidikan</span>
            </span>
        </a>

        <div class="flex-1"></div>

        {{-- Teams Dropdown --}}
        @if (Laravel\Jetstream\Jetstream::hasTeamFeatures())
            <div class="relative hidden sm:block">
                <x-dropdown align="right" width="60">
                    <x-slot name="trigger">
                        <span class="inline-flex rounded-md">
                            <button type="button" class="inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-washed-100 hover:text-gray-900 focus:outline-none">
                                {{ Auth::user()->currentTeam->name }}

                                <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"/>
                                </svg>
                            </button>
                        </span>
                    </x-slot>

                    <x-slot name="content">
                        <div class="w-60">
                            <div class="block px-4 py-2 text-xs text-gray-400">
                                {{ __('Manage Team') }}
                            </div>

                            <x-dropdown-link href="{{ route('teams.show', Auth::user()->currentTeam->id) }}">
                                {{ __('Team Settings') }}
                            </x-dropdown-link>

                            @can('create', Laravel\Jetstream\Jetstream::newTeamModel())
                                <x-dropdown-link href="{{ route('teams.create') }}">
                                    {{ __('Create New Team') }}
                                </x-dropdown-link>
                            @endcan

                            @if (Auth::user()->allTeams()->count() > 1)
                                <div class="border-t border-outline-200"></div>

                                <div class="block px-4 py-2 text-xs text-gray-400">
                                    {{ __('Switch Teams') }}
                                </div>

                                @foreach (Auth::user()->allTeams() as $team)
                                    <x-switchable-team :team="$team" />
                                @endforeach
                            @endif
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>
        @endif

        {{-- User Dropdown --}}
        <div class="relative">
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                        <button class="flex rounded-full border-2 border-transparent text-sm transition focus:border-outline-300 focus:outline-none">
                            <img class="size-8 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                        </button>
                    @else
                        <span class="inline-flex rounded-md">
                            <button type="button" class="inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-washed-100 hover:text-gray-900 focus:outline-none">
                                {{ Auth::user()->name }}

                                <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                                </svg>
                            </button>
                        </span>
                    @endif
                </x-slot>

                <x-slot name="content">
                    <div class="block px-4 py-2 text-xs text-gray-400">
                        {{ __('Manage Account') }}
                    </div>

                    <x-dropdown-link href="{{ route('profile.show') }}">
                        {{ __('Profile') }}
                    </x-dropdown-link>

                    @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                        <x-dropdown-link href="{{ route('api-tokens.index') }}">
                            {{ __('API Tokens') }}
                        </x-dropdown-link>
                    @endif

                    <div class="border-t border-outline-200"></div>

                    <form method="POST" action="{{ route('logout') }}" x-data>
                        @csrf

                        <x-dropdown-link href="{{ route('logout') }}" @click.prevent="$root.submit();">
                            {{ __('Log Out') }}
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>
    </div>
</header>
