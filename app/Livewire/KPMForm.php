<?php

namespace App\Livewire;

use App\Service\KPMService;
use App\Service\LPMService;
use Livewire\Component;

class KPMForm extends Component
{
    public $no_kp;

    public $result = null;

    protected $kpmService;


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
        $this->no_kp = "700110105012";

    }

    public function render()
    {
        return view('livewire.k-p-m-form');
    }

    public function process(KPMService $service)
    {
        ray("call API");
        ray($this->kpmService);
        #$this->lpmService = app(LPMService::class);
        $this->kpmService = $service;
        $x = $this->kpmService->callAPI($this->no_kp);
        ray($x);

        $this->result = $x;
    }

}
