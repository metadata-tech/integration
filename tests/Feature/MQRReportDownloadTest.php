<?php

use App\Livewire\MQRForm;
use App\Models\MqrMappingRun;
use App\Models\User;
use App\Services\MQRReportService;
use App\Services\MQRService;
use Illuminate\Database\Schema\Blueprint;
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

    Schema::create('mqr_data', function (Blueprint $table): void {
        $table->id();
        $table->string('kod_rujukan_akreditasi_penuh')->nullable();
        $table->string('id_institusi')->nullable();
        $table->string('nama_institusi')->nullable();
        $table->text('nama_kelayakan_bm')->nullable();
    });

    Schema::create('ref_institusi', function (Blueprint $table): void {
        $table->string('KOD')->primary();
        $table->string('DISKRIPSI');
        $table->boolean('is_deleted')->default(false);
    });

    Schema::create('ref_pengkhususan', function (Blueprint $table): void {
        $table->string('kod')->primary();
        $table->string('DISKRIPSI');
        $table->integer('KATEGORI')->nullable();
        $table->boolean('is_deleted')->default(false);
    });

    $createMappingTable = function (Blueprint $table): void {
        $table->bigIncrements('kod');
        $table->string('kod_institusi');
        $table->string('kod_pengkhususan');
        $table->integer('kategori')->nullable();
        $table->string('kod_mqa')->nullable();
        $table->integer('status')->default(0);
        $table->boolean('is_deleted')->default(false);
        $table->timestamp('tarikh_cipta')->nullable();
        $table->timestamp('tarikh_ubahsuai')->nullable();
    };

    Schema::create(
        'padanan_institusi_pengkhususan',
        $createMappingTable
    );
    Schema::connection('mqr_mapping')->create(
        'padanan_institusi_pengkhususan',
        $createMappingTable
    );

    DB::table('ref_institusi')->insert([
        'KOD' => '00038',
        'DISKRIPSI' => 'UNIVERSITI SULTAN ZAINAL ABIDIN (UNISZA)',
        'is_deleted' => false,
    ]);
    DB::table('ref_pengkhususan')->insert([
        'kod' => 'P001',
        'DISKRIPSI' => 'Sarjana Muda Teknologi Maklumat',
        'KATEGORI' => 1,
        'is_deleted' => false,
    ]);
    DB::table('mqr_data')->insert([
        [
            'kod_rujukan_akreditasi_penuh' => 'MQA/FA0001',
            'id_institusi' => 'MQR001',
            'nama_institusi' => 'Universiti Sultan Zainal Abidin (UniSZA) (Kampus Gong Badak)',
            'nama_kelayakan_bm' => 'Sarjana Muda Teknologi Maklumat',
        ],
        [
            'kod_rujukan_akreditasi_penuh' => 'MQA/FA0002',
            'id_institusi' => 'MQR002',
            'nama_institusi' => '=INSTITUSI_TIDAK_WUJUD',
            'nama_kelayakan_bm' => 'Kelayakan Tidak Wujud',
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
        ->and($run->success_count)->toBe(1)
        ->and($run->failure_count)->toBe(1)
        ->and($reports->completedReports())->toHaveCount(1)
        ->and(DB::connection('mqr_mapping')
            ->table('padanan_institusi_pengkhususan')
            ->value('kod_mqa'))->toBe('MQA/FA0001')
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
        ->toContain('GAGAL')
        ->toContain('Tiada institusi yang sepadan dalam jadual ref_institusi.')
        ->toContain('Tiada pengkhususan yang sepadan dalam jadual ref_pengkhususan.')
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

it('updates every active duplicate in the configured mapping database', function () {
    DB::connection('mqr_mapping')
        ->table('padanan_institusi_pengkhususan')
        ->insert([
            [
                'kod_institusi' => '00038',
                'kod_pengkhususan' => 'P001',
                'kategori' => 1,
                'kod_mqa' => null,
                'status' => 0,
                'is_deleted' => 0,
                'tarikh_cipta' => now(),
            ],
            [
                'kod_institusi' => '00038',
                'kod_pengkhususan' => 'P001',
                'kategori' => 1,
                'kod_mqa' => 'KOD/LAMA',
                'status' => 0,
                'is_deleted' => 0,
                'tarikh_cipta' => now(),
            ],
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
        ->and($target->where('kod_mqa', 'MQA/FA0001')->count())->toBe(2)
        ->and(DB::table('padanan_institusi_pengkhususan')->count())->toBe(0);
});

it('keeps a deleted mapping and inserts a new active mapping', function () {
    DB::connection('mqr_mapping')
        ->table('padanan_institusi_pengkhususan')
        ->insert([
            'kod_institusi' => '00038',
            'kod_pengkhususan' => 'P001',
            'kategori' => 1,
            'kod_mqa' => 'KOD/ARKIB',
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
        ->and((clone $target)->where('is_deleted', 0)->value('kod_mqa'))
        ->toBe('MQA/FA0001');
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
        ->assertSet('berjayaCount', 1)
        ->assertSet('gagalCount', 1)
        ->assertSet('runStatus', MqrMappingRun::STATUS_COMPLETED)
        ->assertCount('completedReports', 1)
        ->assertSee('Laporan Lengkap')
        ->assertSee('Gagal + Sebab');
});
