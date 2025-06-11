<?php

namespace App\Livewire;

use App\Services\KPTService;
use Livewire\Component;

class KPTForm extends Component
{
    public $no_kp;

    public $result = null;

    public function mount()
    {
        ray("mount");
        $this->no_kp = "890927075102";
    }

    public function render()
    {
        return view('livewire.k-p-t-form');
    }

    public function process(KPTService $service)
    {
        ray("call API");

        $this->kptService = $service;
        $x = $this->kptService->callAPI($this->no_kp);
        ray($x);

        $this->result = $x;
    }
}
