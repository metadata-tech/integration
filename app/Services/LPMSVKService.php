<?php

namespace App\Services;
use App\Models\APILog;
use Illuminate\Support\Facades\Http;

class LPMSVKService {


    public function GetToken()
    {
        $request_data = [
                "email" => config('services.lpm_svk.email'),
                "password" => config('services.lpm_svk.password'),
        ];
        ray($request_data);
        //$headers = ['x-api-key' => config('services.lpm.api_key')];
        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] = 'application/json';
        //$headers = [];

        $url = config('services.lpm_svk.url')."/api/auth/login";

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


        ray($response->json());

        return $response;
    }

    public function callAPI($no_kp, $tahun, $akg)
    {
        $token = $this->GetToken()->json()['user']['_token'];
        ray($token);
        #dd($token);
        $request_data = [
            "tahun" => $tahun,
            "nic" => $no_kp,
            "akg" => $akg,
        ];
        ray($request_data);
        $headers = ['Authorization' => 'Bearer '.$token];

        $url = config('services.lpm_svk.url')."/api/verification/spm";

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

    public function callAPISPMUlangan($no_kp, $tahun, $akg)
    {
        $token = $this->GetToken()->json()['user']['_token'];
        ray($token);
        #dd($token);
        $request_data = [
            "tahun" => $tahun,
            "nic" => $no_kp,
            "akg" => $akg,
        ];
        ray($request_data);
        $headers = ['Authorization' => 'Bearer '.$token];

        $url = config('services.lpm_svk.url')."/api/verification/spmu";

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
