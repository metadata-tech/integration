<?php
namespace App\Http\Controllers\Api;

use App\Services\KPMService;
use App\Services\LPMService;
use App\Services\LPMSVKService;
use App\Services\APKVService;
use Illuminate\Http\Request;

class LpmController extends BaseController
{
    private LPMSVKService $service;
    private APKVService $apkvService;

    public function __construct(LPMSVKService $service, APKVService $apkvService)
    {
        $this->service = $service;
        $this->apkvService = $apkvService;
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

    public function svkspmulangan(Request $request)
    {
        $validated = $request->validate([
            'no_kp' => 'required',
            'tahun' => 'required|integer',
            'angka_giliran' => 'required',
        ]);

        return $this->service->callAPISPMUlangan($validated['no_kp'], $validated['tahun'], $validated['angka_giliran']);
    }

    public function svm(Request $request)
    {
        $validated = $request->validate([
            'no_kp' => 'required',
            'angka_giliran' => 'required',
        ]);

        return $this->apkvService->callAPI($validated['no_kp'], $validated['angka_giliran']);
    }

}
