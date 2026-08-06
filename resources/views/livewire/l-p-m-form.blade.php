<div>
    <form wire:submit="process" class="space-y-4">
        <div>
            <x-label for="lpm_no_kp" value="{{ __('No KP') }}" />
            <x-input id="lpm_no_kp" type="text" class="mt-1 block w-full" wire:model="no_kp" required />
            <x-input-error for="no_kp" class="mt-2" />
        </div>

        <div>
            <x-label for="lpm_tahun" value="{{ __('Tahun') }}" />
            <x-input id="lpm_tahun" type="text" class="mt-1 block w-full" wire:model="tahun" required />
            <x-input-error for="tahun" class="mt-2" />
        </div>

        <div>
            <x-label for="lpm_angka_giliran" value="{{ __('Angka Giliran') }}" />
            <x-input id="lpm_angka_giliran" type="text" class="mt-1 block w-full" wire:model="angka_giliran" />
            <x-input-error for="angka_giliran" class="mt-2" />
        </div>

        <div class="pt-2">
            <x-button wire:loading.attr="disabled">
                Semak LPM SVK SPM
            </x-button>
        </div>
    </form>

    @if ($this->result != null)
        @include('livewire.partials.lpm-result', ['result' => $this->result])
    @endif
</div>
