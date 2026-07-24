<?php

namespace App\Services;

use App\Models\APILog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class IscafService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $statusMethod;

    /**
     * Branch ID (integer) to cawangan code mapping.
     */
    protected array $branchMap = [
        1 => 'HQ',
        2 => 'S',
        3 => 'Q',
    ];

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.iscaf.url'), '/');
        $this->apiKey = config('services.iscaf.api_key');
        $this->statusMethod = strtoupper(config('services.iscaf.status_method', 'POST'));
    }

    /**
     * Map integer branch_id to cawangan string.
     */
    public function mapCawangan(int|string $branch_id): string
    {
        if (in_array($branch_id, ['HQ', 'S', 'Q'], true)) {
            return $branch_id;
        }

        return $this->branchMap[(int) $branch_id] ?? 'HQ';
    }

    /**
     * API 1: Semakan status fail wujud
     * Method depends on environment. Prod doc says POST, UAT currently uses GET.
     */
    public function checkStatus(string $ic_no, string $cawangan, ?int $nt = null): array
    {
        $url = "{$this->baseUrl}/status/{$ic_no}/{$cawangan}";
        
        // Append nt jika parameter disertakan (URL Param)
        if ($nt !== null) {
            $url .= "/{$nt}";
        }

        $log = $this->logRequest($this->statusMethod, $url, [
            'ic_no' => $ic_no, 
            'cawangan' => $cawangan,
            'nt' => $nt
        ]);

        $client = Http::withOptions(['verify' => false])
            ->withHeaders(['X-Api-Key' => $this->apiKey]);

        $response = $this->statusMethod === 'GET'
            ? $client->get($url)
            : $client->post($url);

        ray($response->json())->blue();

        $this->logResponse($log, $response);

        return $response->json() ?? ['status' => 'error', 'message' => 'No response from iSCAF'];
    }

    /**
     * API 2: Permohonan wujud / buka fail (daftar)
     * POST /daftar
     */
    public function daftar(string $mykad_no, string $cawangan, ?string $no_pemerolehan, array $data): array
    {
        $url = "{$this->baseUrl}/daftar";
        $sanitizedData = array_map(function (array $item): array {
            if (array_key_exists('volume', $item) && in_array($item['volume'], [true, 1, '1'], true)) {
                $item['volume'] = true;
            } elseif (array_key_exists('volume', $item)) {
                unset($item['volume']);
            }

            return $item;
        }, $data);

        $payload = [
            'mykad_no' => $mykad_no,
            'cawangan' => $cawangan,
            'data' => $sanitizedData,
        ];

        // Memasukkan no_pemerolehan jika wujud
        if ($no_pemerolehan) {
            $payload['no_pemerolehan'] = $no_pemerolehan;
        }

        $log = $this->logRequest('POST', $url, $payload);

        $response = Http::withOptions(['verify' => false])
            ->acceptJson()
            ->asJson()
            ->withHeaders(['X-Api-Key' => $this->apiKey])
            ->post($url, $payload);

        ray($response->json())->blue();

        $this->logResponse($log, $response);

        return $response->json() ?? ['status' => 'error', 'message' => 'No response from iSCAF'];
    }

    /**
     * API 3: Maklumat fail anggota (informasi)
     * GET /informasi/{ic_no}/{cawangan}/{nt?}
     */
    public function getInfo(string $ic_no, string $cawangan, ?int $nt = null): array
    {
        $url = "{$this->baseUrl}/informasi/{$ic_no}/{$cawangan}";
        
        // Append nt jika parameter disertakan (URL Param)
        if ($nt !== null) {
            $url .= "/{$nt}";
        }

        $log = $this->logRequest('GET', $url, [
            'ic_no' => $ic_no, 
            'cawangan' => $cawangan,
            'nt' => $nt
        ]);

        $response = Http::withOptions(['verify' => false])
            ->withHeaders(['X-Api-Key' => $this->apiKey])
            ->get($url);

        ray($response->json())->blue();

        $this->logResponse($log, $response);

        return $response->json() ?? ['status' => 'error', 'message' => 'No response from iSCAF'];
    }

    /**
     * API 4: Kemaskini fail (kemas-kini)
     * POST /kemas-kini
     */
    public function kemasKini(string $mykad_no, string $cawangan, string $ic_no, int $aktiviti, ?int $nt = null): array
    {
        $url = "{$this->baseUrl}/kemas-kini";

        $payload = [
            'mykad_no' => $mykad_no,
            'cawangan' => $cawangan,
            'ic_no' => $ic_no,
            'aktiviti' => $aktiviti,
        ];

        // Tambah nt di dalam payload body jika disertakan
        if ($nt !== null) {
            $payload['nt'] = $nt;
        }

        $log = $this->logRequest('POST', $url, $payload);

        $response = Http::withOptions(['verify' => false])
            ->withHeaders(['X-Api-Key' => $this->apiKey])
            ->post($url, $payload);

        ray($response->json())->blue();

        $this->logResponse($log, $response);

        return $response->json() ?? ['status' => 'error', 'message' => 'No response from iSCAF'];
    }

    protected function logRequest(string $method, string $url, array $input): APILog
    {
        return APILog::create([
            'method' => $method,
            'input' => $input,
            'headers' => ['X-Api-Key' => '***'],
            'url' => $url,
            'user_id' => Auth::id(),
        ]);
    }

    protected function logResponse(APILog $log, $response): void
    {
        $log->update([
            'output' => $response->json(),
            'status_code' => $response->status(),
        ]);
    }
}