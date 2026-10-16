<?php

namespace App\Livewire;

use App\Services\MYGDXService;
use Livewire\Component;

class StpmForm extends Component
{
    public string $no_kp = '';

    public string $tahun = '';

    public ?array $result = null;

    public function process(MYGDXService $service): void
    {
        $this->result = null;

        $validated = $this->validate([
            'no_kp' => ['required', 'string', 'max:20'],
            'tahun' => ['required', 'digits:4'],
        ]);

        try {
            $this->result = $service->getSTPM($validated['no_kp'], $validated['tahun']);
        } catch (\Throwable $exception) {
            report($exception);

            $this->result = [
                'status' => 'error',
                'message' => 'Semakan STPM gagal. Sila cuba lagi.',
            ];
        }
    }

    public function render()
    {
        return view('livewire.stpm-form');
    }
}
