<?php

namespace App\Livewire;

use Livewire\Component;

class Counter extends Component
{
    public $count = 0;

    public function render()
    {
        return view('livewire.counter');
    }

    public function increment()
    {
        $this->dispatch('notify', [
            'message' => ' Increment successfully ! '.$this->count++,
            'type' => 'success'
        ]);
    }

    public function decrement()
    {
        $this->dispatch('notify', [
            'message' => ' Decrement successfully ! '.$this->count--,
            'type' => 'error'
        ]);
    }
}
