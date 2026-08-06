<div>
    <form wire:submit="process" class="space-y-4">
        <div>
            <x-label for="kpm_no_kp" value="{{ __('No KP') }}" />
            <x-input id="kpm_no_kp" type="text" class="mt-1 block w-full" wire:model="no_kp" required />
            <x-input-error for="no_kp" class="mt-2" />
        </div>

        <div class="pt-2">
            <x-button wire:loading.attr="disabled">
                Semak KPM ePenempatan / eOperasi
            </x-button>
        </div>
    </form>

    @if ($this->result != null)
        <x-result-panel :result="$this->result" />
    @endif
</div>
