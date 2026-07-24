<?php

namespace App\Livewire;

use App\Services\KPMService;
use App\Services\MYGDXService;
use Livewire\Component;

class MuetForm extends Component
{
    public $no_kp;
    public $tahun;

    public $result = null;

    protected $mygdxService;


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
        $this->no_kp = "021009031069";
        $this->tahun = "2021";

    }

    public function render()
    {
        return view('livewire.muet-form');
    }

    public function process(MYGDXService $service)
    {
        ray("call API");
        ray($this->mygdxService);
        #$this->lpmService = app(LPMService::class);
        $this->mygdxService = $service;
        $x = $this->mygdxService->getMUET($this->no_kp, $this->tahun);
        ray($x);

        $this->result = $x;
    }

}
