<?php

namespace App\Livewire;

use App\Services\IscafService;
use Livewire\Component;

class IscafForm extends Component
{
    public $result = [];

    public $no_kp;
    public $activity;
    public $branch_id;
    public $user_id;

    public function mount()
    {

        $this->no_kp = '870824495747';
        $this->activity = '1';
        $this->branch_id = '2';
        $this->user_id = '1';
    }

    public function process(IscafService $service)
    {
        $result = $service->process($this->no_kp, $this->activity, $this->branch_id, $this->user_id);
        #dump($this->result);
        ray($this->result)->blue();

        $this->result = $result;
    }

    public function render()
    {
        return view('livewire.iscaf-form');
    }
}
