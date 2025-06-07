<?php

namespace App\Services;
use App\Models\APILog;
use App\Models\iscaf\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KPMService {


    public function callAPI($no_kp)
    {
        $request_data = [
            "nric" => $no_kp

        ];

        $headers = ['APIKEY' => config('services.kpm.api_key')];

        $url = config('services.kpm.url');

        $log = APILog::create([
            'method' => 'GET',
            'input' => $request_data,
            'headers' => $headers,
            'url' => $url,
            'user_id' => auth()->id()
        ]);

        $response = Http::
        withOptions(['verify' => false,])
            ->withHeaders($headers)
            ->get($url, $request_data);


        ray($response);

        $log->update([
            'output' => $response->json(),
            'status_code' => $response->getStatusCode()
        ]);

        return $response->json();
    }

    public function process($ic_no)
    {

    }


}
