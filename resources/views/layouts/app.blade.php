<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SPP Integration') }}</title>

        <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600&family=poppins:500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        <livewire:notifications />

        <x-banner />

        <div x-data="{ sidebarOpen: false }" class="flex min-h-screen flex-col">
            <x-masthead />

            <x-topbar />

            <div class="flex w-full flex-1">
                {{-- Desktop sidebar --}}
                <aside class="hidden w-64 shrink-0 border-e border-outline-200 bg-white lg:block">
                    <div class="sticky top-14 max-h-[calc(100vh-3.5rem)] overflow-y-auto">
                        <x-sidebar />
                    </div>
                </aside>

                {{-- Mobile off-canvas sidebar --}}
                <div x-cloak x-show="sidebarOpen" class="fixed inset-0 z-50 lg:hidden">
                    <div x-show="sidebarOpen" x-transition.opacity.duration.200ms
                         class="fixed inset-0 bg-gray-900/50" @click="sidebarOpen = false"></div>

                    <div x-show="sidebarOpen"
                         x-transition:enter="transition ease-in-out duration-200 transform"
                         x-transition:enter-start="-translate-x-full"
                         x-transition:enter-end="translate-x-0"
                         x-transition:leave="transition ease-in-out duration-200 transform"
                         x-transition:leave-start="translate-x-0"
                         x-transition:leave-end="-translate-x-full"
                         class="fixed inset-y-0 start-0 flex w-72 max-w-[85%] flex-col bg-white shadow-context-menu">
                        <div class="flex h-14 items-center justify-between border-b border-outline-200 px-4">
                            <a href="{{ route('dashboard') }}" class="flex items-center gap-x-2.5">
                                <x-application-mark class="block h-9 w-auto" />
                                <span class="font-heading text-sm font-semibold text-gray-900">{{ config('app.name', 'MySPP Integration') }}</span>
                            </a>
                            <button type="button" @click="sidebarOpen = false"
                                    class="rounded-lg p-2 text-gray-500 hover:bg-washed-100 hover:text-gray-700">
                                <span class="sr-only">Tutup menu</span>
                                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <div class="flex-1 overflow-y-auto">
                            <x-sidebar />
                        </div>
                    </div>
                </div>

                {{-- Main content --}}
                <div class="flex min-w-0 flex-1 flex-col">
                    @if (isset($header))
                        <header class="border-b border-outline-200 bg-white">
                            <div class="px-4 py-5 sm:px-6 lg:px-8">
                                {{ $header }}
                            </div>
                        </header>
                    @endif

                    <main class="flex-1">
                        {{ $slot }}
                    </main>

                    <footer class="border-t border-outline-200 bg-white">
                        <div class="flex flex-col gap-1 px-4 py-4 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                            <p>&copy; {{ date('Y') }} Suruhanjaya Perkhidmatan Pendidikan (SPP)</p>
                            <p>{{ config('app.name', 'MySPP Integration') }}</p>
                        </div>
                    </footer>
                </div>
            </div>
        </div>

        @stack('modals')

        @livewireScripts
    </body>
</html>
