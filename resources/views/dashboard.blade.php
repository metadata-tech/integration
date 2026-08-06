<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading text-lg font-semibold text-gray-900">
            Utama
        </h2>
        <p class="mt-0.5 text-sm text-gray-500">
            Gerbang integrasi API Suruhanjaya Perkhidmatan Pendidikan dengan agensi luar.
        </p>
    </x-slot>

    <div class="space-y-8 px-4 py-6 sm:px-6 lg:px-8">
        {{-- Quick stats --}}
        <section>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="myds-card p-5">
                    <p class="text-sm text-gray-500">Panggilan hari ini</p>
                    <p class="mt-1 font-heading text-2xl font-semibold text-gray-900">{{ number_format($stats['today']) }}</p>
                </div>
                <div class="myds-card p-5">
                    <p class="text-sm text-gray-500">Ralat hari ini</p>
                    <p class="mt-1 font-heading text-2xl font-semibold {{ $stats['errors_today'] > 0 ? 'text-danger-600' : 'text-gray-900' }}">
                        {{ number_format($stats['errors_today']) }}
                    </p>
                </div>
                <div class="myds-card p-5">
                    <p class="text-sm text-gray-500">Jumlah panggilan</p>
                    <p class="mt-1 font-heading text-2xl font-semibold text-gray-900">{{ number_format($stats['total']) }}</p>
                </div>
                <div class="myds-card p-5">
                    <p class="text-sm text-gray-500">Panggilan terakhir</p>
                    <p class="mt-1 font-heading text-2xl font-semibold text-gray-900">
                        {{ $stats['last'] ? \Illuminate\Support\Carbon::parse($stats['last'])->diffForHumans() : '—' }}
                    </p>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-400">
                Statistik berdasarkan log API. Lihat butiran di
                <a href="{{ route('logapi.index') }}" class="text-primary-600 hover:underline">Log API</a>.
            </p>
        </section>

        {{-- Integration cards --}}
        <section>
            <h3 class="font-heading text-base font-semibold text-gray-900">Perkhidmatan Integrasi</h3>
            <p class="mt-0.5 text-sm text-gray-500">Pilih perkhidmatan untuk membuat semakan atau ujian API.</p>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach (config('integrations') as $slug => $integration)
                    <a href="{{ route("integrasi.{$slug}") }}"
                       class="myds-card group flex flex-col p-5 transition hover:border-primary-300 hover:shadow-context-menu">
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-heading text-sm font-semibold text-gray-900 group-hover:text-primary-700">
                                {{ $integration['label'] }}
                            </p>
                            <svg class="mt-0.5 size-4 shrink-0 text-gray-300 transition group-hover:translate-x-0.5 group-hover:text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                            </svg>
                        </div>
                        <p class="mt-2 text-sm leading-relaxed text-gray-500">
                            {{ $integration['description'] }}
                        </p>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
</x-app-layout>
