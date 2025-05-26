<div>
    {{-- Knowing others is intelligence; knowing yourself is true wisdom. --}}

    <h1 class="mt-8 text-2xl font-medium text-gray-900 dark:text-white">
        Integrasi Dalaman iscaf
    </h1>

    <form wire:submit="process">

        <!-- NO KP -->
        <div class="col-span-6 sm:col-span-4">
            <x-label for="no_kp" value="{{ __('NO KP') }}" />
            <x-input id="no_kp" type="text" class="mt-1 block w-full" wire:model="no_kp" required />
            <x-input-error for="no_kp" class="mt-2" />
        </div>

        <!-- Activity -->
        <div class="col-span-6 sm:col-span-4">

            <div class="col-span-4">

                <x-input id="masuk" type="radio" wire:model="activity" value="1" name="activity" />
                <label for="masuk" class="font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Masuk') }}</label>



                <x-input id="keluar" type="radio" wire:model="activity" value="2" name="activity" />
                <label for="keluar" class="font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Keluar') }}</label>
            </div>






            <x-input-error for="activity" class="mt-2" />
        </div>

        <!-- Branch -->
        <div class="col-span-6 sm:col-span-4">
            <x-label for="branch_id" value="{{ __('Branch') }}" />
            <x-input id="branch_id" type="text" class="mt-1 block w-full" wire:model="branch_id" />
            <x-input-error for="branch_id" class="mt-2" />
        </div>

        <!-- User -->
        <div class="col-span-6 sm:col-span-4">
            <x-label for="user_id" value="{{ __('User Id') }}" />
            <x-input id="user_id" type="text" class="mt-1 block w-full" wire:model="user_id" />
            <x-input-error for="user_id" class="mt-2" />
        </div>

        <br />
        <x-button>
            Call iscaf
        </x-button>

        @if($this->result != null)
            <h1 class="mt-8 text-2xl font-medium text-gray-900 dark:text-white">
                Result
            </h1>

            <h2 class="mt-8 text-2xl font-medium text-gray-900 dark:text-white">
            Table : app
            </h2>
            @dump($this->result->attributesToArray())

            <h2 class="mt-8 text-2xl font-medium text-gray-900 dark:text-white">
            Table : file
            </h2>
            @dump($this->result->file->attributesToArray())

            <h2 class="mt-8 text-2xl font-medium text-gray-900 dark:text-white">
            Table : history_document_number
            </h2>
            @foreach($this->result->file->historyDocumentNumber->toArray() as $v)
                @dump($v);
            @endforeach



        @endif


    </form>
</div>
