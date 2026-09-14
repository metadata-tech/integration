<?php

namespace App\Services;

use App\Models\APILog;
use Illuminate\Support\Facades\Http;

class JPNService
{
    public function callAPI($no_kp, $user_id)
    {
        $request_data = [
            'ic_number' => $no_kp,
            'user_id' => $user_id,
        ];

        $headers = ['X-API-KEY' => config('services.jpn.api_key')];

        $url = config('services.jpn.url');

        $log = APILog::create([
            'method' => 'GET',
            'input' => $request_data,
            'headers' => $headers,
            'url' => $url,
            'user_id' => auth()->id(),
        ]);

        $response = Http::withOptions(['verify' => false])
            ->withHeaders($headers)
            ->get($url, $request_data);

        $log->update([
            'output' => $response->json(),
            'status_code' => $response->getStatusCode(),
        ]);

        return $response->json();
    }

    public function process($ic_no) {}
}
