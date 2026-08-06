<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading text-lg font-semibold text-gray-900">
            {{ $integration['title'] }}
        </h2>
        <p class="mt-0.5 text-sm text-gray-500">
            {{ $integration['description'] }}
        </p>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="myds-card max-w-3xl p-6">
            @livewire($integration['component'])
        </div>
    </div>
</x-app-layout>
