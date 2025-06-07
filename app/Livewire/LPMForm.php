<?php

namespace App\Livewire;

use App\Services\LPMService;
use App\Services\LPMSVKService;
use Livewire\Component;

class LPMForm extends Component
{
    public $no_kp;
    public $tahun;
    public $angka_giliran;

    public $result = null;

    protected $lpmService;


    public function mount()
    {
        #$this->no_kp = $no_kp;
        #$this->tahun = $tahun;
        #$this->angka_giliran = $angka_giliran;
        ray("mount");
        #$this->lpmService = $service;
        #$this->lpmService = app(LPMService::class);
        #ray($this->lpmService);
        #$x = $this->lpmService->callAPI();
        #dd($x);
        $this->no_kp = "880415016011";
        $this->tahun = "2005";

        $this->angka_giliran = "BA105A086";

    }

    public function render()
    {
        return view('livewire.l-p-m-form');
    }

    public function process(LPMSVKService $service)
    {
        ray("call API");
        ray($this->lpmService);
        #$this->lpmService = app(LPMService::class);
        $this->lpmService = $service;
        $x = $this->lpmService->callAPI($this->no_kp, $this->tahun, $this->angka_giliran);
        ray($x);

        $this->result = $x;
    }

}
