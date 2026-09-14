<?php

namespace App\Livewire;

use App\Services\JPNService;
use Livewire\Component;

class JPNForm extends Component
{
    public $no_kp;

    public $user_id;

    public $result = null;

    public function mount()
    {
        $this->no_kp = '021009031069';
        $this->user_id = '';
    }

    public function render()
    {
        return view('livewire.j-p-n-form');
    }

    public function process(JPNService $service)
    {
        $validated = $this->validate([
            'no_kp' => ['required', 'digits:12'],
            'user_id' => ['required', 'digits:12'],
        ]);

        $this->result = $service->callAPI(
            $validated['no_kp'],
            $validated['user_id']
        );
    }
}
