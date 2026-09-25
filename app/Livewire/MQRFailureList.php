<?php

namespace App\Livewire;

use App\Models\MqrMappingFailure;
use App\Models\MqrMappingRun;
use App\Services\MQRService;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class MQRFailureList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $reason = '';

    public array $selected = [];

    public ?array $result = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingReason(): void
    {
        $this->resetPage();
    }

    public function selectPage(): void
    {
        $ids = $this->failures()
            ->getCollection()
            ->filter(fn (MqrMappingFailure $failure): bool => $failure->allowsRegistration())
            ->pluck('id')
            ->map(fn (mixed $id): string => (string) $id)
            ->all();

        $this->selected = array_values(array_unique([
            ...$this->selected,
            ...$ids,
        ]));
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function rebuild(MQRService $service): void
    {
        try {
            $this->result = $service->rebuildFailureList();
            $this->selected = [];
            $this->resetPage();
        } catch (Throwable $e) {
            $this->result = [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function registerInstitutions(MQRService $service): void
    {
        $this->register($service, 'institution');
    }

    public function registerSpecializations(MQRService $service): void
    {
        $this->register($service, 'specialization');
    }

    public function render()
    {
        return view('livewire.m-q-r-failure-list', [
            'run' => $this->latestRun(),
            'failures' => $this->failures(),
        ]);
    }

    private function register(MQRService $service, string $kind): void
    {
        if ($this->selected === []) {
            $this->result = [
                'status' => false,
                'message' => 'Pilih sekurang-kurangnya satu rekod.',
            ];

            return;
        }

        try {
            $this->result = $service->registerMissingReferences(
                $this->selected,
                $kind
            );
            $this->selected = [];
            $this->resetPage();
        } catch (Throwable $e) {
            $this->result = [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function latestRun(): ?MqrMappingRun
    {
        return MqrMappingRun::query()
            ->completed()
            ->latest('completed_at')
            ->first();
    }

    private function failures()
    {
        $run = $this->latestRun();

        if ($run === null) {
            return MqrMappingFailure::query()
                ->whereRaw('1 = 0')
                ->paginate(20);
        }

        return MqrMappingFailure::query()
            ->where('run_id', $run->id)
            ->when($this->reason === 'institution', function ($query): void {
                $query->where('missing_institution', true)
                    ->where('missing_specialization', false)
                    ->where('ambiguous_institution', false);
            })
            ->when($this->reason === 'specialization', function ($query): void {
                $query->where('missing_specialization', true)
                    ->where('missing_institution', false)
                    ->where('ambiguous_institution', false);
            })
            ->when($this->reason === 'both', function ($query): void {
                $query->where('missing_institution', true)
                    ->where('missing_specialization', true)
                    ->where('ambiguous_institution', false);
            })
            ->when($this->reason === 'ambiguous', function ($query): void {
                $query->where('ambiguous_institution', true);
            })
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(function ($query) use ($term): void {
                    $query->where('kod_mqa', 'like', $term)
                        ->orWhere('nama_institusi', 'like', $term)
                        ->orWhere('nama_kelayakan', 'like', $term);
                });
            })
            ->orderBy('id')
            ->paginate(20);
    }
}
