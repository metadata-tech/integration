<?php
namespace App\Http\Controllers\Api;
use App\Services\KPTService;
use App\Services\KPTSKPGService;
use Illuminate\Http\Request;

class KptController extends BaseController
{
    private KPTService $service;
    private KPTSKPGService $skpgService;

    public function __construct(KPTService $service, KPTSKPGService $skpgService)
    {
        $this->service = $service;
        $this->skpgService = $skpgService;
    }

    public function esis(Request $request)
    {
        $validated = $request->validate([
            'no_kp' => 'required'
        ]);

        return $this->service->callAPI($validated['no_kp']);
    }

    public function skpg(Request $request)
    {
        $validated = $request->validate([
            'no_kp' => 'required'
        ]);

        return $this->skpgService->callAPI($validated['no_kp']);
    }

}

