<?php

namespace App\Livewire;

use App\Models\APILog;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class IntegrationMap extends Component
{
    private const CACHE_KEY = 'integration-map.health';

    private const CACHE_TTL = 60; // seconds

    /** How far back in the API log counts as "recent traffic". */
    private const LOG_WINDOW_MINUTES = 15;

    /** An error streak must span at least this long to mark an API down. */
    private const ERROR_STREAK_SECONDS = 10;

    public function checkNow(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function render()
    {
        return view('livewire.integration-map', [
            'integrations' => config('integrations'),
            'health' => Cache::remember(
                self::CACHE_KEY,
                self::CACHE_TTL,
                fn () => $this->probe()
            ),
        ]);
    }

    /**
     * Determine each integration's status. Real logged traffic wins:
     * recent successful requests mean the API is live. Only when there is
     * no recent traffic do we fall back to pinging the configured URL.
     */
    private function probe(): array
    {
        $integrations = config('integrations');

        $recentLogs = APILog::query()
            ->where('created_at', '>=', now()->subMinutes(self::LOG_WINDOW_MINUTES))
            ->whereNotNull('url')
            ->orderByDesc('created_at')
            ->get(['url', 'status_code', 'created_at']);

        $results = [];
        $toPing = [];

        foreach ($integrations as $slug => $integration) {
            $url = $integration['health_url'] ?? null;

            if (! $url) {
                $results[$slug] = [
                    'status' => 'unknown',
                    'detail' => 'Tiada konfigurasi URL',
                    'host' => null,
                ];

                continue;
            }

            $logs = $recentLogs
                ->filter(fn ($log) => str_starts_with($log->url, $url))
                ->values();

            if ($logs->isNotEmpty()) {
                $results[$slug] = $this->statusFromLogs($logs, $url);
            } else {
                $toPing[$slug] = $url;
            }
        }

        return [
            'checked_at' => now()->format('H:i:s'),
            'results' => array_merge($results, $this->pingTargets($toPing)),
        ];
    }

    /**
     * @param  Collection<int, APILog>  $logs  newest first
     */
    private function statusFromLogs(Collection $logs, string $url): array
    {
        $host = parse_url($url, PHP_URL_HOST);
        $latest = $logs->first();
        $age = $latest->created_at->locale('ms')->diffForHumans(short: true);

        if (! $this->isErrorLog($latest)) {
            return [
                'status' => 'up',
                'detail' => "Aktif · log {$age}",
                'host' => $host,
            ];
        }

        // Walk the consecutive error streak from the newest log backwards.
        $streak = $logs->takeWhile(fn ($log) => $this->isErrorLog($log));
        $streakSpan = $streak->last()->created_at
            ->diffInSeconds($streak->first()->created_at);

        $code = $latest->status_code ?: 'tiada respons';

        if ($streakSpan >= self::ERROR_STREAK_SECONDS) {
            return [
                'status' => 'down',
                'detail' => "Ralat berterusan (HTTP {$code}) · {$age}",
                'host' => $host,
            ];
        }

        return [
            'status' => 'up',
            'detail' => "Aktif · ralat terpencil (HTTP {$code})",
            'host' => $host,
        ];
    }

    private function isErrorLog(APILog $log): bool
    {
        return $log->status_code === null || (int) $log->status_code >= 500;
    }

    /**
     * Ping URLs with no recent traffic, all concurrently. Any HTTP response
     * (including 4xx/5xx) means the host is reachable; a connection error
     * or timeout means it is down.
     *
     * @param  array<string, string>  $targets  slug => url
     */
    private function pingTargets(array $targets): array
    {
        if ($targets === []) {
            return [];
        }

        $responses = Http::pool(fn (Pool $pool) => collect($targets)
            ->map(fn ($url, $slug) => $pool->as($slug)
                ->withOptions(['verify' => false])
                ->connectTimeout(3)
                ->timeout(6)
                ->get($url))
            ->values()
            ->all());

        $results = [];

        foreach ($targets as $slug => $url) {
            $response = $responses[$slug] ?? null;
            $host = parse_url($url, PHP_URL_HOST);

            if ($response instanceof Response) {
                $latency = $response->transferStats
                    ? (int) round($response->transferStats->getTransferTime() * 1000)
                    : null;

                $results[$slug] = [
                    'status' => 'up',
                    'detail' => 'Aktif'.($latency !== null ? " · {$latency} ms" : ''),
                    'host' => $host,
                ];
            } else {
                $results[$slug] = [
                    'status' => 'down',
                    'detail' => 'Gagal dicapai',
                    'host' => $host,
                ];
            }
        }

        return $results;
    }
}
