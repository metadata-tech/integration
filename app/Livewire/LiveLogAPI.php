<?php

namespace App\Livewire;

use App\Models\APILog;
use Livewire\Component;

class LiveLogAPI extends Component
{
    public $items = [];

    public function render()
    {
        $this->items = APILog::query()->orderBy('id', 'desc')->get();
        return view('livewire.live-log-api');
    }
}
