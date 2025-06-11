<?php

namespace App\Services;
use App\Models\APILog;
use Illuminate\Support\Facades\Http;

class KPTService {

    public function GetToken()
    {
        $request_data = [
            "username" => config('services.kpt_esis.username'),
            "password" => config('services.kpt_esis.password'),
        ];
        ray($request_data);
        //$headers = ['x-api-key' => config('services.lpm.api_key')];
        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] = 'application/json';
        //$headers = [];

        $url = config('services.kpt_esis.url')."/consumer/login";

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

        $log->update([
            'output' => $response->json(),
            'status_code' => $response->getStatusCode()
        ]);

        return $response;
    }
    public function callAPI($no_kp)
    {
        $token = null;
        ray($this->GetToken()->json()['token']);
        #dd('asd');
        if (empty($this->GetToken()->json()['token'])) {
            return null;
        }
        $token = $this->GetToken()->json();
        ray($token);

        $request_data = [
        ];

        $headers = ['Authorization' => 'Bearer '.$token['token']];

        $url = config('services.kpt_esis.url').'/esis/spp/'.$no_kp;

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
