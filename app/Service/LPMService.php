<?php

namespace App\Service;
use App\Models\APILog;
use Illuminate\Support\Facades\Http;

class LPMService {


    public function callAPI($no_kp, $tahun)
    {
        $request_data = [
            "request" => [
                "action" => "keputusan_spm",
                "dataset" => [
                    "no_kp" => $no_kp,
                    "tahun" => $tahun,
                ]
            ],
        ];

        $headers = ['x-api-key' => config('services.lpm.api_key')];

        $url = config('services.lpm.url');

        $log = APILog::create([
            'method' => 'POST',
            'input' => $request_data,
            'headers' => $headers,
            'url' => $url,
            'user_id' => auth()->id()
        ]);

        $response = Http::
        withOptions(['verify' => false,])
            ->withHeaders($headers)
            ->post($url, $request_data);


        ray($response);

        $log->update([
            'output' => $response->json(),
            'status_code' => $response->getStatusCode()
        ]);

        return $response->json();
    }


}
