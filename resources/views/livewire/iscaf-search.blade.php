<div>
    <h1 class="mt-8 text-2xl font-medium text-gray-900 dark:text-white">
        iSCAF Carian Maklumat Fail
    </h1>

    <form wire:submit="process">

        <!-- NO KP -->
        <div class="col-span-6 sm:col-span-4">
            <x-label for="no_kp" value="{{ __('NO KP') }}" />
            <x-input id="no_kp" type="text" class="mt-1 block w-full" wire:model="no_kp" required />
            <x-input-error for="no_kp" class="mt-2" />
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

        <br />
        <x-button>
            Cari
        </x-button>

        @if(!empty($this->result) && isset($this->result['status']))

            @if($this->result['status'] == 'error')
                <div class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ $this->result['message'] ?? 'Ralat tidak diketahui' }}
                </div>
            @endif

            @if($this->result['status'] == 'success')
                <h2 class="mt-8 text-xl font-medium text-gray-900 dark:text-white">
                    Maklumat Fail
                </h2>

                <div class="mt-4 bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">{{ $this->result['message'] }}</h3>
                    </div>
                    @if(isset($this->result['data']))
                        <div class="border-t border-gray-200 dark:border-gray-700">
                            <dl>
                                @if(isset($this->result['data']['no_rujukan']))
                                    <div class="bg-gray-50 dark:bg-gray-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">No Rujukan</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">{{ $this->result['data']['no_rujukan'] }}</dd>
                                    </div>
                                @endif
                                @if(isset($this->result['data']['no_iscaf']))
                                    <div class="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">No iSCAF</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">{{ $this->result['data']['no_iscaf'] }}</dd>
                                    </div>
                                @endif
                                @if(isset($this->result['data']['bil_surat']))
                                    <div class="bg-gray-50 dark:bg-gray-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Bil Surat</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">{{ $this->result['data']['bil_surat'] }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    @endif
                </div>
            @endif

        @endif

    </form>
</div>
