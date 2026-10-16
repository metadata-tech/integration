<?php

namespace App\Services;

use App\Models\APILog;
use App\Models\MqrData;
use App\Models\MqrMappingFailure;
use App\Models\MqrMappingRun;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class MQRService
{
    private const MAPPING_TABLE = 'padanan_institusi_pengkhususan';

    private const EDUCATION_NEC_CODES = [
        '0110',
        '0111',
        '0112',
        '0113',
        '0114',
    ];

    private const REQUIRED_QUALIFICATION_LEVEL_CODES = [1, 2, 3, 4, 6, 7];

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

            $this->ensureReferences($mqrData, $run->id);
            $references = $this->loadReferenceData();

            $reportRows = [];
            $successfulMappings = [];
            $failureRows = [];

            foreach ($mqrData as $mqr) {
                $stats['processed']++;
                $mapping = $this->evaluateMapping($mqr, $references);
                $reportRows[] = $this->mappingReportRow($mqr, $mapping);

                if ($mapping['success']) {
                    $institutionCode = (string) $mapping['institution']->KOD;
                    $specializationCode = (string) $mapping['specialization']->kod;
                    $successfulMappings[$this->mqaKey((string) $mqr->kod_rujukan_akreditasi_penuh)] = [
                        'kod_institusi' => $institutionCode,
                        'kod_pengkhususan' => $specializationCode,
                        'id_peringkat_kelulusan' => $mapping['qualification_level_id'],
                        'kategori' => $mapping['specialization']->KATEGORI,
                        'kod_mqa' => $mqr->kod_rujukan_akreditasi_penuh,
                    ];

                    $stats['berjaya']++;
                    $stats['latest_berjaya'][] = "{$mqr->nama_institusi} - {$mqr->nama_kelayakan_bm}";

                    continue;
                }

                $stats['gagal']++;
                $stats['latest_gagal'][] = "{$mqr->nama_institusi} - {$mqr->nama_kelayakan_bm} ({$mapping['reason']})";
                $failureRows[] = $this->failureRecord($run->id, $mqr, $mapping);
            }

            if ($failureRows !== []) {
                MqrMappingFailure::query()->insert($failureRows);
            }

            $this->saveMappings(array_values($successfulMappings), $run->id);
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
     *     specialization_exact: Collection<string, object>,
     *     qualification_level_codes?: Collection<int, int>
     * }
     */
    public function prepareReferenceData(Collection $institutions, Collection $specializations): array
    {
        $this->institutionMatchCache = [];
        $lowestCode = function (object $a, object $b, string $column): object {
            $first = (string) $a->{$column};
            $second = (string) $b->{$column};
            if (ctype_digit($first) && ctype_digit($second)) {
                return (int) $first <= (int) $second ? $a : $b;
            }
            if (ctype_digit($first) !== ctype_digit($second)) {
                return ctype_digit($first) ? $a : $b;
            }

            return strcmp($first, $second) <= 0 ? $a : $b;
        };
        $institutionIndex = collect();
        foreach ($institutions as $institution) {
            $key = $this->exactNameKey($institution->DISKRIPSI ?? null);
            if ($key !== '') {
                $institutionIndex[$key] = isset($institutionIndex[$key])
                    ? $lowestCode($institutionIndex[$key], $institution, 'KOD')
                    : $institution;
            }
        }
        $specializationIndex = collect();
        foreach ($specializations as $specialization) {
            $key = $this->exactNameKey($specialization->DISKRIPSI ?? null);
            if ($key !== '') {
                $specializationIndex[$key] = isset($specializationIndex[$key])
                    ? $lowestCode($specializationIndex[$key], $specialization, 'kod')
                    : $specialization;
            }
        }

        return [
            'institution_exact' => $institutionIndex,
            'institution_alias' => $institutionIndex,
            'specialization_exact' => $specializationIndex,
        ];
    }

    /**
     * Nilai satu rekod menggunakan logik yang sama untuk mapping, live feed dan laporan.
     *
     * @param  array{
     *     institution_exact: Collection,
     *     institution_alias: Collection,
     *     specialization_exact: Collection,
     *     qualification_level_codes?: Collection
     * }  $references
     * @return array{
     *     success: bool,
     *     reason: string,
     *     institution: ?object,
     *     specialization: ?object,
     *     institution_match_method: ?string,
     *     qualification_level_id: ?int
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
                $this->exactNameKey($specializationName)
            );

            if (! $specialization) {
                $reasons[] = 'Tiada pengkhususan yang sepadan dalam jadual ref_pengkhususan.';
            }
        }

        $success = $reasons === [];
        $qualificationLevelId = $this->resolveQualificationLevelId(
            $mqr,
            $references
        );

        return [
            'success' => $success,
            'reason' => $success
                ? 'Padanan institusi dan pengkhususan berjaya ditemui.'
                : implode(' ', $reasons),
            'institution' => $institutionResult['institution'],
            'specialization' => $specialization,
            'institution_match_method' => $institutionResult['method'],
            'qualification_level_id' => $qualificationLevelId,
        ];
    }

    /**
     * Dapatkan kod sebenar ref_peringkat_kelulusan berdasarkan medan API MQR.
     *
     * @param  array{qualification_level_codes?: Collection}  $references
     */
    private function resolveQualificationLevelId(
        object $mqr,
        array $references
    ): ?int {
        $level = trim((string) ($mqr->peringkat_kelayakan ?? ''));
        $necCode = trim((string) ($mqr->kod_nec ?? ''));
        $isEducationNec = in_array(
            $necCode,
            self::EDUCATION_NEC_CODES,
            true
        );

        $referenceCode = match (true) {
            in_array($level, ['3', '4', '6', '9'], true) => $isEducationNec ? 1 : 2,
            $level === '7' => $isEducationNec ? 3 : 4,
            in_array($level, ['8', '10'], true) => 6,
            $level === '11' => 7,
            default => null,
        };

        if ($referenceCode === null) {
            return null;
        }

        $qualificationLevelCodes = $references['qualification_level_codes']
            ?? null;

        if (! $qualificationLevelCodes instanceof Collection) {
            return null;
        }

        $qualificationLevelId = $qualificationLevelCodes->get($referenceCode);

        return $qualificationLevelId === null
            ? null
            : (int) $qualificationLevelId;
    }

    /**
     * Proses semua keputusan secara chunk terus daripada data MQR.
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
        $key = $this->exactNameKey($name);
        if ($key === '') {
            return ['institution' => null, 'method' => null, 'reason' => 'Nama institusi tidak dibekalkan oleh API MQR.'];
        }
        $institution = $references['institution_exact']->get($key);

        return [
            'institution' => $institution,
            'method' => $institution ? 'Nama tepat' : null,
            'reason' => $institution ? null : 'Tiada institusi yang sepadan dalam jadual ref_institusi.',
        ];
    }

    private function exactNameKey(?string $name): string
    {
        return mb_strtolower(trim((string) $name), 'UTF-8');
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
     *     specialization_exact: Collection,
     *     qualification_level_codes: Collection<int, int>
     * }
     */
    private function loadReferenceData(): array
    {
        // Reference tables live in the same database as the mapping table
        // (spamenu), not in this app's own database.
        $connection = $this->mappingConnection();

        $institutions = $connection
            ->table($this->qualifiedTableName('ref_institusi'))
            ->where('is_deleted', 0)
            ->get(['KOD', 'DISKRIPSI']);

        $specializations = $connection
            ->table($this->qualifiedTableName('ref_pengkhususan'))
            ->where('is_deleted', 0)
            ->get(['kod', 'DISKRIPSI', 'KATEGORI']);

        $qualificationLevelCodes = $connection
            ->table($this->qualifiedTableName('ref_peringkat_kelulusan'))
            ->where('is_deleted', 0)
            ->whereIn('kod', self::REQUIRED_QUALIFICATION_LEVEL_CODES)
            ->pluck('kod')
            ->mapWithKeys(
                fn (mixed $code): array => [(int) $code => (int) $code]
            );
        $missingQualificationLevelCodes = array_values(array_diff(
            self::REQUIRED_QUALIFICATION_LEVEL_CODES,
            $qualificationLevelCodes->keys()->all()
        ));

        if ($missingQualificationLevelCodes !== []) {
            throw new \RuntimeException(sprintf(
                'Kod ref_peringkat_kelulusan tidak ditemui atau telah dipadam: %s.',
                implode(', ', $missingQualificationLevelCodes)
            ));
        }

        $references = $this->prepareReferenceData(
            $institutions,
            $specializations
        );
        $references['qualification_level_codes'] = $qualificationLevelCodes;

        return $references;
    }

    /** Daftar nama MQR yang belum ada sebelum batch dinilai. */
    private function ensureReferences(Collection $records, ?string $runId = null, ?string $kind = null): int
    {
        $names = ['institution' => [], 'specialization' => []];
        foreach ($records as $record) {
            if (trim((string) ($record->kod_rujukan_akreditasi_penuh ?? '')) === ''
                || trim((string) ($record->nama_institusi ?? '')) === ''
                || trim((string) ($record->nama_kelayakan_bm ?? '')) === '') {
                continue;
            }
            foreach (['institution' => 'nama_institusi', 'specialization' => 'nama_kelayakan_bm'] as $type => $field) {
                if ($kind !== null && $kind !== $type) {
                    continue;
                }
                $raw = trim((string) ($record->{$field} ?? ''));
                if ($raw !== '') {
                    $names[$type][$this->exactNameKey($raw)] = $raw;
                }
            }
        }
        if ($names['institution'] === [] && $names['specialization'] === []) {
            return 0;
        }

        $educationNames = [];
        if ($names['specialization'] !== []) {
            $keys = array_keys($names['specialization']);
            foreach (array_chunk($keys, 300) as $chunk) {
                $placeholders = implode(',', array_fill(0, count($chunk), '?'));
                $rows = DB::table('mqr_data')->whereIn('kod_nec', self::EDUCATION_NEC_CODES)
                    ->whereRaw('LOWER(TRIM(nama_kelayakan_bm)) IN ('.$placeholders.')', $chunk)
                    ->pluck('nama_kelayakan_bm');
                foreach ($rows as $name) {
                    $educationNames[$this->exactNameKey($name)] = true;
                }
            }
        }

        $connection = $this->mappingConnection();
        $hasLock = false;
        if ($connection->getDriverName() === 'mysql') {
            $hasLock = (int) ($connection->selectOne("SELECT GET_LOCK('mqr_reference_allocation', 15) AS acquired")->acquired ?? 0) === 1;
            if (! $hasLock) {
                throw new \RuntimeException('Kunci pendaftaran rujukan MQR tidak dapat diperoleh.');
            }
        }

        try {
            return $connection->transaction(function () use ($connection, $names, $educationNames, $runId): int {
                $registered = 0;
                foreach (['institution' => ['ref_institusi', 'KOD'], 'specialization' => ['ref_pengkhususan', 'kod']] as $type => [$table, $codeColumn]) {
                    if ($names[$type] === []) {
                        continue;
                    }
                    $qualified = $this->qualifiedTableName($table);
                    $columns = $this->referenceColumns($qualified);
                    $nameColumn = $this->requireColumn($columns, 'DISKRIPSI');
                    $typeColumn = $type === 'institution'
                        ? $this->requireColumn($columns, 'JENIS_INSTITUSI')
                        : null;
                    $existingRows = $connection->table($qualified)->where('is_deleted', 0)
                        ->get($type === 'institution'
                            ? [$nameColumn, $typeColumn, $this->requireColumn($columns, 'KUMPULAN_INSTITUSI')]
                            : [$nameColumn]);
                    $groupTypes = $type === 'institution'
                        ? MQRInstitutionType::groupTypes($existingRows)
                        : [];
                    $existing = [];
                    foreach ($existingRows as $row) {
                        $existing[$this->exactNameKey($row->{$nameColumn})] = true;
                    }
                    $nextCode = $this->nextReferenceCode($connection, $qualified, $codeColumn, $type === 'institution');
                    foreach ($names[$type] as $key => $name) {
                        if (isset($existing[$key])) {
                            continue;
                        }
                        $group = $type === 'institution' ? MQRInstitutionGroup::resolve($name) : null;
                        $category = $type === 'specialization' ? (isset($educationNames[$key]) ? 1 : 2) : null;
                        $registeredAt = now();
                        $row = [$codeColumn => $nextCode, $nameColumn => $name];
                        $row[$this->requireColumn($columns, 'TARIKH_CIPTA')] = $registeredAt;
                        $row[$this->requireColumn($columns, 'TARIKH_UBAHSUAI')] = $registeredAt;
                        if ($this->optionalColumn($columns, 'is_deleted') !== null) {
                            $row['is_deleted'] = 0;
                        }
                        if ($type === 'institution') {
                            $row[$this->requireColumn($columns, 'KUMPULAN_INSTITUSI')] = $group;
                            $row[$typeColumn] = MQRInstitutionType::resolve($name, $group, $groupTypes);
                            if ($this->optionalColumn($columns, 'SAH_YT') !== null) {
                                $row['SAH_YT'] = 'Y';
                            }
                        } else {
                            $row[$this->requireColumn($columns, 'KATEGORI')] = $category;
                        }
                        $this->assertReferenceRowAllowed($columns, $row);
                        $connection->table($qualified)->insert($row);
                        $connection->table($this->qualifiedTableName('mqr_reference_registrations'))->insert([
                            'run_id' => $runId,
                            'jenis' => $type,
                            'kod' => $nextCode,
                            'nama' => $name,
                            'kumpulan_institusi' => $group,
                            'kategori' => $category,
                            'created_at' => $registeredAt,
                        ]);
                        $registered++;
                        $nextCode = $this->incrementReferenceCode($nextCode, $type === 'institution');
                    }
                }

                return $registered;
            });
        } finally {
            if ($hasLock) {
                $connection->selectOne("SELECT RELEASE_LOCK('mqr_reference_allocation') AS released");
            }
        }
    }

    /**
     * Simpan satu rekod aktif bagi setiap kod MQA dan audit rekod pendua.
     *
     * @param  array<int, array{
     *     kod_institusi: string,
     *     kod_pengkhususan: string,
     *     id_peringkat_kelulusan: ?int,
     *     kategori: mixed,
     *     kod_mqa: mixed
     * }>  $mappings
     */
    private function saveMappings(array $mappings, ?string $runId = null): void
    {
        if ($mappings === []) {
            return;
        }
        $connection = $this->mappingConnection();
        $hasNormalizedColumn = $this->optionalColumn(
            $this->referenceColumns($this->mappingTableName()),
            'mqr_kod_mqa_aktif'
        ) !== null;
        $connection->transaction(function () use ($connection, $mappings, $runId, $hasNormalizedColumn): void {
            foreach ($mappings as $mapping) {
                $code = $this->mqaKey((string) $mapping['kod_mqa']);
                if ($code === '') {
                    continue;
                }
                $query = $this->mappingTable($connection)->where('is_deleted', 0);
                if ($hasNormalizedColumn) {
                    $query->where('mqr_kod_mqa_aktif', $code);
                } else {
                    $query->whereRaw('UPPER(TRIM(kod_mqa)) = ?', [$code]);
                }
                $rows = $query->orderBy('kod')->lockForUpdate()->get();
                $kept = $rows->first();
                if ($kept === null) {
                    $kept = $this->mappingTable($connection)->where('is_deleted', 0)
                        ->where('kod_institusi', $mapping['kod_institusi'])
                        ->where('kod_pengkhususan', $mapping['kod_pengkhususan'])
                        ->where(fn (QueryBuilder $query) => $query->whereNull('kod_mqa')->orWhereRaw("TRIM(kod_mqa) = ''"))
                        ->orderBy('kod')->lockForUpdate()->first();
                }
                foreach ($rows->skip(1) as $extra) {
                    $this->mappingTable($connection)->where('kod', $extra->kod)
                        ->update(['is_deleted' => 1, 'tarikh_ubahsuai' => now()]);
                    $connection->table($this->qualifiedTableName('mqr_mapping_duplicate_audits'))->insert([
                        'run_id' => $runId,
                        'kod_mqa' => $code,
                        'kod_dikekalkan' => $kept->kod,
                        'kod_dinyahaktifkan' => $extra->kod,
                        'kod_institusi_lama' => $extra->kod_institusi,
                        'kod_pengkhususan_lama' => $extra->kod_pengkhususan,
                        'kod_institusi_akhir' => $mapping['kod_institusi'],
                        'kod_pengkhususan_akhir' => $mapping['kod_pengkhususan'],
                        'hasil' => 'BERJAYA',
                        'created_at' => now(),
                    ]);
                }

                if ($kept !== null) {
                    $update = [
                        'kod_institusi' => $mapping['kod_institusi'],
                        'kod_pengkhususan' => $mapping['kod_pengkhususan'],
                        'kategori' => $mapping['kategori'],
                        'kod_mqa' => trim((string) $mapping['kod_mqa']),
                        'tarikh_ubahsuai' => now(),
                    ];
                    if ($mapping['id_peringkat_kelulusan'] !== null) {
                        $update['id_peringkat_kelulusan'] = $mapping['id_peringkat_kelulusan'];
                    }
                    $this->mappingTable($connection)->where('kod', $kept->kod)->update($update);
                } else {
                    $this->mappingTable($connection)->insert([
                        ...$mapping,
                        'kod_mqa' => trim((string) $mapping['kod_mqa']),
                        'status' => 0,
                        'is_deleted' => 0,
                        'tarikh_cipta' => now(),
                        'tarikh_ubahsuai' => now(),
                    ]);
                }

                $connection->table($this->qualifiedTableName('mqr_mapping_duplicate_audits'))
                    ->where('kod_mqa', $code)->where('kod_dikekalkan', $kept->kod ?? -1)
                    ->where(function (QueryBuilder $query) use ($runId): void {
                        $query->whereNull('run_id');
                        if ($runId !== null) {
                            $query->orWhere('run_id', $runId);
                        }
                    })
                    ->update([
                        'kod_institusi_akhir' => $mapping['kod_institusi'],
                        'kod_pengkhususan_akhir' => $mapping['kod_pengkhususan'],
                        'hasil' => 'BERJAYA',
                    ]);
            }
        });
    }

    private function mqaKey(string $code): string
    {
        return mb_strtoupper(trim($code), 'UTF-8');
    }

    /**
     * @param  array<int, array{kod: mixed, kod_mqa: mixed, id_peringkat_kelulusan: ?int, tarikh_ubahsuai: mixed}>  $updates
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
        $qualificationLevelId = $grammar->wrap('id_peringkat_kelulusan');
        $updatedColumn = $grammar->wrap('tarikh_ubahsuai');
        $caseStatements = [];
        $caseBindings = [];
        $qualificationLevelCaseStatements = [];
        $qualificationLevelCaseBindings = [];
        $ids = [];

        foreach ($updates as $update) {
            $caseStatements[] = 'when ? then ?';
            $caseBindings[] = $update['kod'];
            $caseBindings[] = $update['kod_mqa'];

            if ($update['id_peringkat_kelulusan'] !== null) {
                $qualificationLevelCaseStatements[] = 'when ? then ?';
                $qualificationLevelCaseBindings[] = $update['kod'];
                $qualificationLevelCaseBindings[] = $update['id_peringkat_kelulusan'];
            }

            $ids[] = $update['kod'];
        }

        $qualificationLevelExpression = $qualificationLevelCaseStatements === []
            ? $qualificationLevelId
            : sprintf(
                'case %s %s else %s end',
                $primaryKey,
                implode(' ', $qualificationLevelCaseStatements),
                $qualificationLevelId
            );

        $idPlaceholders = implode(', ', array_fill(0, count($ids), '?'));
        $sql = sprintf(
            'update %s set %s = case %s %s else %s end, %s = %s, %s = ? where %s in (%s)',
            $table,
            $mqaCode,
            $primaryKey,
            implode(' ', $caseStatements),
            $mqaCode,
            $qualificationLevelId,
            $qualificationLevelExpression,
            $updatedColumn,
            $primaryKey,
            $idPlaceholders
        );

        $connection->update(
            $sql,
            [
                ...$caseBindings,
                ...$qualificationLevelCaseBindings,
                $updatedAt,
                ...$ids,
            ]
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
        return $this->qualifiedTableName(self::MAPPING_TABLE);
    }

    private function qualifiedTableName(string $table): string
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

        return $schema === '' ? $table : $schema.'.'.$table;
    }

    private function mappingPairKey(
        string $institutionCode,
        string $specializationCode
    ): string {
        return $institutionCode."\x1F".$specializationCode;
    }

    private function mappingRecordKey(
        string $institutionCode,
        string $specializationCode,
        string $mqaCode
    ): string {
        return $this->mappingPairKey($institutionCode, $specializationCode)."\x1F".$mqaCode;
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

            if ($normalizedWithoutAcronym === 'institut pendidikan guru malaysia') {
                // MQR menggunakan IPG untuk kampus; rujukan menyimpan IPGM.
                $aliases['institut pendidikan guru ipg'] = [
                    'normalized' => 'institut pendidikan guru ipg',
                    'priority' => 2,
                ];
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
            $stats = [
                'total_diperiksa' => 0,
                'berjaya_dipadankan' => 0,
                'gagal_dipadankan' => 0,
                'rujukan_baharu' => 0,
            ];

            MqrData::query()
                ->orderBy('id')
                ->chunkById(1000, function (Collection $mqrChunk) use (&$stats): void {
                    $stats['rujukan_baharu'] += $this->ensureReferences($mqrChunk);
                    $references = $this->loadReferenceData();
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
                            $successfulMappings[$this->mqaKey((string) $mqr->kod_rujukan_akreditasi_penuh)] = [
                                'kod_institusi' => $institutionCode,
                                'kod_pengkhususan' => $specializationCode,
                                'id_peringkat_kelulusan' => $mapping['qualification_level_id'],
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

    /**
     * @param  array{success: bool, reason: string}  $mapping
     * @return array<string, mixed>
     */
    private function failureRecord(string $runId, object $mqr, array $mapping): array
    {
        $reason = $mapping['reason'];
        $now = now();

        return [
            'run_id' => $runId,
            'mqr_data_id' => (int) $mqr->id,
            'kod_mqa' => $mqr->kod_rujukan_akreditasi_penuh,
            'nama_institusi' => $mqr->nama_institusi,
            'nama_kelayakan' => $mqr->nama_kelayakan_bm,
            'reason' => $reason,
            'missing_institution' => str_contains(
                $reason,
                'Tiada institusi yang sepadan'
            ) ? 1 : 0,
            'missing_specialization' => (
                str_contains($reason, 'Tiada pengkhususan yang sepadan')
                || str_contains($reason, 'Nama kelayakan BM tidak dibekalkan')
            ) ? 1 : 0,
            'ambiguous_institution' => str_contains(
                $reason,
                'Lebih daripada satu institusi'
            ) ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * Nilai semula data MQR semasa dan ganti senarai gagal larian terakhir.
     * Padanan yang sudah berjaya tidak ditulis semula.
     *
     * @return array{status: bool, message: string, count: int}
     */
    public function rebuildFailureList(): array
    {
        $run = MqrMappingRun::query()
            ->completed()
            ->latest('completed_at')
            ->first();

        if ($run === null) {
            throw new \RuntimeException(
                'Tiada padanan selesai. Jalankan Automasi Padanan dahulu.'
            );
        }

        $references = $this->loadReferenceData();
        $count = 0;

        DB::transaction(function () use ($run, $references, &$count): void {
            MqrMappingFailure::query()
                ->where('run_id', $run->id)
                ->delete();

            MqrData::query()
                ->orderBy('id')
                ->chunkById(200, function (Collection $records) use (
                    $run,
                    $references,
                    &$count
                ): void {
                    $rows = [];

                    foreach ($records as $mqr) {
                        $mapping = $this->evaluateMapping($mqr, $references);

                        if ($mapping['success']) {
                            continue;
                        }

                        $rows[] = $this->failureRecord($run->id, $mqr, $mapping);
                    }

                    if ($rows !== []) {
                        MqrMappingFailure::query()->insert($rows);
                        $count += count($rows);
                    }
                });
        });

        return [
            'status' => true,
            'count' => $count,
            'message' => "Senarai gagal dikemas kini. {$count} rekod gagal.",
        ];
    }

    /**
     * Daftar nama yang tiada ke jadual rujukan, kemudian padan semula rekod terpilih.
     *
     * @param  array<int, mixed>  $failureIds
     * @return array{status: bool, message: string}
     */
    public function registerMissingReferences(array $failureIds, string $kind): array
    {
        if (! in_array($kind, ['institution', 'specialization'], true)) {
            throw new \RuntimeException('Jenis daftar rujukan tidak sah.');
        }

        $run = MqrMappingRun::query()
            ->completed()
            ->latest('completed_at')
            ->first();

        if ($run === null) {
            throw new \RuntimeException(
                'Tiada padanan selesai. Jalankan Automasi Padanan dahulu.'
            );
        }

        $ids = array_values(array_unique(array_map(
            intval(...),
            $failureIds
        )));
        $failures = MqrMappingFailure::query()
            ->where('run_id', $run->id)
            ->whereIn('id', $ids)
            ->get();
        $eligible = $failures->filter(
            fn (MqrMappingFailure $failure): bool => $kind === 'institution'
                ? $failure->allowsInstitutionRegistration()
                : $failure->allowsSpecializationRegistration()
        );

        if ($eligible->isEmpty()) {
            throw new \RuntimeException(
                'Tiada rekod terpilih yang boleh didaftarkan untuk tindakan ini.'
            );
        }

        $registered = $this->insertMissingReferences($eligible, $kind);
        $rematch = $this->rematchFailures($run->id, $eligible->pluck('mqr_data_id')->all());

        return [
            'status' => true,
            'message' => sprintf(
                '%d rujukan didaftarkan. %d rekod dipadankan. %d rekod masih gagal.',
                $registered,
                $rematch['matched'],
                $rematch['failed']
            ),
        ];
    }

    /**
     * @param  Collection<int, MqrMappingFailure>  $failures
     */
    private function insertMissingReferences(Collection $failures, string $kind): int
    {
        $runId = $failures->first()?->run_id;
        $records = MqrData::query()->whereIn('id', $failures->pluck('mqr_data_id'))->get();

        return $this->ensureReferences($records, $runId, $kind);
    }

    /**
     * @param  array<int, int>  $mqrDataIds
     * @return array{matched: int, failed: int}
     */
    private function rematchFailures(string $runId, array $mqrDataIds): array
    {
        $records = MqrData::query()
            ->whereIn('id', $mqrDataIds)
            ->get();
        $references = $this->loadReferenceData();
        $successfulMappings = [];
        $matchedIds = [];
        $failed = 0;

        foreach ($records as $mqr) {
            $mapping = $this->evaluateMapping($mqr, $references);

            if ($mapping['success']) {
                $institutionCode = (string) $mapping['institution']->KOD;
                $specializationCode = (string) $mapping['specialization']->kod;
                $successfulMappings[$this->mqaKey((string) $mqr->kod_rujukan_akreditasi_penuh)] = [
                    'kod_institusi' => $institutionCode,
                    'kod_pengkhususan' => $specializationCode,
                    'id_peringkat_kelulusan' => $mapping['qualification_level_id'],
                    'kategori' => $mapping['specialization']->KATEGORI,
                    'kod_mqa' => $mqr->kod_rujukan_akreditasi_penuh,
                ];
                $matchedIds[] = (int) $mqr->id;

                continue;
            }

            $failed++;
            $replacement = $this->failureRecord($runId, $mqr, $mapping);
            unset($replacement['created_at']);
            MqrMappingFailure::query()
                ->where('run_id', $runId)
                ->where('mqr_data_id', $mqr->id)
                ->update($replacement);
        }

        $this->saveMappings(array_values($successfulMappings), $runId);

        if ($matchedIds !== []) {
            MqrMappingFailure::query()
                ->where('run_id', $runId)
                ->whereIn('mqr_data_id', $matchedIds)
                ->delete();
        }

        return [
            'matched' => count($matchedIds),
            'failed' => $failed,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function referenceColumns(string $table): array
    {
        $connection = $this->mappingConnection();

        if (! str_contains($table, '.')) {
            return Schema::connection($connection->getName())->getColumns($table);
        }

        if ($connection->getDriverName() !== 'mysql') {
            throw new \RuntimeException('Skema berasingan hanya disokong untuk MySQL.');
        }

        [$schema, $name] = explode('.', $table, 2);

        return $connection->table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $schema)
            ->where('TABLE_NAME', $connection->getTablePrefix().$name)
            ->orderBy('ORDINAL_POSITION')
            ->get()
            ->map(fn ($column) => [
                'name' => $column->COLUMN_NAME,
                'nullable' => $column->IS_NULLABLE === 'YES',
                'default' => $column->COLUMN_DEFAULT,
                'auto_increment' => str_contains(strtolower((string) $column->EXTRA), 'auto_increment'),
            ])
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $columns
     */
    private function requireColumn(array $columns, string $wanted): string
    {
        $column = $this->optionalColumn($columns, $wanted);

        if ($column === null) {
            throw new \RuntimeException(
                "Lajur {$wanted} tidak dijumpai pada jadual rujukan."
            );
        }

        return $column;
    }

    /**
     * @param  array<int, array<string, mixed>>  $columns
     */
    private function optionalColumn(array $columns, string $wanted): ?string
    {
        foreach ($columns as $column) {
            if (strcasecmp((string) $column['name'], $wanted) === 0) {
                return (string) $column['name'];
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $columns
     * @param  array<string, mixed>  $row
     */
    private function assertReferenceRowAllowed(array $columns, array $row): void
    {
        $missing = [];

        foreach ($columns as $column) {
            $name = (string) $column['name'];

            if (array_key_exists($name, $row)) {
                continue;
            }

            if (($column['auto_increment'] ?? false) === true) {
                continue;
            }

            if (($column['nullable'] ?? false) === true) {
                continue;
            }

            if (array_key_exists('default', $column) && $column['default'] !== null) {
                continue;
            }

            $missing[] = $name;
        }

        if ($missing !== []) {
            throw new \RuntimeException(
                'Jadual rujukan memerlukan lajur wajib yang tidak diisi: '
                .implode(', ', $missing).'.'
            );
        }
    }

    private function nextReferenceCode(
        Connection $connection,
        string $table,
        string $column,
        bool $pad
    ): string {
        $width = $pad ? 5 : 1;
        $max = 0;

        foreach ($connection->table($table)->pluck($column) as $code) {
            $code = (string) $code;

            if (preg_match('/\A\d+\z/', $code) !== 1) {
                continue;
            }

            $max = max($max, (int) $code);

            if ($pad) {
                $width = max($width, strlen($code));
            }
        }

        $next = (string) ($max + 1);

        return $pad ? str_pad($next, $width, '0', STR_PAD_LEFT) : $next;
    }

    private function incrementReferenceCode(string $code, bool $pad): string
    {
        $next = (string) (((int) $code) + 1);

        return $pad ? str_pad($next, strlen($code), '0', STR_PAD_LEFT) : $next;
    }
}
