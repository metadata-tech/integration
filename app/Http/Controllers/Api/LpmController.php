<?php
namespace App\Http\Controllers\Api;

use App\Services\KPMService;
use App\Services\LPMService;
use App\Services\LPMSVKService;
use Illuminate\Http\Request;

class LpmController extends BaseController
{
    private LPMSVKService $service;

    public function __construct(LPMSVKService $service)
    {
        $this->service = $service;
    }

    public function svkspm(Request $request)
    {
        $validated = $request->validate([
            'no_kp' => 'required',
            'tahun' => 'required|integer',
            'angka_giliran' => 'required',
        ]);

        return $this->service->callAPI($validated['no_kp'], $validated['tahun'], $validated['angka_giliran']);
    }

}
