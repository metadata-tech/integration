<div>
    {{-- Knowing others is intelligence; knowing yourself is true wisdom. --}}

    <h1 class="mt-8 text-2xl font-medium text-gray-900 dark:text-white">
        KPM MOE ePenempatan / eOperasi
    </h1>

    <form wire:submit="process">

        <!-- NO KP -->
        <div class="col-span-6 sm:col-span-4">
            <x-label for="name" value="{{ __('NO KP') }}" />
            <x-input id="name" type="text" class="mt-1 block w-full" wire:model="no_kp" required />
            <x-input-error for="name" class="mt-2" />
        </div>

        <br />
        <x-button>
            Call KPM MOE API
        </x-button>

        @if($this->result != null)
        <h1 class="mt-8 text-2xl font-medium text-gray-900 dark:text-white">
            Result
        </h1>

        @dump($this->result)

        @endif


    </form>
</div>
