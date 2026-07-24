<?php

namespace App\Livewire;

use App\Services\APKVService;
use App\Services\LPMService;
use App\Services\LPMSVKService;
use Livewire\Component;

class LPMAPKVForm extends Component
{
    public $no_kp;
    public $angka_giliran;

    public $result = null;

    protected $apkvService;


    public function mount()
    {
        ray("mount");

        $this->no_kp = "071201110395";
        $this->angka_giliran = "K701MMTK012";

    }

    public function render()
    {
        return view('livewire.l-p-m-a-p-k-v-form');
    }

    public function process(APKVService $service)
    {
        ray("call API");
        ray($this->apkvService);

        $this->apkvService = $service;
        $x = $this->apkvService->callAPI($this->no_kp, $this->angka_giliran);
        ray($x);
        dd($x);
        $this->result = $x;
    }

}
