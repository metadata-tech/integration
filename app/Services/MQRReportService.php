<?php

namespace App\Services;

use App\Models\MqrData;
use App\Models\MqrMappingRun;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MQRReportService
{
    private const DISK = 'local';

    private const REPORT_ROOT = 'mqr-reports';

    private const OPERATION_LOCK = 'mqr-mapping:operation';

    private const OPERATION_LOCK_SECONDS = 300;

    private const MAPPING_REPORT_TYPES = ['all', 'success', 'failure'];

    private const REPORT_TYPES = ['all', 'success', 'failure', 'references', 'duplicates'];

    private const HEADERS = [
        'Bil.',
        'Kod Rujukan MQA',
        'ID Institusi (MQR)',
        'Nama Institusi (MQR)',
        'Nama Kelayakan (MQR)',
        'Status Padanan',
        'Kaedah Padanan Institusi',
        'Kod Institusi (Ref Sistem)',
        'Padanan Institusi (Ref Sistem)',
        'Kod Pengkhususan (Ref Sistem)',
        'Padanan Pengkhususan (Ref Sistem)',
        'Sebab Kegagalan',
    ];

    public function startOrResume(?int $userId): MqrMappingRun
    {
        return Cache::lock(
            self::OPERATION_LOCK,
            self::OPERATION_LOCK_SECONDS
        )->block(
            5,
            function () use ($userId): MqrMappingRun {
                $running = $this->currentRunning();

                if ($running) {
                    return $running;
                }

                $maxMqrId = (int) MqrData::query()->max('id');
                $totalRecords = MqrData::query()
                    ->where('id', '<=', $maxMqrId)
                    ->count();
                $runId = (string) Str::ulid();
                $reportPath = self::REPORT_ROOT.'/'.$runId;

                if (! $this->disk()->makeDirectory($reportPath.'/parts')) {
                    throw new RuntimeException('Folder laporan MQR tidak dapat dicipta.');
                }

                try {
                    return MqrMappingRun::query()->create([
                        'id' => $runId,
                        'user_id' => $userId,
                        'status' => MqrMappingRun::STATUS_RUNNING,
                        'active_slot' => 1,
                        'max_mqr_id' => $maxMqrId,
                        'total_records' => $totalRecords,
                        'report_path' => $reportPath,
                    ]);
                } catch (\Throwable $e) {
                    $this->disk()->deleteDirectory($reportPath);

                    if ($running = $this->currentRunning()) {
                        return $running;
                    }

                    throw $e;
                }
            }
        );
    }

    /**
     * Jalankan Sync API hanya apabila tiada run padanan aktif. Kunci yang
     * sama digunakan oleh startOrResume supaya snapshot MQR tidak bercampur.
     *
     * @return array{status: bool, message: string}
     */
    public function syncWhenIdle(callable $sync): array
    {
        try {
            return Cache::lock(
                self::OPERATION_LOCK,
                self::OPERATION_LOCK_SECONDS
            )->block(5, function () use ($sync): array {
                if ($this->currentRunning()) {
                    return [
                        'status' => false,
                        'message' => 'Sync API tidak boleh dimulakan ketika proses padanan masih berjalan.',
                    ];
                }

                return $sync();
            });
        } catch (LockTimeoutException) {
            return [
                'status' => false,
                'message' => 'Operasi MQR lain sedang berjalan. Sila cuba semula sebentar lagi.',
            ];
        }
    }

    public function currentRunning(): ?MqrMappingRun
    {
        return MqrMappingRun::query()
            ->whereIn('status', [
                MqrMappingRun::STATUS_RUNNING,
                MqrMappingRun::STATUS_FINALIZING,
            ])
            ->latest()
            ->first();
    }

    /**
     * @param  array<int, array<string, mixed>>  $mappingRows
     */
    public function writeBatchParts(
        MqrMappingRun $run,
        array $mappingRows
    ): void {
        if ($run->status !== MqrMappingRun::STATUS_RUNNING) {
            throw new RuntimeException('Run laporan MQR tidak lagi aktif.');
        }

        $batchNumber = $run->batch_number + 1;
        $allNumber = $run->processed_count;
        $successNumber = $run->success_count;
        $failureNumber = $run->failure_count;
        $rowsByType = [
            'all' => [],
            'success' => [],
            'failure' => [],
        ];

        foreach ($mappingRows as $mappingRow) {
            $allNumber++;
            $values = $this->mappingRowValues($mappingRow);
            $rowsByType['all'][] = [$allNumber, ...$values];

            if ($mappingRow['success']) {
                $successNumber++;
                $rowsByType['success'][] = [$successNumber, ...$values];
            } else {
                $failureNumber++;
                $rowsByType['failure'][] = [$failureNumber, ...$values];
            }
        }

        foreach (self::MAPPING_REPORT_TYPES as $type) {
            $this->atomicPut(
                $this->partPath($run, $batchNumber, $type),
                $this->encodeCsvRows($rowsByType[$type])
            );
        }
    }

    public function complete(MqrMappingRun $run): MqrMappingRun
    {
        $run->refresh();

        if ($run->status === MqrMappingRun::STATUS_COMPLETED) {
            return $run;
        }

        if (
            ! in_array($run->status, [
                MqrMappingRun::STATUS_RUNNING,
                MqrMappingRun::STATUS_FINALIZING,
            ], true)
            || $run->processed_count !== $run->total_records
            || $run->success_count + $run->failure_count
                !== $run->processed_count
        ) {
            throw new RuntimeException(
                'Laporan hanya boleh disiapkan selepas semua rekod diproses.'
            );
        }

        if ($run->status === MqrMappingRun::STATUS_RUNNING) {
            $run->update(['status' => MqrMappingRun::STATUS_FINALIZING]);
        }

        foreach (self::MAPPING_REPORT_TYPES as $type) {
            $stream = fopen('php://temp/maxmemory:5242880', 'w+b');

            if ($stream === false) {
                throw new RuntimeException('Buffer laporan tidak dapat dibuka.');
            }

            try {
                fwrite($stream, "\xEF\xBB\xBF");
                $this->writeCsvRow($stream, self::HEADERS);

                for (
                    $batchNumber = 1;
                    $batchNumber <= $run->batch_number;
                    $batchNumber++
                ) {
                    $partPath = $this->partPath($run, $batchNumber, $type);
                    $partStream = $this->disk()->readStream($partPath);

                    if ($partStream === false) {
                        throw new RuntimeException(
                            "Bahagian laporan {$batchNumber} tidak ditemui."
                        );
                    }

                    try {
                        stream_copy_to_stream($partStream, $stream);
                    } finally {
                        fclose($partStream);
                    }
                }

                rewind($stream);
                $this->atomicPutStream($this->finalPath($run, $type), $stream);
            } finally {
                fclose($stream);
            }
        }

        $this->publishAuditReports($run);

        foreach (self::REPORT_TYPES as $type) {
            if (! $this->disk()->exists($this->finalPath($run, $type))) {
                throw new RuntimeException(
                    "Fail laporan {$type} tidak berjaya diterbitkan."
                );
            }
        }

        $run->update([
            'status' => MqrMappingRun::STATUS_COMPLETED,
            'active_slot' => null,
            'completed_at' => now(),
            'error_message' => null,
        ]);

        $this->disk()->deleteDirectory($run->report_path.'/parts');

        return $run->fresh();
    }

    private function publishAuditReports(MqrMappingRun $run): void
    {
        $connection = DB::connection(config('services.mqr.mapping_connection', 'mqr_mapping'));
        $schema = trim((string) config('services.mqr.mapping_schema'));
        $table = fn (string $name): string => $schema === '' ? $name : $schema.'.'.$name;

        $connection->table($table('mqr_mapping_duplicate_audits'))
            ->whereNull('run_id')->update(['run_id' => $run->id]);
        $connection->table($table('mqr_mapping_duplicate_audits'))
            ->where('run_id', $run->id)->where('hasil', 'MENUNGGU_PADANAN')
            ->update(['hasil' => 'PADANAN_GAGAL']);

        $references = $connection->table($table('mqr_reference_registrations'))
            ->where('run_id', $run->id)->orderBy('id')->get();
        $referenceRows = [[
            'Bil.', 'Jenis', 'Kod Rujukan', 'Nama', 'Kumpulan Institusi', 'Kategori',
        ]];
        foreach ($references as $reference) {
            $referenceRows[] = [count($referenceRows), $reference->jenis, $reference->kod,
                $reference->nama, $reference->kumpulan_institusi, $reference->kategori];
        }

        $duplicates = $connection->table($table('mqr_mapping_duplicate_audits'))
            ->where('run_id', $run->id)->orderBy('kod_mqa')->orderBy('kod_dinyahaktifkan')->get();
        $duplicateRows = [[
            'Bil.', 'Kod MQA', 'ID Dikekalkan', 'ID Dinyahaktifkan',
            'Kod Institusi Lama', 'Kod Pengkhususan Lama',
            'Kod Institusi Akhir', 'Kod Pengkhususan Akhir', 'Hasil',
        ]];
        foreach ($duplicates as $duplicate) {
            $duplicateRows[] = [count($duplicateRows), $duplicate->kod_mqa,
                $duplicate->kod_dikekalkan, $duplicate->kod_dinyahaktifkan,
                $duplicate->kod_institusi_lama, $duplicate->kod_pengkhususan_lama,
                $duplicate->kod_institusi_akhir, $duplicate->kod_pengkhususan_akhir,
                $duplicate->hasil];
        }

        $this->atomicPut($this->finalPath($run, 'references'), "\xEF\xBB\xBF".$this->encodeCsvRows($referenceRows));
        $this->atomicPut($this->finalPath($run, 'duplicates'), "\xEF\xBB\xBF".$this->encodeCsvRows($duplicateRows));
    }

    public function markFailed(MqrMappingRun $run, string $message): void
    {
        $run->update([
            'status' => MqrMappingRun::STATUS_FAILED,
            'active_slot' => null,
            'error_message' => Str::limit($message, 2000, ''),
        ]);

        if ($run->report_path) {
            $this->disk()->deleteDirectory($run->report_path);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function completedReports(int $limit = 5): array
    {
        return MqrMappingRun::query()
            ->completed()
            ->latest('completed_at')
            ->limit($limit)
            ->get()
            ->filter(
                fn (MqrMappingRun $run): bool => collect(self::MAPPING_REPORT_TYPES)
                    ->every(
                        fn (string $type): bool => $this->disk()
                            ->exists($this->finalPath($run, $type))
                    )
            )
            ->map(fn (MqrMappingRun $run): array => $this->state($run))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function state(MqrMappingRun $run): array
    {
        $connection = DB::connection(config('services.mqr.mapping_connection', 'mqr_mapping'));
        $schema = trim((string) config('services.mqr.mapping_schema'));
        $table = fn (string $name): string => $schema === '' ? $name : $schema.'.'.$name;
        $duplicates = $connection->table($table('mqr_mapping_duplicate_audits'))->where('run_id', $run->id);

        return [
            'id' => $run->id,
            'status' => $run->status,
            'total' => $run->total_records,
            'processed' => $run->processed_count,
            'berjaya' => $run->success_count,
            'gagal' => $run->failure_count,
            'kod_mqa_pendua' => (clone $duplicates)->distinct()->count('kod_mqa'),
            'rekod_dinyahaktifkan' => (clone $duplicates)->count(),
            'rujukan_baharu' => $connection->table($table('mqr_reference_registrations'))->where('run_id', $run->id)->count(),
            'ada_laporan_audit' => $this->disk()->exists($this->finalPath($run, 'duplicates')),
            'started_at' => $run->created_at?->format('d/m/Y H:i:s'),
            'completed_at' => $run->completed_at?->format('d/m/Y H:i:s'),
        ];
    }

    public function download(
        MqrMappingRun $run,
        string $type
    ): StreamedResponse {
        if (
            $run->status !== MqrMappingRun::STATUS_COMPLETED
            || ! in_array($type, self::REPORT_TYPES, true)
        ) {
            abort(404);
        }

        $path = $this->finalPath($run, $type);

        if (! $this->disk()->exists($path)) {
            abort(404);
        }

        return $this->disk()->download(
            $path,
            $this->downloadFilename($run, $type),
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    private function finalPath(MqrMappingRun $run, string $type): string
    {
        return $run->report_path.'/'.$this->baseFilename($type);
    }

    private function partPath(
        MqrMappingRun $run,
        int $batchNumber,
        string $type
    ): string {
        return sprintf(
            '%s/parts/%06d_%s.csv',
            $run->report_path,
            $batchNumber,
            $type
        );
    }

    private function baseFilename(string $type): string
    {
        return match ($type) {
            'success' => 'padanan_berjaya.csv',
            'failure' => 'padanan_gagal.csv',
            'references' => 'rujukan_baharu.csv',
            'duplicates' => 'kod_mqa_pendua.csv',
            default => 'laporan_lengkap.csv',
        };
    }

    private function downloadFilename(
        MqrMappingRun $run,
        string $type
    ): string {
        $timestamp = $run->completed_at?->format('Ymd_His')
            ?? $run->updated_at->format('Ymd_His');

        return match ($type) {
            'success' => "senarai_mqr_berjaya_dipadan_{$timestamp}.csv",
            'failure' => "senarai_mqr_gagal_dipadan_{$timestamp}.csv",
            'references' => "senarai_rujukan_baharu_{$timestamp}.csv",
            'duplicates' => "senarai_kod_mqa_pendua_{$timestamp}.csv",
            default => "laporan_padanan_mqr_{$timestamp}.csv",
        };
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, mixed>
     */
    private function mappingRowValues(array $row): array
    {
        return [
            $row['kod_mqa'],
            $row['id_institusi_mqr'],
            $row['nama_institusi_mqr'],
            $row['nama_kelayakan_mqr'],
            $row['success'] ? 'BERJAYA' : 'GAGAL',
            $row['institution_match_method'],
            $row['kod_institusi_ref'],
            $row['nama_institusi_ref'],
            $row['kod_pengkhususan_ref'],
            $row['nama_pengkhususan_ref'],
            $row['success'] ? '' : $row['reason'],
        ];
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function encodeCsvRows(array $rows): string
    {
        $stream = fopen('php://temp', 'w+b');

        if ($stream === false) {
            throw new RuntimeException('Buffer CSV tidak dapat dibuka.');
        }

        try {
            foreach ($rows as $row) {
                $this->writeCsvRow($stream, $row);
            }

            rewind($stream);

            return (string) stream_get_contents($stream);
        } finally {
            fclose($stream);
        }
    }

    /**
     * @param  resource  $stream
     * @param  array<int, mixed>  $values
     */
    private function writeCsvRow($stream, array $values): void
    {
        fputcsv(
            $stream,
            array_map($this->sanitizeSpreadsheetCell(...), $values),
            ',',
            '"',
            ''
        );
    }

    private function sanitizeSpreadsheetCell(mixed $value): string
    {
        $value = str_replace("\0", '', (string) ($value ?? ''));

        if (preg_match('/^[=+\-@\t\r]/u', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }

    private function atomicPut(string $path, string $contents): void
    {
        $temporaryPath = $path.'.tmp-'.Str::uuid();

        if (! $this->disk()->put($temporaryPath, $contents)) {
            $this->disk()->delete($temporaryPath);

            throw new RuntimeException("Fail sementara {$path} tidak dapat ditulis.");
        }

        $this->replaceWithTemporaryFile($temporaryPath, $path);
    }

    /**
     * @param  resource  $stream
     */
    private function atomicPutStream(string $path, $stream): void
    {
        $temporaryPath = $path.'.tmp-'.Str::uuid();

        if (! $this->disk()->put($temporaryPath, $stream)) {
            $this->disk()->delete($temporaryPath);

            throw new RuntimeException("Fail sementara {$path} tidak dapat ditulis.");
        }

        $this->replaceWithTemporaryFile($temporaryPath, $path);
    }

    private function replaceWithTemporaryFile(
        string $temporaryPath,
        string $path
    ): void {
        if ($this->disk()->exists($path)) {
            $this->disk()->delete($path);
        }

        if (! $this->disk()->move($temporaryPath, $path)) {
            $this->disk()->delete($temporaryPath);

            throw new RuntimeException("Fail {$path} tidak dapat diterbitkan.");
        }
    }

    private function disk(): FilesystemAdapter
    {
        return Storage::disk(self::DISK);
    }
}
