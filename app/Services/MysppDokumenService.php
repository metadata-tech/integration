<?php

namespace App\Services;

use App\Models\APILog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class MysppDokumenService
{
    public function streamDokumen(string $idPemohon, string $jenis): Response
    {
        $url = (string) config('services.myspp.url');
        $apiKey = (string) config('services.myspp.api_key');

        if ($url === '' || $apiKey === '') {
            return $this->jsonError(502, 'MySPP dokumen proxy tidak dikonfigurasi.');
        }

        $log = APILog::create([
            'method' => 'GET',
            'input' => [
                'id_pemohon' => $idPemohon,
                'jenis' => $jenis,
            ],
            'headers' => [
                'Authorization' => 'Bearer [redacted]',
            ],
            'url' => $url,
            'user_id' => auth()->id(),
        ]);

        try {
            $response = Http::withOptions(['verify' => false])
                ->timeout(30)
                ->connectTimeout(8)
                ->withToken($apiKey)
                ->get($url, [
                    'id_pemohon' => $idPemohon,
                    'jenis' => $jenis,
                ]);
        } catch (ConnectionException $e) {
            $log->update([
                'status_code' => 502,
                'output' => [
                    'success' => false,
                    'errmsg' => 'MySPP tidak dapat dihubungi.',
                    'bytes' => 0,
                ],
            ]);

            return $this->jsonError(502, 'MySPP tidak dapat dihubungi.');
        }

        $status = $response->status();
        $contentType = (string) $response->header('Content-Type');
        $body = $response->body();
        $isJson = str_contains(strtolower($contentType), 'json');

        $log->update([
            'status_code' => $status,
            'output' => [
                'content_type' => $contentType,
                'bytes' => strlen($body),
                'success' => $status === 200 && !$isJson,
            ],
        ]);

        if ($status !== 200 || $isJson) {
            if ($isJson) {
                $decoded = json_decode($body, true);
                if (is_array($decoded)) {
                    return response()->json($decoded, $status > 0 ? $status : 502);
                }
            }

            return $this->jsonError(
                $status > 0 ? $status : 502,
                is_string($body) && $body !== '' ? substr(trim($body), 0, 200) : 'MySPP dokumen gagal dibaca.'
            );
        }

        $headers = [
            'Content-Type' => $contentType !== '' ? $contentType : 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ];

        $disposition = $response->header('Content-Disposition');
        if (is_string($disposition) && $disposition !== '') {
            $headers['Content-Disposition'] = $disposition;
        }

        $length = $response->header('Content-Length');
        if (is_string($length) && $length !== '') {
            $headers['Content-Length'] = $length;
        } else {
            $headers['Content-Length'] = (string) strlen($body);
        }

        return response($body, 200, $headers);
    }

    private function jsonError(int $status, string $message): Response
    {
        return response()->json([
            'success' => false,
            'errmsg' => $message,
        ], $status);
    }
}
