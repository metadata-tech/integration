<?php

namespace App\Livewire;

use App\Services\IscafService;
use Livewire\Component;

class IscafSearch extends Component
{
    public $result = [];

    public $no_kp;
    public $cawangan;

    public function mount()
    {
        $this->no_kp = '';
        $this->cawangan = 'HQ';
    }

    public function render()
    {
        return view('livewire.iscaf-search');
    }

    public function process(IscafService $service)
    {
        ray($this->no_kp)->green();

        $this->result = $service->getInfo($this->no_kp, $this->cawangan);

        ray($this->result)->blue();
    }
}
