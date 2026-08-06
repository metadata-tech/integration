<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading text-lg font-semibold text-gray-900">
            {{ __('API Tokens') }}
        </h2>
    </x-slot>

    <div>
        <div class="px-4 py-6 sm:px-6 lg:px-8">
            @livewire('api.api-token-manager')
        </div>
    </div>
</x-app-layout>
