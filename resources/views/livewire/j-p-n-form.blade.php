<div>
    <form wire:submit="process" class="space-y-4">
        <div>
            <x-label for="jpn_no_kp" value="{{ __('No KP Individu') }}" />
            <x-input id="jpn_no_kp" type="text" inputmode="numeric" maxlength="12" class="mt-1 block w-full" wire:model="no_kp" required />
            <x-input-error for="no_kp" class="mt-2" />
        </div>

        <div>
            <x-label for="jpn_user_id" value="{{ __('No KP Pengguna') }}" />
            <x-input id="jpn_user_id" type="text" inputmode="numeric" maxlength="12" class="mt-1 block w-full" wire:model="user_id" required />
            <x-input-error for="user_id" class="mt-2" />
        </div>

        <div class="pt-2">
            <x-button wire:loading.attr="disabled">
                Semak JPN MyIdentity
            </x-button>
        </div>
    </form>

    @if ($this->result != null)
        <x-result-panel :result="$this->result['response'] ?? $this->result" title="Hasil JPN" />
    @endif
</div>
