<?php

namespace App\Livewire;

use App\Services\IscafService;
use Livewire\Component;

class IscafForm extends Component
{
    public $result = [];

    public $no_kp;
    public $mykad_no;
    public $cawangan;
    public $aktiviti;

    // Tambahan property nt untuk API
    public $nt = null;
    public $data_nt = null;

    // Daftar fields
    public $no_pemerolehan;
    public $data_nama;
    public $data_ic_no;
    public $data_position_id;
    public $data_volume = false;

    public function mount()
    {
        $this->no_kp = '';
        $this->mykad_no = '';
        $this->cawangan = 'HQ';
        $this->aktiviti = '1';
    }

    public function checkStatus(IscafService $service)
    {
        // Pastikan format nt adalah integer atau null
        $ntValue = ($this->nt !== '' && $this->nt !== null) ? (int)$this->nt : null;
        
        $this->result = $service->checkStatus($this->no_kp, $this->cawangan, $ntValue);
    }

    public function getInfo(IscafService $service)
    {
        // Pastikan format nt adalah integer atau null
        $ntValue = ($this->nt !== '' && $this->nt !== null) ? (int)$this->nt : null;

        $this->result = $service->getInfo($this->no_kp, $this->cawangan, $ntValue);
    }

    public function kemasKini(IscafService $service)
    {
        // Pastikan format nt adalah integer atau null
        $ntValue = ($this->nt !== '' && $this->nt !== null) ? (int)$this->nt : null;

        $this->result = $service->kemasKini(
            $this->mykad_no,
            $this->cawangan,
            $this->no_kp,
            (int) $this->aktiviti,
            $ntValue
        );
        ray($this->result)->blue();
    }

    public function daftar(IscafService $service)
    {
        $dataNtValue = ($this->data_nt !== '' && $this->data_nt !== null) ? (int)$this->data_nt : null;

        $dataArray = [
            'nama' => $this->data_nama,
            'ic_no' => $this->data_ic_no,
            'position_id' => (int) $this->data_position_id,
            'volume' => (bool) $this->data_volume,
        ];

        // Masukkan nt ke dalam array jika pengguna ada buat pilihan
        if ($dataNtValue !== null) {
            $dataArray['nt'] = $dataNtValue;
        }

        $data = [
            $dataArray
        ];

        $this->result = $service->daftar(
            $this->mykad_no,
            $this->cawangan,
            $this->no_pemerolehan,
            $data,
        );
        ray($this->result)->blue();
    }

    public function render()
    {
        return view('livewire.iscaf-form');
    }
}