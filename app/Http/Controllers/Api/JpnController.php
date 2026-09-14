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
        // user_id ialah nombor kad pengenalan pengguna yang membuat semakan.
        $validated = $request->validate([
            'no_kp' => ['required', 'digits:12'],
            'user_id' => ['required', 'digits:12'],
        ]);

        // Panggil servis JPN dan pulangkan response
        return $this->service->callAPI(
            $validated['no_kp'],
            $validated['user_id']
        );
    }
}
