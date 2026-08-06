<div>
    <form wire:submit="process" class="space-y-4">
        <div>
            <x-label for="search_no_kp" value="{{ __('No KP') }}" />
            <x-input id="search_no_kp" type="text" class="mt-1 block w-full" wire:model="no_kp" required />
            <x-input-error for="no_kp" class="mt-2" />
        </div>

        <div>
            <x-label for="search_cawangan" value="{{ __('Cawangan') }}" />
            <x-select id="search_cawangan" wire:model="cawangan" class="mt-1 block w-full">
                <option value="HQ">HQ - Ibu Pejabat / Putrajaya</option>
                <option value="S">S - Sabah</option>
                <option value="Q">Q - Sarawak</option>
            </x-select>
            <x-input-error for="cawangan" class="mt-2" />
        </div>

        <div class="pt-2">
            <x-button wire:loading.attr="disabled">
                Cari
            </x-button>
        </div>
    </form>

    @if (! empty($this->result) && isset($this->result['status']))
        @if ($this->result['status'] == 'error')
            <div class="mt-6 rounded-lg border border-danger-200 bg-danger-50 px-4 py-3 text-sm text-danger-700">
                {{ $this->result['message'] ?? 'Ralat tidak diketahui' }}
            </div>
        @endif

        @if ($this->result['status'] == 'success')
            <div class="mt-6 border-t border-outline-200 pt-5">
                <div class="flex items-center gap-2">
                    <h3 class="font-heading text-sm font-semibold text-gray-900">Maklumat Fail</h3>
                    <span class="myds-badge-success">Berjaya</span>
                </div>

                <p class="mt-2 text-sm text-gray-600">{{ $this->result['message'] }}</p>

                @if (isset($this->result['data']))
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
            </div>
        @endif
    @endif
</div>
