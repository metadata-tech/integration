<?php

namespace App\Console\Commands;

use App\Services\IscafService;
use Illuminate\Console\Command;

class TestIscafApi extends Command
{
    protected $signature = 'iscaf:test
                            {--ic=870824495747 : No KP anggota to test with}
                            {--cawangan=HQ : Cawangan (HQ, S, Q)}
                            {--mykad= : MyKad no pemohon (for daftar & kemas-kini)}
                            {--api= : Run specific API only (status, daftar, informasi, kemas-kini, all)}
                            {--fake : Use fake/mock responses instead of real API calls}';

    protected $description = 'Test iSCAF external API endpoints';

    public function handle(IscafService $service): int
    {
        $ic = $this->option('ic');
        $cawangan = $this->option('cawangan');
        $mykad = $this->option('mykad') ?: $ic;
        $api = $this->option('api') ?: 'all';
        $fake = $this->option('fake');

        $this->info('===========================================');
        $this->info('  iSCAF API Test Script');
        $this->info('===========================================');
        $this->newLine();

        $this->table(['Parameter', 'Value'], [
            ['IC No (anggota)', $ic],
            ['Cawangan', $cawangan],
            ['MyKad No (pemohon)', $mykad],
            ['API', $api],
            ['Mode', $fake ? 'FAKE (mocked)' : 'LIVE'],
            ['Base URL', config('services.iscaf.url')],
            ['API Key', $this->maskKey(config('services.iscaf.api_key'))],
        ]);

        $this->newLine();

        if ($fake) {
            $this->setupFakeResponses();
        }

        $tests = [
            'status' => fn() => $this->testCheckStatus($service, $ic, $cawangan),
            'informasi' => fn() => $this->testGetInfo($service, $ic, $cawangan),
            'kemas-kini' => fn() => $this->testKemasKini($service, $mykad, $cawangan, $ic),
            'daftar' => fn() => $this->testDaftar($service, $mykad, $cawangan, $ic),
        ];

        $results = [];

        foreach ($tests as $name => $test) {
            if ($api !== 'all' && $api !== $name) {
                continue;
            }

            $results[$name] = $test();
            $this->newLine();
        }

        // Summary
        $this->info('===========================================');
        $this->info('  Summary');
        $this->info('===========================================');

        $summaryRows = [];
        foreach ($results as $name => $result) {
            $status = $result['success'] ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
            $summaryRows[] = [$name, $status, $result['http_status'] ?? '-', $result['message'] ?? '-'];
        }

        $this->table(['API', 'Result', 'HTTP Status', 'Message'], $summaryRows);

        return self::SUCCESS;
    }

    private function testCheckStatus(IscafService $service, string $ic, string $cawangan): array
    {
        $this->info('--- API 1: Check Status (POST /status/{ic_no}/{cawangan}) ---');
        $this->line("  POST /status/{$ic}/{$cawangan}");

        try {
            $start = microtime(true);
            $result = $service->checkStatus($ic, $cawangan);
            $elapsed = round((microtime(true) - $start) * 1000);

            $this->line("  Response ({$elapsed}ms):");
            $this->line('  ' . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $success = isset($result['status']);
            $this->resultLine($success);

            return ['success' => $success, 'message' => $result['message'] ?? null, 'http_status' => $result['status'] ?? null];
        } catch (\Exception $e) {
            $this->error("  Exception: {$e->getMessage()}");
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function testGetInfo(IscafService $service, string $ic, string $cawangan): array
    {
        $this->info('--- API 3: Get Info (GET /informasi/{ic_no}/{cawangan}) ---');
        $this->line("  GET /informasi/{$ic}/{$cawangan}");

        try {
            $start = microtime(true);
            $result = $service->getInfo($ic, $cawangan);
            $elapsed = round((microtime(true) - $start) * 1000);

            $this->line("  Response ({$elapsed}ms):");
            $this->line('  ' . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $success = isset($result['status']);

            if (isset($result['data'])) {
                $this->line('  Data fields:');
                foreach ($result['data'] as $key => $value) {
                    $this->line("    {$key}: {$value}");
                }
            }

            $this->resultLine($success);

            return ['success' => $success, 'message' => $result['message'] ?? null, 'http_status' => $result['status'] ?? null];
        } catch (\Exception $e) {
            $this->error("  Exception: {$e->getMessage()}");
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function testKemasKini(IscafService $service, string $mykad, string $cawangan, string $ic): array
    {
        $this->info('--- API 4: Kemas Kini (POST /kemas-kini) ---');

        if (!$this->confirm('  This will UPDATE a file record (aktiviti=1 masuk). Proceed?', false)) {
            $this->warn('  Skipped.');
            return ['success' => true, 'message' => 'Skipped by user'];
        }

        $this->line("  POST /kemas-kini {mykad_no: {$mykad}, cawangan: {$cawangan}, ic_no: {$ic}, aktiviti: 1}");

        try {
            $start = microtime(true);
            $result = $service->kemasKini($mykad, $cawangan, $ic, 1);
            $elapsed = round((microtime(true) - $start) * 1000);

            $this->line("  Response ({$elapsed}ms):");
            $this->line('  ' . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $success = isset($result['status']);
            $this->resultLine($success);

            return ['success' => $success, 'message' => $result['message'] ?? null, 'http_status' => $result['status'] ?? null];
        } catch (\Exception $e) {
            $this->error("  Exception: {$e->getMessage()}");
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function testDaftar(IscafService $service, string $mykad, string $cawangan, string $ic): array
    {
        $this->info('--- API 2: Daftar (POST /daftar) ---');

        if (!$this->confirm('  This will REGISTER a new file. Proceed?', false)) {
            $this->warn('  Skipped.');
            return ['success' => true, 'message' => 'Skipped by user'];
        }

        $data = [
            [
                'nama' => 'TEST USER',
                'ic_no' => $ic,
                'position_id' => 1,
                'volume' => false,
            ]
        ];

        $this->line("  POST /daftar {mykad_no: {$mykad}, cawangan: {$cawangan}}");
        $this->line('  data: ' . json_encode($data));

        try {
            $start = microtime(true);
            $result = $service->daftar($mykad, $cawangan, 'TEST-001', $data);
            $elapsed = round((microtime(true) - $start) * 1000);

            $this->line("  Response ({$elapsed}ms):");
            $this->line('  ' . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $success = isset($result['status']);
            $this->resultLine($success);

            return ['success' => $success, 'message' => $result['message'] ?? null, 'http_status' => $result['status'] ?? null];
        } catch (\Exception $e) {
            $this->error("  Exception: {$e->getMessage()}");
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function setupFakeResponses(): void
    {
        $this->warn('  Using FAKE responses (Http::fake)');
        $this->newLine();

        \Illuminate\Support\Facades\Http::fake([
            '*/status/*' => \Illuminate\Support\Facades\Http::response([
                'status' => 'success',
                'message' => 'record found',
            ], 200),
            '*/informasi/*' => \Illuminate\Support\Facades\Http::response([
                'status' => 'success',
                'message' => 'record found',
                'data' => [
                    'no_rujukan' => 'SPP.910228035975',
                    'no_iscaf' => '0509408',
                    'bil_surat' => 5,
                ],
            ], 200),
            '*/kemas-kini' => \Illuminate\Support\Facades\Http::response([
                'status' => 'success',
                'message' => 'record found',
                'data' => [
                    'no_rujukan' => 'SPP.910228035975',
                    'no_iscaf' => '0509408',
                    'bil_surat' => 6,
                ],
            ], 200),
            '*/daftar' => \Illuminate\Support\Facades\Http::response([
                'status' => 'success',
                'message' => 'record found',
            ], 200),
        ]);
    }

    private function maskKey(?string $key): string
    {
        if (!$key) {
            return '<fg=red>NOT SET</>';
        }

        return substr($key, 0, 8) . '...' . substr($key, -4);
    }

    private function resultLine(bool $success): void
    {
        if ($success) {
            $this->info('  ✓ Response received');
        } else {
            $this->error('  ✗ No valid response');
        }
    }
}
