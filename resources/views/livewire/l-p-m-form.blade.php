<div>
    {{-- Knowing others is intelligence; knowing yourself is true wisdom. --}}

    <h1 class="mt-8 text-2xl font-medium text-gray-900 dark:text-white">
        Integrasi Lembaga Peperiksaan SVK SPM
    </h1>

    <form wire:submit="process">

        <!-- NO KP -->
        <div class="col-span-6 sm:col-span-4">
            <x-label for="name" value="{{ __('NO KP') }}" />
            <x-input id="name" type="text" class="mt-1 block w-full" wire:model="no_kp" required />
            <x-input-error for="name" class="mt-2" />
        </div>

        <!-- Tahun -->
        <div class="col-span-6 sm:col-span-4">
            <x-label for="tahun" value="{{ __('Tahun') }}" />
            <x-input id="tahun" type="text" class="mt-1 block w-full" wire:model="tahun" required />
            <x-input-error for="tahun" class="mt-2" />
        </div>

        <!-- Angka Giliran -->
        <div class="col-span-6 sm:col-span-4">
            <x-label for="angka_giliran" value="{{ __('Angka Giliran') }}" />
            <x-input id="angka_giliran" type="text" class="mt-1 block w-full" wire:model="angka_giliran" />
            <x-input-error for="angka_giliran" class="mt-2" />
        </div>

        <br />
        <x-button>
            Call Lembaga Peperiksaan API
        </x-button>

        @if($this->result != null)

            @if(isset($this->result["status"]) && $this->result["status"] == false)
                <div class="text-red-500">
                    {{ $this->result["message"] }}
                </div>
            @else
                <h1 class="mt-8 text-2xl font-medium text-gray-900 dark:text-white">
                    Result
                </h1>

                @dump($this->result)

                Pelajar : {{ $this->result[0]["pelajar"] }} <br>
                KP : {{ $this->result[0]["kp"] }} <br>
                Tahun : {{ $this->result[0]["tahun"] }} <br>
                Ringkasan : {{ $this->result[0]["ringkasan"] }} <br>
                AKG : {{ $this->result[0]["akg"] }} <br>

                <table class="table-auto">
                    <thead>
                        <th>NAMA</th>
                        <th>KOD</th>
                        <th>GRED</th>
                    </thead>
                    <tbody>
                @foreach($this->result[0]["data"] as $v)
                    <tr>
                        <td>{{ $v["nama"] }}</td>
                        <td>{{$v["kod"]}}</td>
                        <td>{{$v["gred"]}}</td>
                    </tr>


                @endforeach
                    </tbody>
                </table>

            @endif
        @endif


    </form>
</div>
