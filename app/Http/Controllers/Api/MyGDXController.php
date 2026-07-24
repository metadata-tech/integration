<?php
namespace App\Http\Controllers\Api;

use App\Services\KPMService;
use App\Services\LPMService;
use App\Services\LPMSVKService;
use App\Services\MYGDXService;
use App\Services\SOAPService;
use Illuminate\Http\Request;

class MyGDXController extends BaseController
{
    private MYGDXService $service;

    public function __construct(MYGDXService $service)
    {
        $this->service = $service;
    }

    public function muet(Request $request)
    {
        $validated = $request->validate([
            'no_kp' => 'required',
            'tahun' => 'required|integer'
        ]);

        $result = $this->service->getMUET($validated['no_kp'], $validated['tahun']);
        return response()->json($result);
    }

    public function stpm(Request $request)
    {
        $validated = $request->validate([
            'no_kp' => 'required',
            'tahun' => 'required|integer'
        ]);

        $result = $this->service->getSTPM($validated['no_kp'], $validated['tahun']);
        return response()->json($result);
    }
}

