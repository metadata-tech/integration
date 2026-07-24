<?php

namespace App\Services;
use App\Models\APILog;
use Illuminate\Support\Facades\Http;

class KPTSKPGService {

    public function GetToken()
    {
        $request_data = [
            "username" => config('services.kpt_skpg.username'),
            "password" => config('services.kpt_skpg.password'),
        ];

        //$headers = ['x-api-key' => config('services.lpm.api_key')];
        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] = 'application/json';
        //$headers = [];

        $url = config('services.kpt_skpg.url')."/Login";

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


        #ray($response->json());

        $log->update([
            'output' => $response->json(),
            'status_code' => $response->getStatusCode()
        ]);

        return $response;
    }
    public function callAPI($no_kp)
    {
        $token = null;
        #ray($this->GetToken()->json()['token']);
        #dd($this->GetToken()->json()['data']['auth_Token']);;
        if (empty($this->GetToken()->json()['data']['auth_Token'])) {
            return null;
        }
        $token = $this->GetToken()->json()['data']['auth_Token'];
        ray($token);

        $request_data = [
            "ic_no" => $no_kp,
        ];

        $headers = ['Authorization' => 'Bearer '.$token];

        $url = config('services.kpt_skpg.url').'/MOHE/Student';

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
        ray($response->json());

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
