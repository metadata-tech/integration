<div class="myds-card p-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h3 class="text-base font-semibold text-gray-900">Rekod gagal dipadan</h3>
            <p class="mt-1 text-sm text-gray-500">
                @if($run)
                    Larian selesai {{ $run->completed_at?->format('d/m/Y H:i') }}.
                    {{ number_format($failures->total()) }} rekod dalam paparan ini.
                @else
                    Tiada padanan selesai lagi.
                @endif
            </p>
        </div>

        <x-button type="button" wire:click="rebuild" wire:loading.attr="disabled" wire:target="rebuild">
            <span wire:loading.remove wire:target="rebuild">Jana semula senarai</span>
            <span wire:loading wire:target="rebuild">Sedang menjana...</span>
        </x-button>
    </div>

    @if($result)
        <div class="mt-4 rounded-md p-4 {{ $result['status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
            {{ $result['message'] }}
        </div>
    @endif

    <div class="mt-4 flex flex-wrap gap-3">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Cari kod MQA, institusi atau kelayakan"
            class="w-full max-w-md rounded-lg border-gray-300 text-sm"
        >
        <select wire:model.live="reason" class="rounded-lg border-gray-300 text-sm">
            <option value="">Semua sebab</option>
            <option value="institution">Tiada institusi</option>
            <option value="specialization">Tiada pengkhususan</option>
            <option value="both">Kedua-duanya</option>
            <option value="ambiguous">Institusi kabur</option>
        </select>
    </div>

    <div class="mt-4 flex flex-wrap gap-2">
        <x-button type="button" wire:click="selectPage" wire:loading.attr="disabled">
            Pilih muka ini
        </x-button>
        <x-button type="button" wire:click="clearSelection" class="bg-white text-gray-700 border-gray-300 hover:bg-gray-50">
            Kosongkan pilihan
        </x-button>
        <x-button type="button" wire:click="registerInstitutions" wire:loading.attr="disabled" wire:target="registerInstitutions">
            <span wire:loading.remove wire:target="registerInstitutions">Daftar institusi</span>
            <span wire:loading wire:target="registerInstitutions">Sedang mendaftar...</span>
        </x-button>
        <x-button type="button" wire:click="registerSpecializations" wire:loading.attr="disabled" wire:target="registerSpecializations">
            <span wire:loading.remove wire:target="registerSpecializations">Daftar pengkhususan</span>
            <span wire:loading wire:target="registerSpecializations">Sedang mendaftar...</span>
        </x-button>
        <span class="self-center text-sm text-gray-500">{{ count($selected) }} dipilih</span>
    </div>

    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-3 py-2"></th>
                    <th class="px-3 py-2">Kod MQA</th>
                    <th class="px-3 py-2">Institusi (MQR)</th>
                    <th class="px-3 py-2">Kelayakan (MQR)</th>
                    <th class="px-3 py-2">Sebab</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($failures as $failure)
                    <tr wire:key="failure-{{ $failure->id }}">
                        <td class="px-3 py-2">
                            @if($failure->allowsRegistration())
                                <input
                                    type="checkbox"
                                    value="{{ $failure->id }}"
                                    wire:model="selected"
                                >
                            @endif
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ $failure->kod_mqa }}</td>
                        <td class="px-3 py-2">{{ $failure->nama_institusi }}</td>
                        <td class="px-3 py-2">{{ $failure->nama_kelayakan }}</td>
                        <td class="px-3 py-2 text-red-700">{{ $failure->reason }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-6 text-center text-gray-500">
                            Tiada rekod gagal untuk paparan ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $failures->links() }}
    </div>
</div>
