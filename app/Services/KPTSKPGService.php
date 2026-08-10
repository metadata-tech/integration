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
        $tokenResponse = $this->GetToken()->json();
        $token = $tokenResponse['data']['auth_Token'] ?? null;

        if (empty($token)) {
            return null;
        }

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

        $responseData = $response->json();

        $log->update([
            'output' => $responseData,
            'status_code' => $response->getStatusCode()
        ]);

        return is_array($responseData)
            ? $this->removeDuplicateData($responseData)
            : $responseData;
    }

    private function removeDuplicateData(array $responseData): array
    {
        if (
            ! isset($responseData['data'])
            || ! is_array($responseData['data'])
            || ! array_is_list($responseData['data'])
        ) {
            return $responseData;
        }

        $seen = [];
        $uniqueData = [];

        foreach ($responseData['data'] as $record) {
            $fingerprint = hash(
                'sha256',
                serialize($this->canonicalizeForComparison($record))
            );

            if (isset($seen[$fingerprint])) {
                continue;
            }

            $seen[$fingerprint] = true;
            $uniqueData[] = $record;
        }

        $responseData['data'] = $uniqueData;

        if (array_key_exists('message', $responseData)) {
            $responseData['message'] = $this->updateTotalRowMessage(
                $responseData['message'],
                count($uniqueData)
            );
        }

        return $responseData;
    }

    private function canonicalizeForComparison(mixed $value): mixed
    {
        if (is_string($value)) {
            return str_replace(["\r\n", "\r"], "\n", trim($value));
        }

        if (! is_array($value)) {
            return $value;
        }

        $canonical = [];

        foreach ($value as $key => $item) {
            $canonical[$key] = $this->canonicalizeForComparison($item);
        }

        if (! array_is_list($canonical)) {
            ksort($canonical, SORT_STRING);
        }

        return $canonical;
    }

    private function updateTotalRowMessage(mixed $messages, int $total): mixed
    {
        if (! is_array($messages)) {
            return $messages;
        }

        return array_map(
            static function (mixed $message) use ($total): mixed {
                if (
                    is_string($message)
                    && preg_match(
                        '/^\s*TOTAL\s+ROW\s*:\s*\d+\s*$/i',
                        $message
                    ) === 1
                ) {
                    return "TOTAL ROW : {$total}";
                }

                return $message;
            },
            $messages
        );
    }

    public function process($ic_no)
    {

    }


}
