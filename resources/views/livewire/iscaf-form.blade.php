<div>
    <form wire:submit.prevent="kemasKini" class="space-y-4">
        <div>
            <x-label for="no_kp" value="{{ __('No KP (Anggota)') }}" />
            <x-input id="no_kp" type="text" class="mt-1 block w-full" wire:model="no_kp" required />
            <x-input-error for="no_kp" class="mt-2" />
        </div>

        <div>
            <x-label for="mykad_no" value="{{ __('MyKad No (Pemohon)') }}" />
            <x-input id="mykad_no" type="text" class="mt-1 block w-full" wire:model="mykad_no" />
            <x-input-error for="mykad_no" class="mt-2" />
        </div>

        <div>
            <x-label for="cawangan" value="{{ __('Cawangan') }}" />
            <x-select id="cawangan" wire:model="cawangan" class="mt-1 block w-full">
                <option value="HQ">HQ - Ibu Pejabat / Putrajaya</option>
                <option value="S">S - Sabah</option>
                <option value="Q">Q - Sarawak</option>
            </x-select>
            <x-input-error for="cawangan" class="mt-2" />
        </div>

        {{-- Tambahan Parameter nt (Umum) --}}
        <div>
            <x-label for="nt" value="{{ __('Jenis Fail (NT) - Tidak Wajib') }}" />
            <x-select id="nt" wire:model="nt" class="mt-1 block w-full">
                <option value="">-- Sila Pilih (Pilihan) --</option>
                <option value="0">0 - Fail Biasa</option>
                <option value="1">1 - Fail Sulit / Dipertingkat</option>
            </x-select>
            <x-input-error for="nt" class="mt-2" />
        </div>

        <div>
            <x-label value="{{ __('Aktiviti') }}" />
            <div class="mt-2 flex items-center gap-6">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input id="masuk" type="radio" wire:model="aktiviti" value="1" name="aktiviti"
                           class="border-outline-300 text-primary-600 focus:ring-primary-600" />
                    {{ __('Masuk') }}
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input id="keluar" type="radio" wire:model="aktiviti" value="2" name="aktiviti"
                           class="border-outline-300 text-primary-600 focus:ring-primary-600" />
                    {{ __('Keluar') }}
                </label>
            </div>
            <x-input-error for="aktiviti" class="mt-2" />
        </div>

        <div class="flex flex-wrap gap-2 pt-2">
            <x-button type="submit" wire:loading.attr="disabled">
                Kemas Kini
            </x-button>

            <x-secondary-button type="button" wire:click="checkStatus" wire:loading.attr="disabled">
                Semak Status
            </x-secondary-button>

            <x-secondary-button type="button" wire:click="getInfo" wire:loading.attr="disabled">
                Maklumat Fail
            </x-secondary-button>
        </div>

        {{-- Daftar Section --}}
        <div class="border-t border-outline-200 pt-5">
            <h3 class="font-heading text-sm font-semibold text-gray-900">Daftar Fail Baru</h3>
        </div>

        <div>
            <x-label for="no_pemerolehan" value="{{ __('No Pemerolehan') }}" />
            <x-input id="no_pemerolehan" type="text" class="mt-1 block w-full" wire:model="no_pemerolehan" />
            <x-input-error for="no_pemerolehan" class="mt-2" />
        </div>

        <div>
            <x-label for="data_nama" value="{{ __('Nama Anggota') }}" />
            <x-input id="data_nama" type="text" class="mt-1 block w-full" wire:model="data_nama" />
        </div>

        <div>
            <x-label for="data_ic_no" value="{{ __('No KP Anggota') }}" />
            <x-input id="data_ic_no" type="text" class="mt-1 block w-full" wire:model="data_ic_no" />
        </div>

        <div>
            <x-label for="data_position_id" value="{{ __('Position ID') }}" />
            <x-input id="data_position_id" type="text" class="mt-1 block w-full" wire:model="data_position_id" />
        </div>

        {{-- Tambahan Parameter nt untuk pendaftaran fail --}}
        <div>
            <x-label for="data_nt" value="{{ __('Jenis Fail Untuk Pendaftaran (NT) - Tidak Wajib') }}" />
            <x-select id="data_nt" wire:model="data_nt" class="mt-1 block w-full">
                <option value="">-- Sila Pilih (Pilihan) --</option>
                <option value="0">0 - Fail Biasa</option>
                <option value="1">1 - Fail Sulit / Dipertingkat</option>
            </x-select>
        </div>

        <div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <x-checkbox id="data_volume" wire:model="data_volume" />
                {{ __('Jilid (Volume)') }}
            </label>
        </div>

        <div class="pt-2">
            <x-button type="button" wire:click="daftar" wire:loading.attr="disabled">
                Daftar Fail
            </x-button>
        </div>

        @if (! empty($this->result))
            <x-result-panel :result="$this->result" />

            @if (isset($this->result['status']) && $this->result['status'] == 'success' && isset($this->result['data']))
                <dl class="mt-3 divide-y divide-outline-200 rounded-lg border border-outline-200 text-sm">
                    @if (isset($this->result['data']['no_rujukan']))
                        <div class="flex justify-between gap-4 px-4 py-2.5">
                            <dt class="text-gray-500">No Rujukan</dt>
                            <dd class="font-medium text-gray-900">{{ $this->result['data']['no_rujukan'] }}</dd>
                        </div>
                    @endif
                    @if (isset($this->result['data']['no_iscaf']))
                        <div class="flex justify-between gap-4 px-4 py-2.5">
                            <dt class="text-gray-500">No iSCAF</dt>
                            <dd class="font-medium text-gray-900">{{ $this->result['data']['no_iscaf'] }}</dd>
                        </div>
                    @endif
                    @if (isset($this->result['data']['bil_surat']))
                        <div class="flex justify-between gap-4 px-4 py-2.5">
                            <dt class="text-gray-500">Bil Surat</dt>
                            <dd class="font-medium text-gray-900">{{ $this->result['data']['bil_surat'] }}</dd>
                        </div>
                    @endif
                </dl>
            @endif
        @endif
    </form>
</div>
