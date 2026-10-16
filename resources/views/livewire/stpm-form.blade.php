<div>
    <form wire:submit="process" class="space-y-4">
        <div>
            <x-label for="stpm_no_kp" value="No KP" />
            <x-input id="stpm_no_kp" type="text" class="mt-1 block w-full" wire:model="no_kp" autocomplete="off" required />
            <x-input-error for="no_kp" class="mt-2" />
        </div>

        <div>
            <x-label for="stpm_tahun" value="Tahun" />
            <x-input id="stpm_tahun" type="text" inputmode="numeric" maxlength="4" class="mt-1 block w-full" wire:model="tahun" required />
            <x-input-error for="tahun" class="mt-2" />
        </div>

        <div class="pt-2">
            <x-button wire:loading.attr="disabled" wire:target="process">
                Semak MyGDX STPM
            </x-button>
        </div>
    </form>

    @if ($result !== null)
        <x-result-panel :result="$result" title="Keputusan STPM" />
    @endif
</div>
