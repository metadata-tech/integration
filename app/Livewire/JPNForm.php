<?php

namespace App\Livewire;

use App\Services\JPNService;
use Livewire\Component;

class JPNForm extends Component
{
    public $no_kp;

    public $result = null;

    public function mount()
    {
        ray("mount");
        $this->no_kp = "021009031069";
    }

    public function render()
    {
        return view('livewire.j-p-n-form');
    }

    public function process(JPNService $service)
    {
        ray("call API");

        $this->jpnService = $service;
        $x = $this->jpnService->callAPI($this->no_kp);
        ray($x);

        $this->result = $x;
    }
}
