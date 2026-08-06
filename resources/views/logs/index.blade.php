<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading text-lg font-semibold text-gray-900">
            {{ __('Log API') }}
        </h2>
        <p class="mt-0.5 text-sm text-gray-500">
            Rekod permintaan dan respons API integrasi.
        </p>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <livewire:live-log-api />
    </div>
</x-app-layout>
