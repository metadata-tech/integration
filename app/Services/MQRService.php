<?php

namespace App\Services;

use App\Models\APILog;
use App\Models\MqrData;
use App\Models\MqrMappingRun;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MQRService
{
    private const MAPPING_TABLE = 'padanan_institusi_pengkhususan';

    private const MINIMUM_PREFIX_LENGTH = 5;

    /**
     * @var array<int, array<string, array{institution: ?object, method: ?string, reason: ?string}>>
     */
    private array $institutionMatchCache = [];

    /**
     * Fungsi untuk Automasi Padanan secara Kelompok (Batching untuk UI Livewire)
     */
    public function mapNextBatch(
        string $runId,
        int $limit,
        MQRReportService $reportService
    ): array {
        try {
            return Cache::lock("mqr-mapping-run:{$runId}", 120)->block(
                5,
                fn (): array => $this->processNextBatch(
                    $runId,
                    $limit,
                    $reportService
                )
            );
        } catch (Throwable $e) {
            Log::error('Mapping Batch Error: '.$e->getMessage());

            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    private function processNextBatch(
        string $runId,
        int $limit,
        MQRReportService $reportService
    ): array {
        $batch = DB::transaction(function () use (
            $runId,
            $limit,
            $reportService
        ): array {
            $run = MqrMappingRun::query()
                ->whereKey($runId)
                ->lockForUpdate()
                ->firstOrFail();
            $stats = [
                'processed' => 0,
                'berjaya' => 0,
                'gagal' => 0,
                'latest_berjaya' => [],
                'latest_gagal' => [],
            ];

            if (in_array($run->status, [
                MqrMappingRun::STATUS_COMPLETED,
                MqrMappingRun::STATUS_FINALIZING,
            ], true)) {
                return ['run' => $run, 'stats' => $stats];
            }

            if ($run->status !== MqrMappingRun::STATUS_RUNNING) {
                throw new \RuntimeException(
                    'Proses padanan ini tidak lagi aktif.'
                );
            }

            if ($run->processed_count === $run->total_records) {
                $run->update([
                    'status' => MqrMappingRun::STATUS_FINALIZING,
                ]);

                return ['run' => $run, 'stats' => $stats];
            }

            $references = $this->loadReferenceData();
            $mqrData = MqrData::query()
                ->where('id', '>', $run->last_mqr_id)
                ->where('id', '<=', $run->max_mqr_id)
                ->orderBy('id')
                ->limit(max(1, $limit))
                ->get();

            if ($mqrData->isEmpty()) {
                throw new \RuntimeException(
                    'Snapshot data MQR berubah sebelum semua rekod sempat diproses.'
                );
            }

            $reportRows = [];
            $successfulMappings = [];

            foreach ($mqrData as $mqr) {
                $stats['processed']++;
                $mapping = $this->evaluateMapping($mqr, $references);
                $reportRows[] = $this->mappingReportRow($mqr, $mapping);

                if ($mapping['success']) {
                    $institutionCode = (string) $mapping['institution']->KOD;
                    $specializationCode = (string) $mapping['specialization']->kod;
                    $successfulMappings[$this->mappingPairKey(
                        $institutionCode,
                        $specializationCode
                    )] = [
                        'kod_institusi' => $institutionCode,
                        'kod_pengkhususan' => $specializationCode,
                        'kategori' => $mapping['specialization']->KATEGORI,
                        'kod_mqa' => $mqr->kod_rujukan_akreditasi_penuh,
                    ];

                    $stats['berjaya']++;
                    $stats['latest_berjaya'][] = "{$mqr->nama_institusi} - {$mqr->nama_kelayakan_bm}";

                    continue;
                }

                $stats['gagal']++;
                $stats['latest_gagal'][] = "{$mqr->nama_institusi} - {$mqr->nama_kelayakan_bm} ({$mapping['reason']})";
            }

            $this->saveMappings(array_values($successfulMappings));
            $reportService->writeBatchParts($run, $reportRows);

            $processedCount = $run->processed_count + $stats['processed'];
            $run->update([
                'last_mqr_id' => (int) $mqrData->last()->id,
                'processed_count' => $processedCount,
                'success_count' => $run->success_count + $stats['berjaya'],
                'failure_count' => $run->failure_count + $stats['gagal'],
                'batch_number' => $run->batch_number + 1,
                'status' => $processedCount === $run->total_records
                    ? MqrMappingRun::STATUS_FINALIZING
                    : MqrMappingRun::STATUS_RUNNING,
            ]);

            return ['run' => $run->fresh(), 'stats' => $stats];
        });

        $run = $batch['run'];
        $stats = $batch['stats'];

        if ($run->status === MqrMappingRun::STATUS_FINALIZING) {
            $run = $reportService->complete($run);
        }

        $stats['latest_berjaya'] = array_slice(
            array_reverse($stats['latest_berjaya']),
            0,
            10
        );
        $stats['latest_gagal'] = array_slice(
            array_reverse($stats['latest_gagal']),
            0,
            10
        );
        $stats['run'] = $reportService->state($run);

        return ['status' => true, 'data' => $stats];
    }

    /**
     * @param array{
     *     success: bool,
     *     reason: string,
     *     institution: ?object,
     *     specialization: ?object,
     *     institution_match_method: ?string
     * } $mapping
     * @return array<string, mixed>
     */
    private function mappingReportRow(object $mqr, array $mapping): array
    {
        return [
            'kod_mqa' => $mqr->kod_rujukan_akreditasi_penuh,
            'id_institusi_mqr' => $mqr->id_institusi,
            'nama_institusi_mqr' => $mqr->nama_institusi,
            'nama_kelayakan_mqr' => $mqr->nama_kelayakan_bm,
            'success' => $mapping['success'],
            'institution_match_method' => $mapping['institution_match_method'] ?? '',
            'kod_institusi_ref' => $mapping['institution']->KOD ?? '',
            'nama_institusi_ref' => $mapping['institution']->DISKRIPSI ?? '',
            'kod_pengkhususan_ref' => $mapping['specialization']->kod ?? '',
            'nama_pengkhususan_ref' => $mapping['specialization']->DISKRIPSI ?? '',
            'reason' => $mapping['reason'],
        ];
    }

    /**
     * Sediakan indeks rujukan sekali sahaja untuk digunakan oleh resolver padanan.
     *
     * @return array{
     *     institution_exact: Collection<string, Collection<int, array{record: object, normalized: string, priority: int}>>,
     *     institution_alias: Collection<string, Collection<int, array{record: object, normalized: string, priority: int}>>,
     *     specialization_exact: Collection<string, object>
     * }
     */
    public function prepareReferenceData(Collection $institutions, Collection $specializations): array
    {
        $this->institutionMatchCache = [];

        $institutionItems = $institutions
            ->flatMap(function (object $institution): array {
                return array_map(
                    fn (array $alias): array => [
                        'record' => $institution,
                        'normalized' => $alias['normalized'],
                        'priority' => $alias['priority'],
                    ],
                    $this->institutionAliases($institution->DISKRIPSI ?? '')
                );
            })
            ->filter(fn (array $item): bool => $item['normalized'] !== '')
            ->unique(
                fn (array $item): string => ($item['record']->KOD ?? '').'|'.$item['normalized']
            )
            ->values();

        $specializationIndex = $specializations->mapWithKeys(
            function (object $specialization): array {
                $normalized = $this->normalizeQualificationText(
                    $specialization->DISKRIPSI ?? ''
                );

                return $normalized === ''
                    ? []
                    : [$normalized => $specialization];
            }
        );

        return [
            'institution_exact' => $institutionItems
                ->where('priority', 3)
                ->groupBy('normalized'),
            'institution_alias' => $institutionItems->groupBy('normalized'),
            'specialization_exact' => $specializationIndex,
        ];
    }

    /**
     * Nilai satu rekod menggunakan logik yang sama untuk mapping, live feed dan laporan.
     *
     * @param  array{
     *     institution_exact: Collection,
     *     institution_alias: Collection,
     *     specialization_exact: Collection
     * }  $references
     * @return array{
     *     success: bool,
     *     reason: string,
     *     institution: ?object,
     *     specialization: ?object,
     *     institution_match_method: ?string
     * }
     */
    public function evaluateMapping(object $mqr, array $references): array
    {
        $reasons = [];
        $mqaCode = trim((string) ($mqr->kod_rujukan_akreditasi_penuh ?? ''));

        if ($mqaCode === '') {
            $reasons[] = 'Kod rujukan MQA tidak dibekalkan oleh API MQR.';
        }

        $institutionResult = $this->findInstitutionMatch(
            $mqr->nama_institusi ?? null,
            $references
        );

        if ($institutionResult['reason'] !== null) {
            $reasons[] = $institutionResult['reason'];
        }

        $specializationName = trim((string) ($mqr->nama_kelayakan_bm ?? ''));
        $specialization = null;

        if ($specializationName === '') {
            $reasons[] = 'Nama kelayakan BM tidak dibekalkan oleh API MQR.';
        } else {
            $specialization = $references['specialization_exact']->get(
                $this->normalizeQualificationText($specializationName)
            );

            if (! $specialization) {
                $reasons[] = 'Tiada pengkhususan yang sepadan dalam jadual ref_pengkhususan.';
            }
        }

        $success = $reasons === [];

        return [
            'success' => $success,
            'reason' => $success
                ? 'Padanan institusi dan pengkhususan berjaya ditemui.'
                : implode(' ', $reasons),
            'institution' => $institutionResult['institution'],
            'specialization' => $specialization,
            'institution_match_method' => $institutionResult['method'],
        ];
    }

    /**
     * Proses semua keputusan secara chunk tanpa bergantung pada kod_mqa yang boleh ditimpa.
     */
    public function eachMappingResult(callable $callback, int $chunkSize = 1000): void
    {
        $references = $this->loadReferenceData();

        MqrData::query()
            ->orderBy('id')
            ->chunkById($chunkSize, function (Collection $records) use ($callback, $references): void {
                foreach ($records as $mqr) {
                    $callback($mqr, $this->evaluateMapping($mqr, $references));
                }
            });
    }

    /**
     * @param  array{
     *     institution_exact: Collection,
     *     institution_alias: Collection,
     *     specialization_exact: Collection
     * }  $references
     * @return array{institution: ?object, method: ?string, reason: ?string}
     */
    private function findInstitutionMatch(?string $name, array $references): array
    {
        $normalizedName = $this->normalizeText($name);
        $cacheKey = trim((string) $name);
        $referenceKey = spl_object_id($references['institution_alias']);

        if (isset($this->institutionMatchCache[$referenceKey])
            && array_key_exists(
                $cacheKey,
                $this->institutionMatchCache[$referenceKey]
            )
        ) {
            return $this->institutionMatchCache[$referenceKey][$cacheKey];
        }

        return $this->institutionMatchCache[$referenceKey][$cacheKey] =
            $this->resolveInstitutionMatch($name, $normalizedName, $references);
    }

    /**
     * @param  array{
     *     institution_exact: Collection,
     *     institution_alias: Collection,
     *     specialization_exact: Collection
     * }  $references
     * @return array{institution: ?object, method: ?string, reason: ?string}
     */
    private function resolveInstitutionMatch(
        ?string $name,
        string $normalizedName,
        array $references
    ): array {
        if ($normalizedName === '') {
            return [
                'institution' => null,
                'method' => null,
                'reason' => 'Nama institusi tidak dibekalkan oleh API MQR.',
            ];
        }

        $exactCandidates = $references['institution_exact']->get($normalizedName);

        if ($exactCandidates) {
            return $this->resolveInstitutionCandidates($exactCandidates, 'Nama tepat');
        }

        $aliasCandidates = $references['institution_alias']->get($normalizedName);

        if ($aliasCandidates) {
            return $this->resolveInstitutionCandidates(
                $aliasCandidates,
                'Nama alternatif institusi dalam jadual rujukan'
            );
        }

        $nameWithoutBranch = $this->stripBranchQualifier($name);
        $baseName = $this->normalizeText($nameWithoutBranch);
        $baseCandidates = $references['institution_alias']->get($baseName);

        if ($baseCandidates && $baseName !== $normalizedName) {
            return $this->resolveInstitutionCandidates(
                $baseCandidates,
                'Maklumat kampus/cawangan di hujung nama diabaikan'
            );
        }

        $nameWithoutAcronym = $this->normalizeText(
            $this->stripTrailingAcronymQualifier($nameWithoutBranch)
        );
        $nameWithoutAcronymCandidates = $references['institution_alias']->get(
            $nameWithoutAcronym
        );

        if (
            $nameWithoutAcronym !== $baseName
            && $nameWithoutAcronymCandidates
        ) {
            return $this->resolveInstitutionCandidates(
                $nameWithoutAcronymCandidates,
                'Nama asas sama (singkatan akhir dalam kurungan diabaikan)'
            );
        }

        $branchPrefixCandidates = $this->findLongestPrefixCandidates(
            $references['institution_alias'],
            $normalizedName,
            branchQualifierOnly: true
        );

        if ($branchPrefixCandidates->isNotEmpty()) {
            return $this->resolveInstitutionCandidates(
                $branchPrefixCandidates,
                'Nama institusi diikuti maklumat kampus/cawangan'
            );
        }

        /*
         * Lokasi cawangan kadangkala dihantar tanpa perkataan "kampus", seperti
         * "Universiti Teknologi MARA (UiTM) Shah Alam". Padanan ini hanya
         * dibenarkan terhadap nama penuh/alias eksplisit (priority >= 2).
         * Alias priority 1 telah membuang singkatan dan terlalu umum untuk
         * digunakan sebagai awalan; contohnya Universiti Malaya-Wales bukan
         * cawangan Universiti Malaya.
         */
        $locationPrefixCandidates = $this->findLongestPrefixCandidates(
            $references['institution_alias'],
            $normalizedName,
            minimumPriority: 2
        );

        if ($locationPrefixCandidates->isNotEmpty()) {
            return $this->resolveInstitutionCandidates(
                $locationPrefixCandidates,
                'Nama institusi diikuti maklumat lokasi/cawangan'
            );
        }

        /*
         * Bagi nama asas yang terhasil selepas singkatan rujukan dibuang,
         * sekurang-kurangnya tiga perkataan diperlukan. Ini masih menerima
         * nama/cawangan rasmi seperti "Universiti Malaysia Pahang Al-Sultan
         * Abdullah", tetapi menghalang awalan terlalu umum seperti
         * "Universiti Malaya" daripada menyerap "Universiti Malaya-Wales".
         */
        $baseLocationPrefixCandidates = $this->findLongestPrefixCandidates(
            $references['institution_alias'],
            $normalizedName,
            maximumPriority: 1,
            minimumCandidateWords: 3
        );

        if ($baseLocationPrefixCandidates->isNotEmpty()) {
            return $this->resolveInstitutionCandidates(
                $baseLocationPrefixCandidates,
                'Nama asas institusi diikuti maklumat lokasi/cawangan'
            );
        }

        return [
            'institution' => null,
            'method' => null,
            'reason' => 'Tiada institusi yang sepadan dalam jadual ref_institusi.',
        ];
    }

    /**
     * Cari hanya prefix yang memang wujud dalam indeks alias. Bilangan lookup
     * kini mengikut bilangan perkataan nama MQR, bukan jumlah institusi rujukan.
     *
     * @param  Collection<string, Collection<int, array{record: object, normalized: string, priority: int}>>  $aliasIndex
     * @return Collection<int, array{record: object, normalized: string, priority: int}>
     */
    private function findLongestPrefixCandidates(
        Collection $aliasIndex,
        string $name,
        int $minimumPriority = 1,
        ?int $maximumPriority = null,
        bool $branchQualifierOnly = false,
        int $minimumCandidateWords = 1
    ): Collection {
        $words = explode(' ', $name);

        for (
            $wordCount = count($words) - 1;
            $wordCount >= $minimumCandidateWords;
            $wordCount--
        ) {
            $candidateName = implode(' ', array_slice($words, 0, $wordCount));

            if (
                strlen($candidateName) < self::MINIMUM_PREFIX_LENGTH
                || str_word_count($candidateName) < $minimumCandidateWords
            ) {
                continue;
            }

            $candidates = $aliasIndex->get($candidateName);

            if (! $candidates) {
                continue;
            }

            $candidates = $candidates
                ->filter(
                    fn (array $candidate): bool => $candidate['priority'] >= $minimumPriority
                        && (
                            $maximumPriority === null
                            || $candidate['priority'] <= $maximumPriority
                        )
                )
                ->values();

            if ($candidates->isEmpty()) {
                continue;
            }

            $remainder = implode(' ', array_slice($words, $wordCount));

            if (
                $branchQualifierOnly
                    ? ! $this->containsBranchMarker($remainder)
                    : ! $this->isSafeAdditionalQualifier($remainder)
            ) {
                continue;
            }

            return $candidates;
        }

        return collect();
    }

    /**
     * @param  Collection<int, array{record: object, normalized: string, priority: int}>  $candidates
     * @return array{institution: ?object, method: ?string, reason: ?string}
     */
    private function resolveInstitutionCandidates(Collection $candidates, string $method): array
    {
        $highestPriority = (int) $candidates->max('priority');
        $uniqueCandidates = $candidates
            ->where('priority', $highestPriority)
            ->unique(
                fn (array $candidate): string => (string) ($candidate['record']->KOD ?? '')
            );

        if ($uniqueCandidates->count() > 1) {
            return [
                'institution' => null,
                'method' => null,
                'reason' => 'Lebih daripada satu institusi rujukan berpotensi sepadan; semakan manual diperlukan.',
            ];
        }

        return [
            'institution' => $uniqueCandidates->first()['record'],
            'method' => $method,
            'reason' => null,
        ];
    }

    /**
     * @return array{
     *     institution_exact: Collection,
     *     institution_alias: Collection,
     *     specialization_exact: Collection
     * }
     */
    private function loadReferenceData(): array
    {
        $institutions = DB::table('ref_institusi')
            ->where('is_deleted', 0)
            ->get(['KOD', 'DISKRIPSI']);

        $specializations = DB::table('ref_pengkhususan')
            ->where('is_deleted', 0)
            ->get(['kod', 'DISKRIPSI', 'KATEGORI']);

        return $this->prepareReferenceData($institutions, $specializations);
    }

    /**
     * Simpan semua pasangan berjaya menggunakan maksimum tiga query sasaran:
     * satu bacaan, satu bulk update dan satu bulk insert.
     *
     * @param  array<int, array{
     *     kod_institusi: string,
     *     kod_pengkhususan: string,
     *     kategori: mixed,
     *     kod_mqa: mixed
     * }>  $mappings
     */
    private function saveMappings(array $mappings): void
    {
        if ($mappings === []) {
            return;
        }

        $connection = $this->mappingConnection();

        $connection->transaction(function () use (
            $connection,
            $mappings
        ): void {
            $existingMappings = $this->mappingTable($connection)
                ->select(['kod', 'kod_institusi', 'kod_pengkhususan'])
                ->where('is_deleted', 0)
                ->where(function (QueryBuilder $query) use ($mappings): void {
                    foreach ($mappings as $mapping) {
                        $query->orWhere(
                            function (QueryBuilder $pair) use ($mapping): void {
                                $pair
                                    ->where(
                                        'kod_institusi',
                                        $mapping['kod_institusi']
                                    )
                                    ->where(
                                        'kod_pengkhususan',
                                        $mapping['kod_pengkhususan']
                                    );
                            }
                        );
                    }
                })
                ->lockForUpdate()
                ->get()
                ->groupBy(
                    fn (object $mapping): string => $this->mappingPairKey(
                        (string) $mapping->kod_institusi,
                        (string) $mapping->kod_pengkhususan
                    )
                );
            $updatedAt = now();
            $updates = [];
            $inserts = [];

            foreach ($mappings as $mapping) {
                $key = $this->mappingPairKey(
                    $mapping['kod_institusi'],
                    $mapping['kod_pengkhususan']
                );
                $existingRows = $existingMappings->get($key);

                if ($existingRows) {
                    foreach ($existingRows as $existingRow) {
                        $updates[] = [
                            'kod' => $existingRow->kod,
                            'kod_mqa' => $mapping['kod_mqa'],
                            'tarikh_ubahsuai' => $updatedAt,
                        ];
                    }

                    continue;
                }

                $inserts[] = [
                    ...$mapping,
                    'status' => 0,
                    'is_deleted' => 0,
                    'tarikh_cipta' => $updatedAt,
                    'tarikh_ubahsuai' => $updatedAt,
                ];
            }

            if ($updates !== []) {
                $this->bulkUpdateMappings(
                    $connection,
                    $updates,
                    $updatedAt
                );
            }

            if ($inserts !== []) {
                $this->mappingTable($connection)->insert($inserts);
            }
        });
    }

    /**
     * @param  array<int, array{kod: mixed, kod_mqa: mixed, tarikh_ubahsuai: mixed}>  $updates
     */
    private function bulkUpdateMappings(
        Connection $connection,
        array $updates,
        mixed $updatedAt
    ): void {
        $grammar = $connection->getQueryGrammar();
        $table = $grammar->wrapTable($this->mappingTableName());
        $primaryKey = $grammar->wrap('kod');
        $mqaCode = $grammar->wrap('kod_mqa');
        $updatedColumn = $grammar->wrap('tarikh_ubahsuai');
        $caseStatements = [];
        $caseBindings = [];
        $ids = [];

        foreach ($updates as $update) {
            $caseStatements[] = 'when ? then ?';
            $caseBindings[] = $update['kod'];
            $caseBindings[] = $update['kod_mqa'];
            $ids[] = $update['kod'];
        }

        $idPlaceholders = implode(', ', array_fill(0, count($ids), '?'));
        $sql = sprintf(
            'update %s set %s = case %s %s else %s end, %s = ? where %s in (%s)',
            $table,
            $mqaCode,
            $primaryKey,
            implode(' ', $caseStatements),
            $mqaCode,
            $updatedColumn,
            $primaryKey,
            $idPlaceholders
        );

        $connection->update(
            $sql,
            [...$caseBindings, $updatedAt, ...$ids]
        );
    }

    private function mappingConnection(): Connection
    {
        $connection = trim((string) config(
            'services.mqr.mapping_connection',
            'mqr_mapping'
        ));

        if ($connection === '') {
            throw new \RuntimeException(
                'Connection jadual padanan MQR belum dikonfigurasi.'
            );
        }

        return DB::connection($connection);
    }

    private function mappingTable(Connection $connection): QueryBuilder
    {
        return $connection->table($this->mappingTableName());
    }

    private function mappingTableName(): string
    {
        $schema = trim((string) config('services.mqr.mapping_schema'));

        if (
            $schema !== ''
            && preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/', $schema) !== 1
        ) {
            throw new \RuntimeException(
                'Nama schema jadual padanan MQR tidak sah.'
            );
        }

        return $schema === ''
            ? self::MAPPING_TABLE
            : $schema.'.'.self::MAPPING_TABLE;
    }

    private function mappingPairKey(
        string $institutionCode,
        string $specializationCode
    ): string {
        return $institutionCode."\x1F".$specializationCode;
    }

    public function syncData()
    {
        // 1. Tambah masa execution PHP supaya tak timeout (0 bermaksud tiada limit masa)
        set_time_limit(0);

        $url = config('services.mqr.url');

        $log = APILog::create([
            'method' => 'GET',
            'input' => [],
            'headers' => [],
            'url' => $url,
            'user_id' => auth()->id(),
        ]);

        try {
            // Jika API MQR juga lambat respond, kita set timeout untuk HTTP request (cth: 120 saat)
            $response = Http::withOptions(['verify' => false])
                ->timeout(120)
                ->get($url);

            $log->update([
                'output' => 'Data terlalu besar untuk log sepenuhnya', // Elak log penuh
                'status_code' => $response->getStatusCode(),
            ]);

            if ($response->successful()) {
                $data = $response->json('dataMQR');

                if (! $data) {
                    return ['status' => false, 'message' => 'Tiada data ditemui dari API MQR.'];
                }

                $count = count($data);

                // 2. Format semula array untuk padan dengan column di table db
                $formattedData = [];
                foreach ($data as $item) {
                    $formattedData[] = [
                        'kod_rujukan_akreditasi_penuh' => $item['KodRujukanAkreditasiPenuh'],
                        'id_institusi' => $item['IdInstitusi'],
                        'nama_institusi' => $item['NamaInstitusi'],
                        'kod_nec' => $item['KodNEC'],
                        'bidang_nec' => $item['BidangNEC'],
                        'nama_kelayakan_bm' => $item['NamaKelayakanBM'],
                        'nama_kelayakan_bi' => $item['NamaKelayakanBI'],
                        'nama_kelayakan_baru_bm' => $item['NamaKelayakanBaruBM'],
                        'nama_kelayakan_baru_bi' => $item['NamaKelayakanBaruBI'],
                        'peringkat_kelayakan' => $item['PeringkatKelayakan'],
                        'tahap' => $item['Tahap'],
                        'tarikh_akreditasi_mula' => $item['TarikhAkreditasiMula'],
                        'tarikh_akreditasi_tamat' => $item['TarikhAkreditasiTamat'],
                        'tarikh_henti_tamat' => $item['TarikhHentiTamat'],
                    ];
                }

                // 3. Pecahkan kepada 'chunks' (1,000 rekod setiap insert)
                // Ini penting supaya kita tak langgar limit 'Prepared Statement' MySQL
                $chunks = array_chunk($formattedData, 1000);

                foreach ($chunks as $chunk) {
                    MqrData::upsert(
                        $chunk,
                        // Column(s) yang unik untuk check wujud atau tak
                        ['kod_rujukan_akreditasi_penuh'],
                        // Column(s) yang perlu di-update jika rekod dah wujud
                        [
                            'id_institusi', 'nama_institusi', 'kod_nec', 'bidang_nec',
                            'nama_kelayakan_bm', 'nama_kelayakan_bi', 'nama_kelayakan_baru_bm',
                            'nama_kelayakan_baru_bi', 'peringkat_kelayakan', 'tahap',
                            'tarikh_akreditasi_mula', 'tarikh_akreditasi_tamat', 'tarikh_henti_tamat',
                        ]
                    );
                }

                return ['status' => true, 'message' => "Berjaya sync kesemua $count rekod MQR dengan sangat pantas!"];
            }

            return ['status' => false, 'message' => 'Ralat ketika menghubungi API MQR. Kod: '.$response->getStatusCode()];

        } catch (\Exception $e) {
            Log::error('MQR Sync Error: '.$e->getMessage());

            $log->update([
                'output' => $e->getMessage(),
                'status_code' => 500,
            ]);

            return ['status' => false, 'message' => 'Ralat sistem: '.$e->getMessage()];
        }
    }

    /**
     * Fungsi untuk bersihkan teks supaya padanan lebih tepat
     */
    private function normalizeText(?string $text): string
    {
        if (! $text) {
            return '';
        }

        $text = Str::ascii(Str::lower($text));
        $text = preg_replace('/[^a-z0-9]+/', ' ', $text);

        return trim((string) preg_replace('/\s+/', ' ', (string) $text));
    }

    /**
     * Kekalkan kaedah asal bagi kelayakan supaya skop perubahan hanya pada institusi.
     */
    private function normalizeQualificationText(?string $text): string
    {
        if (! $text) {
            return '';
        }

        $text = Str::ascii(Str::lower($text));
        $text = preg_replace('/[^a-z0-9\s]/', '', $text);

        return trim((string) preg_replace('/\s+/', ' ', (string) $text));
    }

    /**
     * Nama rujukan kadangkala menyimpan beberapa nama lama/alternatif dipisahkan "/".
     * Setiap alias dan versi tanpa singkatan kurungan diindeks, tetapi collision kekal
     * sebagai ambigu dan tidak dipilih secara rawak.
     *
     * @return array<int, array{normalized: string, priority: int}>
     */
    private function institutionAliases(?string $name): array
    {
        if (! $name) {
            return [];
        }

        $aliases = [];
        $fullName = $this->normalizeText($name);

        if ($fullName !== '') {
            $aliases[$fullName] = [
                'normalized' => $fullName,
                'priority' => 3,
            ];
        }

        foreach (preg_split('/\s*\/\s*/u', $name) ?: [] as $rawAlias) {
            $normalizedAlias = $this->normalizeText($rawAlias);
            $normalizedWithoutAcronym = $this->normalizeText(
                $this->stripTrailingAcronymQualifier($rawAlias)
            );
            $normalizedWithoutHistoricalMetadata = $this->normalizeText(
                $this->stripTrailingHistoricalNameMetadata($rawAlias)
            );

            foreach ([
                [$normalizedAlias, 2],
                [$normalizedWithoutAcronym, 1],
                [$normalizedWithoutHistoricalMetadata, 1],
            ] as [$normalized, $priority]) {
                if (
                    $normalized !== ''
                    && (! isset($aliases[$normalized]) || $aliases[$normalized]['priority'] < $priority)
                ) {
                    $aliases[$normalized] = [
                        'normalized' => $normalized,
                        'priority' => $priority,
                    ];
                }
            }
        }

        return array_values($aliases);
    }

    /**
     * Buang kurungan akhir hanya apabila kandungannya kelihatan seperti
     * singkatan. Penerangan bermakna seperti "(Malaysia)" tidak dibuang.
     */
    private function stripTrailingAcronymQualifier(?string $text): string
    {
        if (! $text) {
            return '';
        }

        do {
            $previousText = $text;

            if (
                preg_match('/\(([^()]*)\)\s*$/u', $text, $matches) !== 1
                || ! $this->isLikelyInstitutionAcronym($matches[1])
            ) {
                break;
            }

            $text = (string) preg_replace('/\([^()]*\)\s*$/u', '', $text);
        } while ($text !== $previousText);

        return trim($text);
    }

    private function isLikelyInstitutionAcronym(string $value): bool
    {
        $value = trim($value);
        $normalized = $this->normalizeText($value);
        $compact = preg_replace('/[^A-Za-z0-9]/', '', Str::ascii($value));

        if (
            $normalized === ''
            || $compact === null
            || strlen($compact) < 2
            || strlen($compact) > 15
        ) {
            return false;
        }

        $descriptiveQualifiers = [
            'malaysia',
            'singapura',
            'utama',
            'induk',
            'utara',
            'selatan',
            'timur',
            'barat',
            'sabah',
            'sarawak',
            'johor',
            'kedah',
            'kelantan',
            'melaka',
            'negeri sembilan',
            'pahang',
            'perak',
            'perlis',
            'selangor',
            'terengganu',
            'labuan',
            'putrajaya',
            'kuala lumpur',
            'pulau pinang',
            'formerly',
            'previously',
            'dahulu',
        ];

        if (in_array($normalized, $descriptiveQualifiers, true)) {
            return false;
        }

        return preg_match_all('/[A-Z]/', Str::ascii($value)) >= 2;
    }

    /**
     * Sesetengah rujukan menambah nama lama sebagai metadata, bukan sebagai
     * sebahagian nama institusi semasa.
     */
    private function stripTrailingHistoricalNameMetadata(?string $text): string
    {
        if (! $text) {
            return '';
        }

        return trim((string) preg_replace(
            '/\(\s*(?:dahulu\s+dikenali\s+sebagai|formerly\s+known\s+as|formely\s+known\s+as|previously\s+known\s+as)\b[^()]*\)\s*$/iu',
            '',
            $text
        ));
    }

    /**
     * Buang hanya maklumat kampus/cawangan yang ditambah pada hujung nama MQR.
     * Singkatan institusi seperti (UniSZA) dikekalkan sebagai sebahagian identiti.
     */
    private function stripBranchQualifier(?string $text): string
    {
        if (! $text) {
            return '';
        }

        $branchMarkers = 'kampus|campus|cawangan|branch';

        do {
            $previousText = $text;
            $text = (string) preg_replace(
                '/[\s,;\/\-]*\([^)]*\b(?:'.$branchMarkers.')\b[^)]*\)\s*$/iu',
                '',
                $text
            );
        } while ($text !== $previousText);

        $text = (string) preg_replace(
            '/[\s,;\/\-]+\b(?:'.$branchMarkers.')\b\s+\S.*$/iu',
            '',
            $text
        );

        return trim($text, " \t\n\r\0\x0B,;/-");
    }

    private function containsBranchMarker(string $text): bool
    {
        return preg_match('/(?:^|\s)(?:kampus|campus|cawangan|branch)(?:\s|$)/', $text) === 1;
    }

    /**
     * Elak menganggap sambungan nama organisasi sebagai lokasi/cawangan.
     * Contoh: "City University of Hong Kong" tidak boleh dipadankan kepada
     * "City University" jika institusi khusus itu tiada dalam rujukan.
     */
    private function isSafeAdditionalQualifier(string $text): bool
    {
        if ($text === '') {
            return false;
        }

        if ($this->containsBranchMarker($text)) {
            return true;
        }

        return preg_match(
            '/^(?:of|dan|and|untuk|for|the|universiti|university|kolej|college|institut|institute|akademi|academy|pusat|centre|center|sekolah|school|politeknik|polytechnic)(?:\s|$)/',
            $text
        ) !== 1;
    }

    /**
     * Fungsi untuk Automasi Padanan
     */
    public function mapAndSyncPadanan(): array
    {
        set_time_limit(0);

        try {
            $references = $this->loadReferenceData();

            $stats = [
                'total_diperiksa' => 0,
                'berjaya_dipadankan' => 0,
                'gagal_dipadankan' => 0,
            ];

            MqrData::query()
                ->orderBy('id')
                ->chunkById(1000, function (Collection $mqrChunk) use (&$stats, $references): void {
                    DB::transaction(function () use ($mqrChunk, &$stats, $references): void {
                        $successfulMappings = [];

                        foreach ($mqrChunk as $mqr) {
                            $stats['total_diperiksa']++;
                            $mapping = $this->evaluateMapping($mqr, $references);

                            if (! $mapping['success']) {
                                $stats['gagal_dipadankan']++;

                                continue;
                            }

                            $institutionCode = (string) $mapping['institution']->KOD;
                            $specializationCode = (string) $mapping['specialization']->kod;
                            $successfulMappings[$this->mappingPairKey(
                                $institutionCode,
                                $specializationCode
                            )] = [
                                'kod_institusi' => $institutionCode,
                                'kod_pengkhususan' => $specializationCode,
                                'kategori' => $mapping['specialization']->KATEGORI,
                                'kod_mqa' => $mqr->kod_rujukan_akreditasi_penuh,
                            ];
                            $stats['berjaya_dipadankan']++;
                        }

                        $this->saveMappings(array_values($successfulMappings));
                    });
                });

            return [
                'status' => true,
                'message' => 'Proses pemadanan selesai.',
                'data' => $stats,
            ];
        } catch (Throwable $e) {
            Log::error('Mapping Error: '.$e->getMessage());

            return [
                'status' => false,
                'message' => 'Ralat sistem: '.$e->getMessage(),
            ];
        }
    }
}
