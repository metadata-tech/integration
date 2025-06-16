<?php
namespace App\Http\Controllers\Api;
use App\Services\KPTService;
use Illuminate\Http\Request;

class KptController extends BaseController
{
    private KPTService $service;

    public function __construct(KPTService $service)
    {
        $this->service = $service;
    }

    public function esis(Request $request)
    {
        $validated = $request->validate([
            'no_kp' => 'required'
        ]);

        return $this->service->callAPI($validated['no_kp']);
    }

}
