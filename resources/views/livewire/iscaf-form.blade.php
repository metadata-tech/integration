<div>
    <h1 class="mt-8 text-2xl font-medium text-gray-900 dark:text-white">
        Integrasi iSCAF API
    </h1>

    <form wire:submit.prevent="kemasKini">

        <div class="col-span-6 sm:col-span-4">
            <x-label for="no_kp" value="{{ __('No KP (Anggota)') }}" />
            <x-input id="no_kp" type="text" class="mt-1 block w-full" wire:model="no_kp" required />
            <x-input-error for="no_kp" class="mt-2" />
        </div>

        <div class="col-span-6 sm:col-span-4 mt-4">
            <x-label for="mykad_no" value="{{ __('MyKad No (Pemohon)') }}" />
            <x-input id="mykad_no" type="text" class="mt-1 block w-full" wire:model="mykad_no" />
            <x-input-error for="mykad_no" class="mt-2" />
        </div>

        <div class="col-span-6 sm:col-span-4 mt-4">
            <x-label for="cawangan" value="{{ __('Cawangan') }}" />
            <select id="cawangan" wire:model="cawangan" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                <option value="HQ">HQ - Ibu Pejabat / Putrajaya</option>
                <option value="S">S - Sabah</option>
                <option value="Q">Q - Sarawak</option>
            </select>
            <x-input-error for="cawangan" class="mt-2" />
        </div>

        {{-- Tambahan Parameter nt (Umum) --}}
        <div class="col-span-6 sm:col-span-4 mt-4">
            <x-label for="nt" value="{{ __('Jenis Fail (NT) - Tidak Wajib') }}" />
            <select id="nt" wire:model="nt" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                <option value="">-- Sila Pilih (Pilihan) --</option>
                <option value="0">0 - Fail Biasa</option>
                <option value="1">1 - Fail Sulit / Dipertingkat</option>
            </select>
            <x-input-error for="nt" class="mt-2" />
        </div>

        <div class="col-span-6 sm:col-span-4 mt-4">
            <div class="col-span-4">
                <x-input id="masuk" type="radio" wire:model="aktiviti" value="1" name="aktiviti" />
                <label for="masuk" class="font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Masuk') }}</label>

                <x-input id="keluar" type="radio" wire:model="aktiviti" value="2" name="aktiviti" />
                <label for="keluar" class="font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Keluar') }}</label>
            </div>
            <x-input-error for="aktiviti" class="mt-2" />
        </div>

        <br />
        <div class="flex gap-2 flex-wrap">
            <x-button type="submit">
                Kemas Kini
            </x-button>

            <x-button type="button" wire:click="checkStatus" class="bg-gray-500 hover:bg-gray-600">
                Semak Status
            </x-button>

            <x-button type="button" wire:click="getInfo" class="bg-blue-500 hover:bg-blue-600">
                Maklumat Fail
            </x-button>
        </div>

        {{-- Daftar Section --}}
        <h2 class="mt-8 text-xl font-medium text-gray-900 dark:text-white">Daftar Fail Baru</h2>

        <div class="col-span-6 sm:col-span-4 mt-4">
            <x-label for="no_pemerolehan" value="{{ __('No Pemerolehan') }}" />
            <x-input id="no_pemerolehan" type="text" class="mt-1 block w-full" wire:model="no_pemerolehan" />
            <x-input-error for="no_pemerolehan" class="mt-2" />
        </div>

        <div class="col-span-6 sm:col-span-4 mt-4">
            <x-label for="data_nama" value="{{ __('Nama Anggota') }}" />
            <x-input id="data_nama" type="text" class="mt-1 block w-full" wire:model="data_nama" />
        </div>

        <div class="col-span-6 sm:col-span-4 mt-4">
            <x-label for="data_ic_no" value="{{ __('No KP Anggota') }}" />
            <x-input id="data_ic_no" type="text" class="mt-1 block w-full" wire:model="data_ic_no" />
        </div>

        <div class="col-span-6 sm:col-span-4 mt-4">
            <x-label for="data_position_id" value="{{ __('Position ID') }}" />
            <x-input id="data_position_id" type="text" class="mt-1 block w-full" wire:model="data_position_id" />
        </div>

        {{-- Tambahan Parameter nt untuk pendaftaran fail --}}
        <div class="col-span-6 sm:col-span-4 mt-4">
            <x-label for="data_nt" value="{{ __('Jenis Fail Untuk Pendaftaran (NT) - Tidak Wajib') }}" />
            <select id="data_nt" wire:model="data_nt" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                <option value="">-- Sila Pilih (Pilihan) --</option>
                <option value="0">0 - Fail Biasa</option>
                <option value="1">1 - Fail Sulit / Dipertingkat</option>
            </select>
        </div>

        <div class="col-span-6 sm:col-span-4 mt-4">
            <label class="flex items-center">
                <x-input id="data_volume" type="checkbox" wire:model="data_volume" />
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Jilid (Volume)') }}</span>
            </label>
        </div>

        <br />
        <x-button type="button" wire:click="daftar" class="bg-green-500 hover:bg-green-600">
            Daftar Fail
        </x-button>

        <br />

        @if(!empty($this->result) && isset($this->result['status']) && $this->result['status'] == 'error')
            <div class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                {{ $this->result['message'] ?? 'Ralat tidak diketahui' }}
            </div>
        @endif

        @if(!empty($this->result) && isset($this->result['status']) && $this->result['status'] == 'success')
            <div class="mt-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                {{ $this->result['message'] ?? 'Berjaya' }}
            </div>

            @if(isset($this->result['data']))
                <h2 class="mt-4 text-xl font-medium text-gray-900 dark:text-white">Data</h2>
                <div class="mt-2 bg-gray-100 dark:bg-gray-800 p-4 rounded">
                    @if(isset($this->result['data']['no_rujukan']))
                        <p><strong>No Rujukan:</strong> {{ $this->result['data']['no_rujukan'] }}</p>
                    @endif
                    @if(isset($this->result['data']['no_iscaf']))
                        <p><strong>No iSCAF:</strong> {{ $this->result['data']['no_iscaf'] }}</p>
                    @endif
                    @if(isset($this->result['data']['bil_surat']))
                        <p><strong>Bil Surat:</strong> {{ $this->result['data']['bil_surat'] }}</p>
                    @endif
                </div>
            @endif
        @endif
    </form>
</div>