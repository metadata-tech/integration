<?php

namespace App\Livewire;

use App\Models\APILog;
use Livewire\Component;
use Livewire\WithPagination;

class LiveLogApi extends Component
{
    use WithPagination;

    //public $items = [];
    public $startDate = '';
    public $endDate = '';
    public $perPage = 15;

    protected $queryString = [
        //'startDate',
        //'endDate'
    ];

    public function mount()
    {
        // Set default dates if empty
        if (empty($this->startDate)) {
            $this->startDate = now()->startOfDay()->format('Y-m-d');
        }
        if (empty($this->endDate)) {
            $this->endDate = now()->endOfDay()->format('Y-m-d');
        }
    }

    public function updatedStartDate()
    {
        $this->resetPage();
    }

    public function updatedEndDate()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['startDate', 'endDate']);
        $this->resetPage();
    }

    public function render()
    {
        $query = APILog::query()
            ->when($this->startDate, function($query) {
                return $query->whereDate('created_at', '>=', $this->startDate);
            })
            ->when($this->endDate, function($query) {
                return $query->whereDate('created_at', '<=', $this->endDate);
            })
            ->with('user')
            ->orderBy('id', 'desc');
        #$query = APILog::query();
        ray($query->paginate(10))->purple();;
        #$this->items = $query->paginate($this->perPage);

        return view('livewire.live-log-api',[
            'logs' => $query->paginate($this->perPage)
        ]);
    }
}
