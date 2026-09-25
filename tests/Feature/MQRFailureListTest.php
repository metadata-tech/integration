<?php

use App\Livewire\MQRFailureList;
use App\Models\MqrMappingFailure;
use App\Models\User;
use App\Services\MQRReportService;
use App\Services\MQRService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(function () {
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
        $table->boolean('is_deleted')->default(false);
    };
    $specialization = function (Blueprint $table): void {
        $table->string('kod')->primary();
        $table->string('DISKRIPSI');
        $table->integer('KATEGORI')->nullable();
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
        }
    );

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

it('lists failed mappings and filters them by reason', function () {
    DB::table('mqr_data')->insert([
        [
            'kod_rujukan_akreditasi_penuh' => 'MQA/INST',
            'nama_institusi' => 'Universiti Tiada Dalam Rujukan',
            'nama_kelayakan_bm' => 'Sarjana Muda Teknologi Maklumat',
        ],
        [
            'kod_rujukan_akreditasi_penuh' => 'MQA/SPEC',
            'nama_institusi' => 'Universiti Sultan Zainal Abidin (UniSZA)',
            'nama_kelayakan_bm' => 'Kelayakan Tiada Dalam Rujukan',
        ],
        [
            'kod_rujukan_akreditasi_penuh' => 'MQA/BOTH',
            'nama_institusi' => 'Institusi Lain Tiada',
            'nama_kelayakan_bm' => 'Kelayakan Lain Tiada',
        ],
    ]);

    $reports = app(MQRReportService::class);
    $run = $reports->startOrResume(null);
    app(MQRService::class)->mapNextBatch($run->id, 50, $reports);

    expect(MqrMappingFailure::query()->count())->toBe(3);

    Livewire::test(MQRFailureList::class)
        ->assertSee('Universiti Tiada Dalam Rujukan')
        ->assertSee('Kelayakan Tiada Dalam Rujukan')
        ->set('reason', 'institution')
        ->assertSee('Universiti Tiada Dalam Rujukan')
        ->assertDontSee('Kelayakan Tiada Dalam Rujukan')
        ->assertDontSee('Institusi Lain Tiada')
        ->set('reason', 'specialization')
        ->assertSee('Kelayakan Tiada Dalam Rujukan')
        ->assertDontSee('Universiti Tiada Dalam Rujukan')
        ->set('search', 'MQA/BOTH')
        ->set('reason', '')
        ->assertSee('Institusi Lain Tiada')
        ->assertDontSee('Universiti Tiada Dalam Rujukan');
});

it('registers one shared institution and rematches the selected records', function () {
    DB::table('mqr_data')->insert([
        [
            'kod_rujukan_akreditasi_penuh' => 'MQA/A',
            'nama_institusi' => 'Universiti Baharu Bersama',
            'nama_kelayakan_bm' => 'Sarjana Muda Teknologi Maklumat',
            'peringkat_kelayakan' => '7',
            'kod_nec' => '0210',
        ],
        [
            'kod_rujukan_akreditasi_penuh' => 'MQA/B',
            'nama_institusi' => 'Universiti Baharu Bersama',
            'nama_kelayakan_bm' => 'Sarjana Muda Teknologi Maklumat',
            'peringkat_kelayakan' => '7',
            'kod_nec' => '0210',
        ],
    ]);

    $reports = app(MQRReportService::class);
    $run = $reports->startOrResume(null);
    app(MQRService::class)->mapNextBatch($run->id, 50, $reports);
    $ids = MqrMappingFailure::query()->pluck('id')->all();

    Livewire::test(MQRFailureList::class)
        ->set('selected', $ids)
        ->call('registerInstitutions')
        ->assertSee('1 rujukan didaftarkan. 2 rekod dipadankan. 0 rekod masih gagal.');

    expect(DB::connection('mqr_mapping')->table('ref_institusi')->count())->toBe(2)
        ->and(DB::connection('mqr_mapping')->table('ref_institusi')->where('KOD', '00039')->value('DISKRIPSI'))
        ->toBe('Universiti Baharu Bersama')
        ->and(DB::connection('mqr_mapping')->table('padanan_institusi_pengkhususan')->count())->toBe(1)
        ->and(DB::connection('mqr_mapping')->table('padanan_institusi_pengkhususan')->value('kod_institusi'))
        ->toBe('00039')
        ->and(MqrMappingFailure::query()->count())->toBe(0);
});

it('does not register an ambiguous institution', function () {
    DB::connection('mqr_mapping')->table('ref_institusi')->insert([
        'KOD' => '00039',
        'DISKRIPSI' => 'KOLEJ SAMA',
        'is_deleted' => false,
    ]);
    DB::connection('mqr_mapping')->table('ref_institusi')->where('KOD', '00038')->update([
        'DISKRIPSI' => 'KOLEJ SAMA',
    ]);
    DB::table('mqr_data')->insert([
        'kod_rujukan_akreditasi_penuh' => 'MQA/AMB',
        'nama_institusi' => 'Kolej Sama',
        'nama_kelayakan_bm' => 'Sarjana Muda Teknologi Maklumat',
    ]);

    $reports = app(MQRReportService::class);
    $run = $reports->startOrResume(null);
    app(MQRService::class)->mapNextBatch($run->id, 50, $reports);
    $failure = MqrMappingFailure::query()->first();

    expect($failure->ambiguous_institution)->toBeTrue();

    Livewire::test(MQRFailureList::class)
        ->set('selected', [$failure->id])
        ->call('registerInstitutions')
        ->assertSee('Tiada rekod terpilih yang boleh didaftarkan');

    expect(DB::connection('mqr_mapping')->table('ref_institusi')->count())->toBe(2)
        ->and(DB::connection('mqr_mapping')->table('padanan_institusi_pengkhususan')->count())->toBe(0);
});

it('rejects registration when a reference column is required', function () {
    DB::table('mqr_data')->insert([
        'kod_rujukan_akreditasi_penuh' => 'MQA/JENIS',
        'nama_institusi' => 'Universiti Tanpa Jenis',
        'nama_kelayakan_bm' => 'Sarjana Muda Teknologi Maklumat',
    ]);

    $reports = app(MQRReportService::class);
    $run = $reports->startOrResume(null);
    app(MQRService::class)->mapNextBatch($run->id, 50, $reports);

    Schema::connection('mqr_mapping')->drop('ref_institusi');
    Schema::connection('mqr_mapping')->create('ref_institusi', function (Blueprint $table): void {
        $table->string('KOD')->primary();
        $table->string('DISKRIPSI');
        $table->string('JENIS_INSTITUSI');
        $table->boolean('is_deleted')->default(false);
    });
    DB::connection('mqr_mapping')->table('ref_institusi')->insert([
        'KOD' => '00038',
        'DISKRIPSI' => 'UNIVERSITI SULTAN ZAINAL ABIDIN (UNISZA)',
        'JENIS_INSTITUSI' => '1',
        'is_deleted' => false,
    ]);

    $ids = MqrMappingFailure::query()->pluck('id')->all();

    Livewire::test(MQRFailureList::class)
        ->set('selected', $ids)
        ->call('registerInstitutions')
        ->assertSee('JENIS_INSTITUSI');

    expect(DB::connection('mqr_mapping')->table('ref_institusi')->count())->toBe(1);
});

it('shows the failure page from the MQR menu', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('integrasi.mqr.gagal'))
        ->assertOk()
        ->assertSee('Senarai Gagal MQR');
});
