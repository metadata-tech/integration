<div>
    <h1 class="mt-8 text-2xl font-medium text-gray-900 dark:text-white">
        MQR (Malaysian Qualifications Register) Reference Data
    </h1>

    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        Klik butang di bawah untuk menarik data API dan memulakan padanan automatik secara batch.
    </p>

    <div class="mt-4 flex flex-wrap gap-2">
        <form wire:submit="process">
            <x-button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="process"
                :disabled="$isMapping || $hasActiveRun"
            >
                <span wire:loading.remove wire:target="process">
                    1. Sync Data MQR (API)
                </span>
                <span wire:loading wire:target="process">
                    Sedang Memuat Turun Data...
                </span>
            </x-button>
        </form>

        <form wire:submit.prevent="startMapping">
            <x-button
                type="submit"
                class="bg-indigo-600 hover:bg-indigo-700"
                :disabled="$isMapping"
            >
                @if($isMapping)
                    Sedang Memproses Padanan...
                @elseif($hasActiveRun)
                    2. Sambung Automasi Padanan
                @else
                    2. Automasi Padanan (Mapping)
                @endif
            </x-button>
        </form>
    </div>

    @if($result != null)
        <div class="mt-6 rounded-md p-4 {{ $result['status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
            <h2 class="text-lg font-medium">Status Proses:</h2>
            <p class="mt-2 font-bold">{{ $result['message'] }}</p>
        </div>
    @endif

    <div class="mt-8 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                <h2 class="text-base font-bold text-gray-800">
                    Laporan Padanan yang Telah Siap
                </h2>
                <p class="mt-1 text-xs text-gray-500">
                    Fail hanya diterbitkan selepas semua rekod selesai dipadan. Muat turun tidak menjalankan padanan semula.
                </p>
            </div>
        </div>

        <div class="mt-4 space-y-3">
            @forelse($completedReports as $report)
                <div class="rounded-md border border-gray-200 bg-gray-50 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="text-sm text-gray-700">
                            <p class="font-semibold">
                                Selesai: {{ $report['completed_at'] }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ number_format($report['total']) }} rekod ·
                                {{ number_format($report['berjaya']) }} berjaya ·
                                {{ number_format($report['gagal']) }} gagal
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <a
                                href="{{ route('mqr.reports.download', ['run' => $report['id'], 'type' => 'all']) }}"
                                class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-white transition hover:bg-indigo-500"
                            >
                                Laporan Lengkap
                            </a>
                            <a
                                href="{{ route('mqr.reports.download', ['run' => $report['id'], 'type' => 'success']) }}"
                                class="inline-flex items-center rounded-md bg-gray-800 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-white transition hover:bg-gray-700"
                            >
                                Berjaya
                            </a>
                            <a
                                href="{{ route('mqr.reports.download', ['run' => $report['id'], 'type' => 'failure']) }}"
                                class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-white transition hover:bg-red-500"
                            >
                                Gagal + Sebab
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <p class="rounded-md bg-gray-50 px-4 py-3 text-sm text-gray-500">
                    Belum ada laporan lengkap. Laporan pertama akan tersedia selepas proses padanan selesai.
                </p>
            @endforelse
        </div>
    </div>

    @if($isMapping || $processedCount > 0)
        <div class="mt-8 rounded-lg border border-gray-200 bg-gray-50 p-6 shadow-sm">
            <div class="mb-2 flex justify-between gap-4">
                <span class="text-sm font-semibold text-gray-700">
                    {{ $isMapping ? 'Kemajuan Padanan...' : 'Kemajuan Padanan Terakhir' }}
                </span>
                <span class="text-sm font-semibold text-gray-700">
                    {{ number_format($processedCount) }} / {{ number_format($totalMqr) }}
                    ({{ $totalMqr > 0 ? round(($processedCount / $totalMqr) * 100) : 0 }}%)
                </span>
            </div>

            <div class="h-3 w-full rounded-full bg-gray-200">
                <div
                    class="h-3 rounded-full bg-indigo-600 transition-all duration-300 ease-in-out"
                    style="width: {{ $totalMqr > 0 ? min(100, ($processedCount / $totalMqr) * 100) : 0 }}%"
                ></div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4 text-center">
                <div class="rounded-md border border-green-300 bg-green-100 p-3">
                    <p class="text-sm font-semibold text-green-700">Berjaya Map</p>
                    <p class="text-2xl font-bold text-green-800">
                        {{ number_format($berjayaCount) }}
                    </p>
                </div>
                <div class="rounded-md border border-red-300 bg-red-100 p-3">
                    <p class="text-sm font-semibold text-red-700">
                        Gagal / Tiada Padanan
                    </p>
                    <p class="text-2xl font-bold text-red-800">
                        {{ number_format($gagalCount) }}
                    </p>
                </div>
            </div>

            @if(!$isMapping && $runStatus === 'completed')
                <div class="mt-4 text-center text-sm font-bold text-green-600">
                    Proses padanan data MQR telah selesai sepenuhnya.
                </div>
            @elseif(!$isMapping && $hasActiveRun)
                <div class="mt-4 text-center text-sm font-semibold text-amber-700">
                    @if($runStatus === 'finalizing')
                        Pemuktamadan laporan dijeda. Klik “Sambung Automasi Padanan” untuk mencuba semula.
                    @else
                        Proses dijeda. Klik “Sambung Automasi Padanan” untuk meneruskan dari rekod terakhir.
                    @endif
                </div>
            @endif

            <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="h-48 overflow-y-auto rounded-md border border-gray-200 bg-white p-3">
                    <h3 class="sticky top-0 mb-2 border-b bg-white pb-1 text-xs font-bold uppercase text-green-600">
                        Live Feed: 10 Berjaya Terkini
                    </h3>
                    <ul class="space-y-1 text-xs text-gray-600">
                        @forelse($logBerjaya as $log)
                            <li class="flex items-start gap-1">
                                <span class="text-green-500">✓</span>
                                <span class="truncate" title="{{ $log }}">{{ $log }}</span>
                            </li>
                        @empty
                            <li class="italic text-gray-400">Tiada rekod...</li>
                        @endforelse
                    </ul>
                </div>

                <div class="h-48 overflow-y-auto rounded-md border border-gray-200 bg-white p-3">
                    <h3 class="sticky top-0 mb-2 border-b bg-white pb-1 text-xs font-bold uppercase text-red-600">
                        Live Feed: 10 Gagal Terkini
                    </h3>
                    <ul class="space-y-1 text-xs text-gray-600">
                        @forelse($logGagal as $log)
                            <li class="flex items-start gap-1">
                                <span class="text-red-500">✗</span>
                                <span class="truncate" title="{{ $log }}">{{ $log }}</span>
                            </li>
                        @empty
                            <li class="italic text-gray-400">Tiada rekod...</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    @endif
</div>
