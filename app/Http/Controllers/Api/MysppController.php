<?php

namespace App\Http\Controllers\Api;

use App\Services\MysppDokumenService;
use Illuminate\Http\Request;

class MysppController extends BaseController
{
    public function __construct(private MysppDokumenService $service)
    {
    }

    public function dokumen(Request $request)
    {
        $validated = $request->validate([
            'id_pemohon' => ['required', 'regex:/^\d+$/'],
            'jenis' => ['required', 'regex:/^[A-Za-z0-9_]+$/'],
        ]);

        return $this->service->streamDokumen(
            $validated['id_pemohon'],
            strtoupper($validated['jenis'])
        );
    }
}
