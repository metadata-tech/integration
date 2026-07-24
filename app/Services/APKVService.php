<?php

namespace App\Services;
use App\Models\APILog;
use Illuminate\Support\Facades\Http;

class APKVService {


    public function callAPI($no_kp, $angka_giliran)
    {
        $request_data = [
            "noKadPengenalan" => $no_kp,
            "angkaGiliran" => $angka_giliran,
        ];

        $headers = ['Authorization' => config('services.lpm_apkv.bearer')];
        #dd($headers);
        $url = config('services.lpm_apkv.url');

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
