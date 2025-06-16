<?php
namespace App\Http\Controllers\Api;

use App\Services\KPMService;
use Illuminate\Http\Request;

class KpmController extends BaseController
{
    private KPMService $service;

    public function __construct(KPMService $service)
    {
        $this->service = $service;
    }

    public function penempatanOperasi(Request $request)
    {
        $validated = $request->validate([
            'no_kp' => 'required'
        ]);

        return $this->service->callAPI($validated['no_kp']);
    }

}
