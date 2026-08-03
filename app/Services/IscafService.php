<?php

namespace App\Services;

use App\Models\APILog;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class IscafService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $statusMethod;

    /**
     * Last upstream HTTP status from the most recent call.
     */
    protected int $lastHttpStatus = 200;

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

    public function getLastHttpStatus(): int
    {
        return $this->lastHttpStatus;
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

        if ($nt !== null) {
            $url .= "/{$nt}";
        }

        $log = $this->logRequest($this->statusMethod, $url, [
            'ic_no' => $ic_no,
            'cawangan' => $cawangan,
            'nt' => $nt,
        ]);

        $client = $this->client();

        $response = $this->statusMethod === 'GET'
            ? $client->get($url)
            : $client->post($url);

        return $this->finalize($log, $response);
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

        if ($no_pemerolehan) {
            $payload['no_pemerolehan'] = $no_pemerolehan;
        }

        $log = $this->logRequest('POST', $url, $payload);

        $response = $this->client()
            ->asJson()
            ->post($url, $payload);

        return $this->finalize($log, $response);
    }

    /**
     * API 3: Maklumat fail anggota (informasi)
     * GET /informasi/{ic_no}/{cawangan}/{nt?}
     */
    public function getInfo(string $ic_no, string $cawangan, ?int $nt = null): array
    {
        $url = "{$this->baseUrl}/informasi/{$ic_no}/{$cawangan}";

        if ($nt !== null) {
            $url .= "/{$nt}";
        }

        $log = $this->logRequest('GET', $url, [
            'ic_no' => $ic_no,
            'cawangan' => $cawangan,
            'nt' => $nt,
        ]);

        $response = $this->client()->get($url);

        return $this->finalize($log, $response);
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

        if ($nt !== null) {
            $payload['nt'] = $nt;
        }

        $log = $this->logRequest('POST', $url, $payload);

        $response = $this->client()
            ->asJson()
            ->post($url, $payload);

        return $this->finalize($log, $response);
    }

    protected function client(): PendingRequest
    {
        return Http::withOptions(['verify' => false])
            ->acceptJson()
            ->withHeaders(['X-Api-Key' => $this->apiKey]);
    }

    protected function finalize(APILog $log, Response $response): array
    {
        $this->lastHttpStatus = $response->status();
        $result = $this->normalizeResponse($response);

        if (function_exists('ray')) {
            ray($result)->blue();
        }

        $this->logResponse($log, $response, $result);

        return $result;
    }

    /**
     * Normalize upstream body into the iSCAF {status, message, data?} shape.
     * Handles JSON success/error, throttle JSON, and HTML 429 pages (debugbar).
     */
    protected function normalizeResponse(Response $response): array
    {
        $json = $response->json();

        if (is_array($json) && (isset($json['status']) || isset($json['data']))) {
            return $json;
        }

        if ($response->status() === 429 || $this->bodyLooksThrottled($response)) {
            return [
                'status' => 'error',
                'message' => 'Too Many Attempts. Please retry later.',
            ];
        }

        if (is_array($json) && isset($json['message'])) {
            return [
                'status' => 'error',
                'message' => $json['message'],
            ];
        }

        return [
            'status' => 'error',
            'message' => 'No response from iSCAF',
        ];
    }

    protected function bodyLooksThrottled(Response $response): bool
    {
        $body = $response->body();

        return $body !== '' && (
            str_contains($body, 'Too Many Attempts')
            || str_contains($body, 'Too Many Requests')
            || str_contains($body, 'ThrottleRequestsException')
        );
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

    protected function logResponse(APILog $log, Response $response, array $normalized): void
    {
        $log->update([
            'output' => $normalized,
            'status_code' => $response->status(),
        ]);
    }
}
