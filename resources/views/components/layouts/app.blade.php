{{-- Livewire full-page component layout: reuses the main app shell --}}
<x-app-layout>
    @isset($header)
        <x-slot name="header">{{ $header }}</x-slot>
    @endisset

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        {{ $slot }}
    </div>
</x-app-layout>
