<?php
namespace App\Http\Controllers\Api;

use App\Services\IscafService;
use Illuminate\Http\Request;

class IscafController extends BaseController
{
    private IscafService $service;

    public function __construct(IscafService $service)
    {
        $this->service = $service;
    }

    /**
     * API 1: Semakan status fail wujud
     */
    public function status(Request $request)
    {
        $validated = $request->validate([
            'no_kp' => 'required',
            'cawangan' => 'required|in:HQ,S,Q',
            'nt' => 'nullable|integer|in:0,1',
        ]);

        return response()->json(
            $this->service->checkStatus($validated['no_kp'], $validated['cawangan'], $validated['nt'] ?? null)
        );
    }

    /**
     * API 2: Permohonan wujud / buka fail
     */
    public function daftar(Request $request)
    {
        $validated = $request->validate([
            'mykad_no' => 'required',
            'cawangan' => 'required|in:HQ,S,Q',
            'no_pemerolehan' => 'sometimes|nullable|string',
            'data' => 'required|array',
            'data.*.nama' => 'required|string',
            'data.*.ic_no' => 'required',
            'data.*.position_id' => 'required|integer',
            'data.*.volume' => 'sometimes|boolean',
            'data.*.nt' => 'nullable|integer|in:0,1',
        ]);

        return response()->json(
            $this->service->daftar(
                $validated['mykad_no'],
                $validated['cawangan'],
                $validated['no_pemerolehan'] ?? null,
                $validated['data']
            )
        );
    }

    /**
     * API 3: Maklumat fail anggota (informasi)
     */
    public function informasi(Request $request)
    {
        $validated = $request->validate([
            'no_kp' => 'required',
            'cawangan' => 'required|in:HQ,S,Q',
            'nt' => 'nullable|integer|in:0,1',
        ]);

        return response()->json(
            $this->service->getInfo($validated['no_kp'], $validated['cawangan'], $validated['nt'] ?? null)
        );
    }

    /**
     * API 4: Kemaskini fail
     */
    public function kemasKini(Request $request)
    {
        $validated = $request->validate([
            'mykad_no' => 'required',
            'cawangan' => 'required|in:HQ,S,Q',
            'ic_no' => 'required',
            'aktiviti' => 'required|integer|in:1,2',
            'nt' => 'nullable|integer|in:0,1',
        ]);

        return response()->json(
            $this->service->kemasKini(
                $validated['mykad_no'],
                $validated['cawangan'],
                $validated['ic_no'],
                $validated['aktiviti'],
                $validated['nt'] ?? null
            )
        );
    }
}