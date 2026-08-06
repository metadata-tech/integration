<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading text-lg font-semibold text-gray-900">
            {{ __('Pengurusan Kebenaran') }}
        </h2>
        <p class="mt-0.5 text-sm text-gray-500">
            Urus senarai kebenaran sistem.
        </p>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <livewire:admin.permissions.permission-management />
    </div>
</x-app-layout>
