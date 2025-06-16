<?php

namespace App\Livewire;

use App\Services\LPMSVKService;
use Livewire\Component;

class LPMSVKSPMUlanganForm extends Component
{
    public $no_kp;
    public $tahun;
    public $angka_giliran;

    public $result = null;

    protected $lpmService;


    public function mount()
    {
        $this->no_kp = "";
        $this->tahun = "";
        $this->angka_giliran = "";
    }

    public function render()
    {
        return view('livewire.lpm_svk_spm_ulangan_form');
    }

    public function process(LPMSVKService $service)
    {
        ray("call API");
        ray($this->lpmService);

        $this->lpmService = $service;
        $x = $this->lpmService->callAPISPMUlangan($this->no_kp, $this->tahun, $this->angka_giliran);
        ray($x);

        $this->result = $x;
    }

}
