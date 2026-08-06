<div>
    <form wire:submit="process" class="space-y-4">
        <div>
            <x-label for="muet_no_kp" value="{{ __('No KP') }}" />
            <x-input id="muet_no_kp" type="text" class="mt-1 block w-full" wire:model="no_kp" required />
            <x-input-error for="no_kp" class="mt-2" />
        </div>

        <div>
            <x-label for="muet_tahun" value="{{ __('Tahun') }}" />
            <x-input id="muet_tahun" type="text" class="mt-1 block w-full" wire:model="tahun" required />
            <x-input-error for="tahun" class="mt-2" />
        </div>

        <div class="pt-2">
            <x-button wire:loading.attr="disabled">
                Semak MyGDX MUET
            </x-button>
        </div>
    </form>

    @if ($this->result != null)
        <x-result-panel :result="$this->result" />
    @endif
</div>
