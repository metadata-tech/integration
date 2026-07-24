<?php

namespace App\Livewire;

use App\Services\KPTService;
use App\Services\KPTSKPGService;
use Livewire\Component;

class KPTSKPGForm extends Component
{
    public $no_kp;

    public $result = null;

    public function mount()
    {
        ray("mount");
        $this->no_kp = "880415016011";
    }

    public function render()
    {
        return view('livewire.k-p-t-s-k-p-g-form');
    }

    public function process(KPTSKPGService $service)
    {
        ray("call API");

        $this->kptService = $service;
        $x = $this->kptService->callAPI($this->no_kp);
        ray($x);

        $this->result = $x;
    }
}
