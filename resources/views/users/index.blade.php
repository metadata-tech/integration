<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading text-lg font-semibold text-gray-900">
            {{ __('Pengurusan Pengguna') }}
        </h2>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <livewire:user.index/>
    </div>
</x-app-layout>
