<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading text-lg font-semibold text-gray-900">
            Senarai Gagal MQR
        </h2>
        <p class="mt-0.5 text-sm text-gray-500">
            Rekod padanan yang gagal dan tindakan daftar ke jadual rujukan.
        </p>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-4">
            <a href="{{ route('integrasi.mqr') }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">
                Kembali ke MQR
            </a>
        </div>

        @livewire(\App\Livewire\MQRFailureList::class)
    </div>
</x-app-layout>
