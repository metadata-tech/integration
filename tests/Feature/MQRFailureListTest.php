<?php

use App\Livewire\MQRFailureList;
use App\Models\MqrMappingFailure;
use App\Models\User;
use App\Services\MQRReportService;
use App\Services\MQRService;
use Illuminate\Database\Schema\Blueprint;
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

    $institution = function (Blueprint $table): void {
        $table->string('KOD')->primary();
        $table->string('DISKRIPSI');
        $table->string('KUMPULAN_INSTITUSI')->nullable();
        $table->string('JENIS_INSTITUSI')->nullable();
        $table->dateTime('TARIKH_CIPTA')->nullable();
        $table->dateTime('TARIKH_UBAHSUAI')->nullable();
        $table->boolean('is_deleted')->default(false);
    };
    $specialization = function (Blueprint $table): void {
        $table->string('kod')->primary();
        $table->string('DISKRIPSI');
        $table->integer('KATEGORI')->nullable();
        $table->dateTime('TARIKH_CIPTA')->nullable();
        $table->dateTime('TARIKH_UBAHSUAI')->nullable();
        $table->boolean('is_deleted')->default(false);
    };

    Schema::connection('mqr_mapping')->create('ref_institusi', $institution);
    Schema::connection('mqr_mapping')->create('ref_pengkhususan', $specialization);
    Schema::connection('mqr_mapping')->create(
        'ref_peringkat_kelulusan',
        function (Blueprint $table): void {
            $table->integer('kod')->primary();
            $table->string('diskripsi')->nullable();
            $table->boolean('is_deleted')->default(false);
        }
    );
    Schema::connection('mqr_mapping')->create(
        'padanan_institusi_pengkhususan',
        function (Blueprint $table): void {
            $table->bigIncrements('kod');
            $table->string('kod_institusi');
            $table->string('kod_pengkhususan');
            $table->integer('id_peringkat_kelulusan')->nullable();
            $table->integer('kategori')->nullable();
            $table->string('kod_mqa')->nullable();
            $table->integer('status')->default(0);
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('tarikh_cipta')->nullable();
            $table->timestamp('tarikh_ubahsuai')->nullable();
            $table->integer('id_pencipta')->nullable();
            $table->integer('id_pengubahsuai')->nullable();
        }
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

    DB::connection('mqr_mapping')->table('ref_institusi')->insert([
        'KOD' => '00038',
        'DISKRIPSI' => 'UNIVERSITI SULTAN ZAINAL ABIDIN (UNISZA)',
        'is_deleted' => false,
    ]);
    DB::connection('mqr_mapping')->table('ref_pengkhususan')->insert([
        'kod' => 'P001',
        'DISKRIPSI' => 'Sarjana Muda Teknologi Maklumat',
        'KATEGORI' => 1,
        'is_deleted' => false,
    ]);
    DB::connection('mqr_mapping')->table('ref_peringkat_kelulusan')->insert(
        array_map(
            fn (int $code): array => [
                'kod' => $code,
                'diskripsi' => "Peringkat {$code}",
                'is_deleted' => false,
            ],
            [1, 2, 3, 4, 6, 7]
        )
    );
});

afterEach(function () {
    DB::purge('mqr_mapping');
});

it('keeps records with missing source fields in the failure list', function () {
    DB::table('mqr_data')->insert([
        'kod_rujukan_akreditasi_penuh' => 'MQA/INCOMPLETE',
        'nama_institusi' => null,
        'nama_kelayakan_bm' => null,
    ]);

    $reports = app(MQRReportService::class);
    $run = $reports->startOrResume(null);
    app(MQRService::class)->mapNextBatch($run->id, 50, $reports);

    expect(MqrMappingFailure::query()->count())->toBe(1)
        ->and(MqrMappingFailure::query()->first()->reason)->toContain('Nama institusi tidak dibekalkan');

    Livewire::test(MQRFailureList::class)->assertSee('MQA/INCOMPLETE');
});

it('pauses automatic registration when a required reference column cannot be populated', function () {
    Schema::connection('mqr_mapping')->drop('ref_institusi');
    Schema::connection('mqr_mapping')->create('ref_institusi', function (Blueprint $table): void {
        $table->string('KOD')->primary();
        $table->string('DISKRIPSI');
        $table->string('KUMPULAN_INSTITUSI')->nullable();
        $table->string('JENIS_INSTITUSI')->nullable();
        $table->string('NEGARA');
        $table->dateTime('TARIKH_CIPTA')->nullable();
        $table->dateTime('TARIKH_UBAHSUAI')->nullable();
        $table->boolean('is_deleted')->default(false);
    });
    DB::connection('mqr_mapping')->table('ref_institusi')->insert([
        'KOD' => '00038', 'DISKRIPSI' => 'UNIVERSITI SULTAN ZAINAL ABIDIN (UNISZA)',
        'JENIS_INSTITUSI' => '1', 'NEGARA' => '130', 'is_deleted' => 0,
    ]);
    DB::table('mqr_data')->insert([
        'kod_rujukan_akreditasi_penuh' => 'MQA/NEW',
        'nama_institusi' => 'Institusi Baharu',
        'nama_kelayakan_bm' => 'Diploma Baharu',
    ]);

    $reports = app(MQRReportService::class);
    $run = $reports->startOrResume(null);
    $result = app(MQRService::class)->mapNextBatch($run->id, 50, $reports);

    expect($result['status'])->toBeFalse()
        ->and($result['message'])->toContain('NEGARA')
        ->and($run->fresh()->processed_count)->toBe(0);
});

it('shows the failure page from the MQR menu', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('integrasi.mqr.gagal'))
        ->assertOk()
        ->assertSee('Senarai Gagal MQR');
});
