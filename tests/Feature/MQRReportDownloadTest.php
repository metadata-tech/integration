<?php

use App\Livewire\MQRForm;
use App\Models\MqrMappingRun;
use App\Models\User;
use App\Services\MQRReportService;
use App\Services\MQRService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    config()->set('services.mqr.mapping_connection', 'mqr_mapping');
    config()->set('services.mqr.mapping_schema', null);
    DB::purge('mqr_mapping');

    Schema::dropIfExists('mqr_data');
    Schema::create('mqr_data', function (Blueprint $table): void {
        $table->id();
        $table->string('kod_rujukan_akreditasi_penuh')->nullable();
        $table->string('id_institusi')->nullable();
        $table->string('nama_institusi')->nullable();
        $table->text('nama_kelayakan_bm')->nullable();
        $table->string('kod_nec')->nullable();
        $table->string('peringkat_kelayakan')->nullable();
    });

    $createInstitutionTable = function (Blueprint $table): void {
        $table->string('KOD')->primary();
        $table->string('DISKRIPSI');
        $table->string('KUMPULAN_INSTITUSI')->nullable();
        $table->string('JENIS_INSTITUSI')->nullable();
        $table->dateTime('TARIKH_CIPTA')->nullable();
        $table->dateTime('TARIKH_UBAHSUAI')->nullable();
        $table->boolean('is_deleted')->default(false);
    };
    Schema::create('ref_institusi', $createInstitutionTable);
    Schema::connection('mqr_mapping')->create(
        'ref_institusi',
        $createInstitutionTable
    );

    $createSpecializationTable = function (Blueprint $table): void {
        $table->string('kod')->primary();
        $table->string('DISKRIPSI');
        $table->integer('KATEGORI')->nullable();
        $table->dateTime('TARIKH_CIPTA')->nullable();
        $table->dateTime('TARIKH_UBAHSUAI')->nullable();
        $table->boolean('is_deleted')->default(false);
    };
    Schema::create('ref_pengkhususan', $createSpecializationTable);
    Schema::connection('mqr_mapping')->create(
        'ref_pengkhususan',
        $createSpecializationTable
    );

    Schema::connection('mqr_mapping')->create(
        'ref_peringkat_kelulusan',
        function (Blueprint $table): void {
            $table->integer('kod')->primary();
            $table->string('diskripsi')->nullable();
            $table->boolean('is_deleted')->default(false);
        }
    );

    $createMappingTable = function (Blueprint $table): void {
        $table->bigIncrements('kod');
        $table->string('kod_institusi');
        $table->string('kod_pengkhususan');
        $table->integer('id_peringkat_kelulusan')->nullable();
        $table->integer('peringkat_kelulusan')->nullable();
        $table->integer('kategori')->nullable();
        $table->string('kod_mqa')->nullable();
        $table->integer('status')->default(0);
        $table->boolean('is_deleted')->default(false);
        $table->timestamp('tarikh_cipta')->nullable();
        $table->timestamp('tarikh_ubahsuai')->nullable();
        $table->integer('id_pencipta')->nullable();
        $table->integer('id_pengubahsuai')->nullable();
    };

    Schema::create(
        'padanan_institusi_pengkhususan',
        $createMappingTable
    );
    Schema::connection('mqr_mapping')->create(
        'padanan_institusi_pengkhususan',
        $createMappingTable
    );

    Schema::connection('mqr_mapping')->create('mqr_reference_registrations', function (Blueprint $table): void {
        $table->id();
        $table->string('run_id')->nullable();
        $table->string('jenis');
        $table->string('kod');
        $table->text('nama');
        $table->string('kumpulan_institusi')->nullable();
        $table->integer('kategori')->nullable();
        $table->timestamp('created_at')->nullable();
    });
    Schema::connection('mqr_mapping')->create('mqr_mapping_duplicate_audits', function (Blueprint $table): void {
        $table->id();
        $table->string('run_id')->nullable();
        $table->string('kod_mqa');
        $table->integer('kod_dikekalkan');
        $table->integer('kod_dinyahaktifkan');
        $table->string('kod_institusi_lama')->nullable();
        $table->string('kod_pengkhususan_lama')->nullable();
        $table->string('kod_institusi_akhir')->nullable();
        $table->string('kod_pengkhususan_akhir')->nullable();
        $table->string('hasil');
        $table->timestamp('created_at')->nullable();
    });

    DB::table('ref_institusi')->insert([
        'KOD' => '00038',
        'DISKRIPSI' => 'UNIVERSITI SULTAN ZAINAL ABIDIN (UNISZA)',
        'is_deleted' => false,
    ]);
    DB::connection('mqr_mapping')->table('ref_institusi')->insert([
        'KOD' => '00038',
        'DISKRIPSI' => 'UNIVERSITI SULTAN ZAINAL ABIDIN (UNISZA)',
        'KUMPULAN_INSTITUSI' => 'UNISZA',
        'JENIS_INSTITUSI' => '1',
        'is_deleted' => false,
    ]);
    DB::connection('mqr_mapping')->table('ref_institusi')->insert([
        'KOD' => '00012',
        'DISKRIPSI' => 'INSTITUT PENDIDIKAN GURU MALAYSIA (IPGM)',
        'KUMPULAN_INSTITUSI' => 'IPGM',
        'JENIS_INSTITUSI' => '1',
        'is_deleted' => false,
    ]);
    DB::table('ref_pengkhususan')->insert([
        'kod' => 'P001',
        'DISKRIPSI' => 'Sarjana Muda Teknologi Maklumat',
        'KATEGORI' => 1,
        'is_deleted' => false,
    ]);
    DB::connection('mqr_mapping')->table('ref_pengkhususan')->insert([
        'kod' => 'P001',
        'DISKRIPSI' => 'Sarjana Muda Teknologi Maklumat',
        'KATEGORI' => 1,
        'is_deleted' => false,
    ]);
    DB::connection('mqr_mapping')
        ->table('ref_peringkat_kelulusan')
        ->insert(array_map(
            fn (int $code): array => [
                'kod' => $code,
                'diskripsi' => "Peringkat {$code}",
                'is_deleted' => false,
            ],
            [1, 2, 3, 4, 6, 7]
        ));
    DB::table('mqr_data')->insert([
        [
            'kod_rujukan_akreditasi_penuh' => 'MQA/FA0001',
            'id_institusi' => 'MQR001',
            'nama_institusi' => 'Universiti Sultan Zainal Abidin (UniSZA) (Kampus Gong Badak)',
            'nama_kelayakan_bm' => 'Sarjana Muda Teknologi Maklumat',
            'kod_nec' => '0112',
            'peringkat_kelayakan' => '7',
        ],
        [
            'kod_rujukan_akreditasi_penuh' => 'MQA/FA0002',
            'id_institusi' => 'MQR002',
            'nama_institusi' => '=INSTITUSI_TIDAK_WUJUD',
            'nama_kelayakan_bm' => 'Kelayakan Tidak Wujud',
            'kod_nec' => '0210',
            'peringkat_kelayakan' => '3',
        ],
    ]);
});

afterEach(function () {
    Date::setTestNow();
    DB::purge('mqr_mapping');
});

it('only publishes Excel-compatible reports after every batch is complete', function () {
    Date::setTestNow('2026-07-28 10:11:12');

    $reports = app(MQRReportService::class);
    $mappings = app(MQRService::class);
    $run = $reports->startOrResume(null);

    $firstBatch = $mappings->mapNextBatch($run->id, 1, $reports);
    $run->refresh();

    expect($firstBatch['status'])->toBeTrue()
        ->and($run->status)->toBe(MqrMappingRun::STATUS_RUNNING)
        ->and($run->processed_count)->toBe(1)
        ->and($reports->completedReports())->toBeEmpty();

    Storage::disk('local')->assertMissing(
        $run->report_path.'/laporan_lengkap.csv'
    );

    $lastBatch = $mappings->mapNextBatch($run->id, 1, $reports);
    $run->refresh();

    expect($lastBatch['status'])->toBeTrue()
        ->and($run->status)->toBe(MqrMappingRun::STATUS_COMPLETED)
        ->and($run->processed_count)->toBe(2)
        ->and($run->success_count)->toBe(2)
        ->and($run->failure_count)->toBe(0)
        ->and($reports->completedReports())->toHaveCount(1)
        ->and(DB::connection('mqr_mapping')
            ->table('padanan_institusi_pengkhususan')
            ->value('kod_mqa'))->toBe('MQA/FA0001')
        ->and(DB::connection('mqr_mapping')
            ->table('padanan_institusi_pengkhususan')
            ->value('id_peringkat_kelulusan'))->toBe(3)
        ->and(DB::connection('mqr_mapping')
            ->table('padanan_institusi_pengkhususan')
            ->value('peringkat_kelulusan'))->toBeNull()
        ->and(DB::connection('mqr_mapping')->table('ref_institusi')
            ->where('DISKRIPSI', '=INSTITUSI_TIDAK_WUJUD')
            ->value('JENIS_INSTITUSI'))->toBe('2')
        ->and(DB::table('padanan_institusi_pengkhususan')->count())->toBe(0);

    $content = Storage::disk('local')->get(
        $run->report_path.'/laporan_lengkap.csv'
    );

    expect($content)
        ->toStartWith("\xEF\xBB\xBF")
        ->toContain('Status Padanan')
        ->toContain('Sebab Kegagalan')
        ->toContain('MQA/FA0001')
        ->toContain('BERJAYA')
        ->toContain('MQA/FA0002')
        ->not->toContain('GAGAL')
        ->toContain("'=INSTITUSI_TIDAK_WUJUD");
});

it('downloads a stored completed report without evaluating source data again', function () {
    Date::setTestNow('2026-07-28 10:11:12');

    $reports = app(MQRReportService::class);
    $run = $reports->startOrResume(null);
    app(MQRService::class)->mapNextBatch($run->id, 250, $reports);
    $run->refresh();

    DB::table('mqr_data')->delete();
    DB::table('ref_institusi')->delete();
    DB::table('ref_pengkhususan')->delete();

    $this->actingAs(User::factory()->create())
        ->get(route('mqr.reports.download', [
            'run' => $run,
            'type' => 'all',
        ]))
        ->assertOk()
        ->assertDownload('laporan_padanan_mqr_20260728_101112.csv');
});

it('keeps an older completed report downloadable while a new run is active', function () {
    Date::setTestNow('2026-07-28 10:00:00');

    $reports = app(MQRReportService::class);
    $mappings = app(MQRService::class);
    $oldRun = $reports->startOrResume(null);
    $mappings->mapNextBatch($oldRun->id, 250, $reports);
    $oldRun->refresh();

    Date::setTestNow('2026-07-28 11:00:00');
    DB::table('mqr_data')->insert([
        'kod_rujukan_akreditasi_penuh' => 'MQA/FA0003',
        'id_institusi' => 'MQR003',
        'nama_institusi' => 'Institusi Baharu',
        'nama_kelayakan_bm' => 'Kelayakan Baharu',
    ]);

    $newRun = $reports->startOrResume(null);
    $mappings->mapNextBatch($newRun->id, 1, $reports);
    $newRun->refresh();
    $history = $reports->completedReports();

    expect($oldRun->status)->toBe(MqrMappingRun::STATUS_COMPLETED)
        ->and($newRun->status)->toBe(MqrMappingRun::STATUS_RUNNING)
        ->and($history)->toHaveCount(1)
        ->and($history[0]['id'])->toBe($oldRun->id);

    $this->actingAs(User::factory()->create())
        ->get(route('mqr.reports.download', [
            'run' => $oldRun,
            'type' => 'failure',
        ]))
        ->assertOk()
        ->assertDownload('senarai_mqr_gagal_dipadan_20260728_100000.csv');
});

it('does not start an API sync while a mapping run is active', function () {
    $reports = app(MQRReportService::class);
    $reports->startOrResume(null);
    $syncCalled = false;

    $response = $reports->syncWhenIdle(
        function () use (&$syncCalled): array {
            $syncCalled = true;

            return ['status' => true, 'message' => 'Tidak sepatutnya dipanggil'];
        }
    );

    expect($response['status'])->toBeFalse()
        ->and($syncCalled)->toBeFalse()
        ->and($response['message'])->toContain('proses padanan masih berjalan');
});

it('uses a legacy row once and keeps each different MQA code', function () {
    DB::connection('mqr_mapping')
        ->table('padanan_institusi_pengkhususan')
        ->insert([
            [
                'kod_institusi' => '00038',
                'kod_pengkhususan' => 'P001',
                'kategori' => 1,
                'kod_mqa' => null,
                'id_peringkat_kelulusan' => 1,
                'peringkat_kelulusan' => 9,
                'status' => 0,
                'is_deleted' => 0,
                'tarikh_cipta' => now(),
            ],
            [
                'kod_institusi' => '00038',
                'kod_pengkhususan' => 'P001',
                'kategori' => 1,
                'kod_mqa' => 'KOD/LAMA',
                'id_peringkat_kelulusan' => 2,
                'peringkat_kelulusan' => 8,
                'status' => 0,
                'is_deleted' => 0,
                'tarikh_cipta' => now(),
            ],
        ]);
    DB::table('mqr_data')
        ->where('kod_rujukan_akreditasi_penuh', 'MQA/FA0002')
        ->update([
            'nama_institusi' => 'Universiti Sultan Zainal Abidin (UniSZA)',
            'nama_kelayakan_bm' => 'Sarjana Muda Teknologi Maklumat',
            'kod_nec' => '0112',
            'peringkat_kelayakan' => '7',
        ]);

    $reports = app(MQRReportService::class);
    $run = $reports->startOrResume(null);
    app(MQRService::class)->mapNextBatch($run->id, 250, $reports);

    $target = DB::connection('mqr_mapping')
        ->table('padanan_institusi_pengkhususan');

    expect($target->count())->toBe(3)
        ->and((clone $target)->where('kod_mqa', 'MQA/FA0001')->value('id_peringkat_kelulusan'))->toBe(3)
        ->and((clone $target)->where('kod_mqa', 'MQA/FA0002')->value('id_peringkat_kelulusan'))->toBe(3)
        ->and((clone $target)->where('kod_mqa', 'KOD/LAMA')->value('id_peringkat_kelulusan'))->toBe(2)
        ->and((clone $target)->orderBy('kod')->pluck('peringkat_kelulusan')->all())
        ->toBe([9, 8, null])
        ->and(DB::table('padanan_institusi_pengkhususan')->count())->toBe(0);
});

it('stores separate mappings for different MQA codes with the same institution and specialization', function (int $batchSize) {
    DB::table('mqr_data')
        ->where('kod_rujukan_akreditasi_penuh', 'MQA/FA0002')
        ->update([
            'nama_institusi' => 'Universiti Sultan Zainal Abidin (UniSZA)',
            'nama_kelayakan_bm' => 'Sarjana Muda Teknologi Maklumat',
            'kod_nec' => '0112',
            'peringkat_kelayakan' => '7',
        ]);

    $reports = app(MQRReportService::class);
    $mappings = app(MQRService::class);
    $run = $reports->startOrResume(null);

    do {
        expect($mappings->mapNextBatch($run->id, $batchSize, $reports)['status'])->toBeTrue();
        $run->refresh();
    } while ($run->status !== MqrMappingRun::STATUS_COMPLETED);

    $target = DB::connection('mqr_mapping')->table('padanan_institusi_pengkhususan');

    expect($run->success_count)->toBe(2)
        ->and($target->orderBy('kod_mqa')->pluck('kod_mqa')->all())
        ->toBe(['MQA/FA0001', 'MQA/FA0002'])
        ->and(Storage::disk('local')->get($run->report_path.'/padanan_berjaya.csv'))
        ->toContain('MQA/FA0001', 'MQA/FA0002');

    $rerun = $reports->startOrResume(null);
    expect($mappings->mapNextBatch($rerun->id, 250, $reports)['status'])->toBeTrue()
        ->and(DB::connection('mqr_mapping')->table('padanan_institusi_pengkhususan')->count())->toBe(2);
})->with([1, 250]);

it('keeps a deleted mapping and inserts a new active mapping', function () {
    DB::connection('mqr_mapping')
        ->table('padanan_institusi_pengkhususan')
        ->insert([
            'kod_institusi' => '00038',
            'kod_pengkhususan' => 'P001',
            'kategori' => 1,
            'kod_mqa' => 'KOD/ARKIB',
            'id_peringkat_kelulusan' => 7,
            'peringkat_kelulusan' => 4,
            'status' => 0,
            'is_deleted' => 1,
            'tarikh_cipta' => now(),
        ]);
    DB::table('mqr_data')
        ->where('kod_rujukan_akreditasi_penuh', 'MQA/FA0002')
        ->delete();

    $reports = app(MQRReportService::class);
    $run = $reports->startOrResume(null);
    app(MQRService::class)->mapNextBatch($run->id, 250, $reports);

    $target = DB::connection('mqr_mapping')
        ->table('padanan_institusi_pengkhususan');

    expect($target->count())->toBe(2)
        ->and((clone $target)->where('is_deleted', 1)->value('kod_mqa'))
        ->toBe('KOD/ARKIB')
        ->and((clone $target)->where('is_deleted', 1)->value('id_peringkat_kelulusan'))
        ->toBe(7)
        ->and((clone $target)->where('is_deleted', 0)->value('kod_mqa'))
        ->toBe('MQA/FA0001')
        ->and((clone $target)->where('is_deleted', 0)->value('id_peringkat_kelulusan'))
        ->toBe(3)
        ->and((clone $target)->where('is_deleted', 0)->value('peringkat_kelulusan'))
        ->toBeNull();
});

it('keeps an existing qualification id when the MQR level has no mapping', function () {
    DB::connection('mqr_mapping')
        ->table('padanan_institusi_pengkhususan')
        ->insert([
            'kod_institusi' => '00038',
            'kod_pengkhususan' => 'P001',
            'id_peringkat_kelulusan' => 6,
            'peringkat_kelulusan' => 3,
            'kategori' => 1,
            'kod_mqa' => 'MQA/FA0001',
            'status' => 0,
            'is_deleted' => 0,
            'tarikh_cipta' => now(),
        ]);
    DB::table('mqr_data')
        ->where('kod_rujukan_akreditasi_penuh', 'MQA/FA0001')
        ->update(['peringkat_kelayakan' => '12']);
    DB::table('mqr_data')
        ->where('kod_rujukan_akreditasi_penuh', 'MQA/FA0002')
        ->delete();

    $reports = app(MQRReportService::class);
    $run = $reports->startOrResume(null);
    app(MQRService::class)->mapNextBatch($run->id, 250, $reports);

    $mapping = DB::connection('mqr_mapping')
        ->table('padanan_institusi_pengkhususan')
        ->first();

    expect($mapping->kod_mqa)->toBe('MQA/FA0001')
        ->and($mapping->id_peringkat_kelulusan)->toBe(6)
        ->and($mapping->peringkat_kelulusan)->toBe(3);
});

it('stores a null qualification id for a new mapping with an unmapped level', function () {
    DB::table('mqr_data')
        ->where('kod_rujukan_akreditasi_penuh', 'MQA/FA0001')
        ->update(['peringkat_kelayakan' => '12']);
    DB::table('mqr_data')
        ->where('kod_rujukan_akreditasi_penuh', 'MQA/FA0002')
        ->delete();

    $reports = app(MQRReportService::class);
    $run = $reports->startOrResume(null);
    app(MQRService::class)->mapNextBatch($run->id, 250, $reports);

    $mapping = DB::connection('mqr_mapping')
        ->table('padanan_institusi_pengkhususan')
        ->first();

    expect($mapping->id_peringkat_kelulusan)->toBeNull()
        ->and($mapping->peringkat_kelulusan)->toBeNull();
});

it('stores both MQA codes through the full mapping flow', function () {
    DB::table('mqr_data')
        ->where('kod_rujukan_akreditasi_penuh', 'MQA/FA0002')
        ->update([
            'nama_institusi' => 'Universiti Sultan Zainal Abidin (UniSZA)',
            'nama_kelayakan_bm' => 'Sarjana Muda Teknologi Maklumat',
            'kod_nec' => '0112',
            'peringkat_kelayakan' => '9',
        ]);

    $response = app(MQRService::class)->mapAndSyncPadanan();
    $mappings = DB::connection('mqr_mapping')->table('padanan_institusi_pengkhususan');

    expect($response['status'])->toBeTrue()
        ->and($response['data']['berjaya_dipadankan'])->toBe(2)
        ->and((clone $mappings)->orderBy('kod_mqa')->pluck('kod_mqa')->all())
        ->toBe(['MQA/FA0001', 'MQA/FA0002'])
        ->and((clone $mappings)->where('kod_mqa', 'MQA/FA0001')->value('id_peringkat_kelulusan'))->toBe(3)
        ->and((clone $mappings)->where('kod_mqa', 'MQA/FA0002')->value('id_peringkat_kelulusan'))->toBe(1)
        ->and((clone $mappings)->whereNull('peringkat_kelulusan')->count())->toBe(2);
});

it('does not advance the run when a required qualification reference is missing', function () {
    DB::connection('mqr_mapping')
        ->table('ref_peringkat_kelulusan')
        ->where('kod', 7)
        ->delete();

    $reports = app(MQRReportService::class);
    $run = $reports->startOrResume(null);
    $response = app(MQRService::class)->mapNextBatch(
        $run->id,
        250,
        $reports
    );
    $run->refresh();

    expect($response['status'])->toBeFalse()
        ->and($response['message'])->toContain('ref_peringkat_kelulusan')
        ->and($response['message'])->toContain('7')
        ->and($run->processed_count)->toBe(0)
        ->and($run->last_mqr_id)->toBe(0)
        ->and(DB::connection('mqr_mapping')
            ->table('padanan_institusi_pengkhususan')->count())->toBe(0);
});

it('does not advance the run when the target mapping connection fails', function () {
    config()->set(
        'services.mqr.mapping_connection',
        'connection_yang_tidak_wujud'
    );

    $reports = app(MQRReportService::class);
    $run = $reports->startOrResume(null);
    $response = app(MQRService::class)->mapNextBatch(
        $run->id,
        250,
        $reports
    );
    $run->refresh();

    expect($response['status'])->toBeFalse()
        ->and($run->processed_count)->toBe(0)
        ->and($run->last_mqr_id)->toBe(0);

    Storage::disk('local')->assertMissing(
        $run->report_path.'/laporan_lengkap.csv'
    );
});

it('resumes the same batch safely after a report write rolls back', function () {
    DB::table('mqr_data')
        ->where('kod_rujukan_akreditasi_penuh', 'MQA/FA0002')
        ->delete();

    $reports = app(MQRReportService::class);
    $run = $reports->startOrResume(null);
    $flakyReports = new class extends MQRReportService
    {
        public bool $failOnce = true;

        public function writeBatchParts(
            MqrMappingRun $run,
            array $mappingRows
        ): void {
            parent::writeBatchParts($run, $mappingRows);

            if ($this->failOnce) {
                $this->failOnce = false;

                throw new RuntimeException('Kegagalan storan sementara.');
            }
        }
    };

    $failedAttempt = app(MQRService::class)->mapNextBatch(
        $run->id,
        250,
        $flakyReports
    );
    $run->refresh();

    expect($failedAttempt['status'])->toBeFalse()
        ->and($run->status)->toBe(MqrMappingRun::STATUS_RUNNING)
        ->and($run->processed_count)->toBe(0)
        ->and(DB::connection('mqr_mapping')
            ->table('padanan_institusi_pengkhususan')
            ->count())->toBe(1);

    $retry = app(MQRService::class)->mapNextBatch(
        $run->id,
        250,
        $reports
    );
    $run->refresh();

    $content = Storage::disk('local')->get(
        $run->report_path.'/laporan_lengkap.csv'
    );

    expect($retry['status'])->toBeTrue()
        ->and($run->status)->toBe(MqrMappingRun::STATUS_COMPLETED)
        ->and(DB::connection('mqr_mapping')
            ->table('padanan_institusi_pengkhususan')
            ->count())->toBe(1)
        ->and(DB::connection('mqr_mapping')
            ->table('padanan_institusi_pengkhususan')
            ->value('kod_mqa'))->toBe('MQA/FA0001')
        ->and(substr_count($content, 'MQA/FA0001'))->toBe(1);
});

it('restores an unfinished run in Livewire after the page is reopened', function () {
    $reports = app(MQRReportService::class);
    $run = $reports->startOrResume(null);
    app(MQRService::class)->mapNextBatch($run->id, 1, $reports);

    Livewire::test(MQRForm::class)
        ->assertSet('runId', $run->id)
        ->assertSet('hasActiveRun', true)
        ->assertSet('processedCount', 1)
        ->assertSee('Sambung Automasi Padanan')
        ->call('startMapping')
        ->call('handleNextBatch')
        ->assertSet('hasActiveRun', false)
        ->assertSet('runStatus', MqrMappingRun::STATUS_COMPLETED)
        ->assertSet('processedCount', 2)
        ->assertCount('completedReports', 1);
});

it('connects the Livewire action to the resumable run and completed history', function () {
    Livewire::test(MQRForm::class)
        ->assertSee('Laporan Padanan yang Telah Siap')
        ->call('startMapping')
        ->assertSet('isMapping', true)
        ->call('handleNextBatch')
        ->assertSet('isMapping', false)
        ->assertSet('processedCount', 2)
        ->assertSet('berjayaCount', 2)
        ->assertSet('gagalCount', 0)
        ->assertSet('runStatus', MqrMappingRun::STATUS_COMPLETED)
        ->assertCount('completedReports', 1)
        ->assertSee('Laporan Lengkap')
        ->assertSee('Gagal + Sebab');
});

it('registers exact branch and specialization names and reports their groups and categories', function () {
    DB::table('mqr_data')->delete();
    DB::table('mqr_data')->insert([
        [
            'kod_rujukan_akreditasi_penuh' => 'MQA/EDU1',
            'nama_institusi' => 'Institut Pendidikan Guru (IPG) (Kampus Bahasa Melayu)',
            'nama_kelayakan_bm' => 'Diploma Pendidikan (Bahasa Melayu)',
            'kod_nec' => '0210',
            'peringkat_kelayakan' => '9',
        ],
        [
            'kod_rujukan_akreditasi_penuh' => 'MQA/EDU2',
            'nama_institusi' => 'Institut Pendidikan Guru (IPG) (Kampus Bahasa Melayu)',
            'nama_kelayakan_bm' => 'Diploma Pendidikan (Bahasa Melayu)',
            'kod_nec' => '0112',
            'peringkat_kelayakan' => '9',
        ],
    ]);

    $reports = app(MQRReportService::class);
    $run = $reports->startOrResume(null);
    app(MQRService::class)->mapNextBatch($run->id, 1, $reports);
    app(MQRService::class)->mapNextBatch($run->id, 1, $reports);
    $run->refresh();

    $target = DB::connection('mqr_mapping');
    expect($run->success_count)->toBe(2)
        ->and($target->table('ref_institusi')->where('KOD', '00039')->value('KUMPULAN_INSTITUSI'))->toBe('IPGM')
        ->and($target->table('ref_institusi')->where('KOD', '00039')->value('JENIS_INSTITUSI'))->toBe('1')
        ->and($target->table('ref_institusi')->where('KOD', '00039')->value('TARIKH_CIPTA'))->not->toBeNull()
        ->and($target->table('ref_institusi')->where('KOD', '00039')->value('TARIKH_CIPTA'))
        ->toBe($target->table('ref_institusi')->where('KOD', '00039')->value('TARIKH_UBAHSUAI'))
        ->and($target->table('ref_pengkhususan')->where('DISKRIPSI', 'Diploma Pendidikan (Bahasa Melayu)')->value('KATEGORI'))->toBe(1)
        ->and($target->table('ref_pengkhususan')->where('DISKRIPSI', 'Diploma Pendidikan (Bahasa Melayu)')->value('TARIKH_CIPTA'))
        ->toBe($target->table('ref_pengkhususan')->where('DISKRIPSI', 'Diploma Pendidikan (Bahasa Melayu)')->value('TARIKH_UBAHSUAI'))
        ->and($target->table('padanan_institusi_pengkhususan')->where('is_deleted', 0)->count())->toBe(2)
        ->and($reports->state($run)['rujukan_baharu'])->toBe(2)
        ->and(Storage::disk('local')->get($run->report_path.'/rujukan_baharu.csv'))->toContain('IPGM', 'Diploma Pendidikan (Bahasa Melayu)');
});

it('fills missing types for previously registered institutions without changing existing types', function () {
    $target = DB::connection('mqr_mapping');
    $target->table('ref_institusi')->insert([
        ['KOD' => '00039', 'DISKRIPSI' => 'Institut Pendidikan Guru Kampus Bahasa Melayu', 'KUMPULAN_INSTITUSI' => 'IPGM', 'JENIS_INSTITUSI' => null],
        ['KOD' => '00040', 'DISKRIPSI' => 'Kolej Baharu', 'KUMPULAN_INSTITUSI' => null, 'JENIS_INSTITUSI' => null],
        ['KOD' => '00041', 'DISKRIPSI' => 'Institusi Ditentukan Manual', 'KUMPULAN_INSTITUSI' => null, 'JENIS_INSTITUSI' => '1'],
    ]);
    foreach (['00039', '00040', '00041'] as $code) {
        $target->table('mqr_reference_registrations')->insert([
            'jenis' => 'institution', 'kod' => $code, 'nama' => 'Nama ujian',
        ]);
    }

    $migration = require database_path('migrations/2026_10_11_000002_backfill_mqr_institution_types.php');
    $migration->up();
    $migration->up();

    expect($target->table('ref_institusi')->where('KOD', '00039')->value('JENIS_INSTITUSI'))->toBe('1')
        ->and($target->table('ref_institusi')->where('KOD', '00040')->value('JENIS_INSTITUSI'))->toBe('2')
        ->and($target->table('ref_institusi')->where('KOD', '00041')->value('JENIS_INSTITUSI'))->toBe('1');
});

it('backfills reference timestamps from registration history without replacing existing dates', function () {
    $target = DB::connection('mqr_mapping');
    $target->table('ref_institusi')->insert([
        'KOD' => '00039', 'DISKRIPSI' => 'Institusi Lama',
        'TARIKH_CIPTA' => '2020-01-01 09:00:00', 'TARIKH_UBAHSUAI' => null,
    ]);
    $target->table('ref_pengkhususan')->insert([
        'kod' => 'P002', 'DISKRIPSI' => 'Pengkhususan Lama', 'KATEGORI' => 2,
        'TARIKH_CIPTA' => null, 'TARIKH_UBAHSUAI' => null,
    ]);
    foreach ([['institution', '00039'], ['specialization', 'P002']] as [$kind, $code]) {
        $target->table('mqr_reference_registrations')->insert([
            'jenis' => $kind, 'kod' => $code, 'nama' => 'Nama ujian',
            'created_at' => '2026-10-11 14:00:24',
        ]);
    }

    $migration = require database_path('migrations/2026_10_11_000003_backfill_mqr_reference_timestamps.php');
    $migration->up();
    $migration->up();

    expect($target->table('ref_institusi')->where('KOD', '00039')->value('TARIKH_CIPTA'))->toBe('2020-01-01 09:00:00')
        ->and($target->table('ref_institusi')->where('KOD', '00039')->value('TARIKH_UBAHSUAI'))->toBe('2026-10-11 14:00:24')
        ->and($target->table('ref_pengkhususan')->where('kod', 'P002')->value('TARIKH_CIPTA'))->toBe('2026-10-11 14:00:24')
        ->and($target->table('ref_pengkhususan')->where('kod', 'P002')->value('TARIKH_UBAHSUAI'))->toBe('2026-10-11 14:00:24');
});

it('exports one portable SQL file without inserting a generated column', function () {
    DB::connection('mqr_mapping')->table('padanan_institusi_pengkhususan')->insert([
        'kod_institusi' => '00038', 'kod_pengkhususan' => 'P001',
        'kod_mqa' => 'MQA/EXPORT', 'tarikh_cipta' => '2026-10-11 14:00:24',
    ]);
    $path = sys_get_temp_dir().'/mqr-export-'.uniqid().'.sql';

    try {
        $result = Artisan::call('mqr:export-padanan-mariadb', ['--output' => $path]);
        expect($result)->toBe(0, Artisan::output());
        $sql = file_get_contents($path);

        expect($sql)->toContain('DROP TABLE IF EXISTS `padanan_institusi_pengkhususan`')
            ->toContain('mqr_kod_mqa_aktif` varchar(100) GENERATED ALWAYS AS')
            ->not->toContain('VIRTUAL NULL')
            ->toContain("'MQA/EXPORT'")
            ->toContain('-- Jumlah rekod: 1');
        preg_match('/INSERT INTO `padanan_institusi_pengkhususan` \(([^)]*)\)/', $sql, $match);
        expect($match[1] ?? '')->not->toContain('mqr_kod_mqa_aktif');
    } finally {
        @unlink($path);
    }
});

it('keeps the lowest mapping id for a duplicate MQA code and reports the removed row', function () {
    DB::table('mqr_data')->where('kod_rujukan_akreditasi_penuh', 'MQA/FA0002')->delete();
    $target = DB::connection('mqr_mapping')->table('padanan_institusi_pengkhususan');
    $target->insert([
        ['kod' => 10, 'kod_institusi' => '00038', 'kod_pengkhususan' => 'P001', 'kategori' => 1, 'kod_mqa' => 'MQA/FA0001', 'status' => 0, 'is_deleted' => 0, 'tarikh_cipta' => now()],
        ['kod' => 20, 'kod_institusi' => '00038', 'kod_pengkhususan' => 'P999', 'kategori' => 2, 'kod_mqa' => ' mqa/fa0001 ', 'status' => 0, 'is_deleted' => 0, 'tarikh_cipta' => now()],
    ]);

    $reports = app(MQRReportService::class);
    $run = $reports->startOrResume(null);
    app(MQRService::class)->mapNextBatch($run->id, 250, $reports);
    $run->refresh();

    expect($run->success_count)->toBe(1)
        ->and((clone $target)->where('kod', 10)->value('is_deleted'))->toBe(0)
        ->and((clone $target)->where('kod', 10)->value('kod_institusi'))->toBe('00039')
        ->and((clone $target)->where('kod', 20)->value('is_deleted'))->toBe(1)
        ->and($reports->state($run)['kod_mqa_pendua'])->toBe(1)
        ->and($reports->state($run)['rekod_dinyahaktifkan'])->toBe(1)
        ->and(Storage::disk('local')->get($run->report_path.'/kod_mqa_pendua.csv'))->toContain('MQA/FA0001', 'P999', 'BERJAYA');

    $rerun = $reports->startOrResume(null);
    app(MQRService::class)->mapNextBatch($rerun->id, 250, $reports);
    expect($reports->state($rerun->fresh())['kod_mqa_pendua'])->toBe(0)
        ->and((clone $target)->where('is_deleted', 0)->where('kod_mqa', 'MQA/FA0001')->count())->toBe(1);
});

it('reconciles existing duplicates before enforcing unique active MQA codes', function () {
    $target = DB::connection('mqr_mapping')->table('padanan_institusi_pengkhususan');
    $target->insert([
        ['kod' => 10, 'kod_institusi' => '00038', 'kod_pengkhususan' => 'P001', 'kod_mqa' => 'MQA/ONE', 'status' => 0, 'is_deleted' => 0, 'tarikh_cipta' => now()],
        ['kod' => 20, 'kod_institusi' => '00038', 'kod_pengkhususan' => 'P001', 'kod_mqa' => ' mqa/one ', 'status' => 0, 'is_deleted' => 0, 'tarikh_cipta' => now()],
    ]);

    $migration = require base_path('database/migrations/2026_10_11_000001_mqr_reference_groups_and_audits.php');
    $migration->up();

    expect((clone $target)->where('kod', 10)->value('is_deleted'))->toBe(0)
        ->and((clone $target)->where('kod', 20)->value('is_deleted'))->toBe(1)
        ->and(DB::connection('mqr_mapping')->table('mqr_mapping_duplicate_audits')->count())->toBe(1);

    expect(fn () => $target->insert([
        'kod_institusi' => '00038', 'kod_pengkhususan' => 'P001',
        'kod_mqa' => 'mqa/one', 'status' => 0, 'is_deleted' => 0, 'tarikh_cipta' => now(),
    ]))->toThrow(\Illuminate\Database\QueryException::class);

    $target->insert([
        'kod_institusi' => '00038', 'kod_pengkhususan' => 'P001',
        'kod_mqa' => null, 'status' => 0, 'is_deleted' => 0, 'tarikh_cipta' => now(),
    ]);

    $reports = app(MQRReportService::class);
    $run = $reports->startOrResume(null);
    app(MQRService::class)->mapNextBatch($run->id, 250, $reports);
    $run->refresh();
    expect($reports->state($run)['kod_mqa_pendua'])->toBe(1)
        ->and(Storage::disk('local')->get($run->report_path.'/kod_mqa_pendua.csv'))->toContain('MQA/ONE', 'PADANAN_GAGAL');
});
