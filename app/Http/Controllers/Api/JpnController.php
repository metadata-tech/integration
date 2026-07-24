<?php

namespace App\Http\Controllers\Api;

use App\Services\JPNService;
use Illuminate\Http\Request;

class JpnController extends BaseController
{
    private JPNService $service;

    public function __construct(JPNService $service)
    {
        $this->service = $service;
    }

    public function semakMaklumat(Request $request)
    {
        // Validasi input untuk memastikan 'no_kp' dihantar oleh client
        $validated = $request->validate([
            'no_kp' => 'required'
        ]);

        // Panggil servis JPN dan pulangkan response
        return $this->service->callAPI($validated['no_kp']);
    }
}