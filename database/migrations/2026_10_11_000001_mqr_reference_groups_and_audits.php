<?php

use App\Services\MQRInstitutionGroup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mqr_mapping');
        $namespace = trim((string) config('services.mqr.mapping_schema'));
        $qualified = fn (string $name): string => $namespace === '' ? $name : $namespace.'.'.$name;
        if (! $this->targetHasTable('ref_institusi') || ! $this->targetHasTable('padanan_institusi_pengkhususan')) {
            if (app()->environment('testing')) {
                return;
            }

            throw new \RuntimeException('Jadual rujukan dan padanan MQR belum tersedia pada pangkalan data sasaran.');
        }

        if (! $this->targetHasColumn('ref_institusi', 'KUMPULAN_INSTITUSI')) {
            $schema->table($qualified('ref_institusi'), function (Blueprint $table): void {
                $table->string('KUMPULAN_INSTITUSI', 100)->nullable()->index();
            });
        }

        if (! $this->targetHasTable('mqr_reference_registrations')) {
            $schema->create($qualified('mqr_reference_registrations'), function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('run_id', 26)->nullable()->index();
                $table->string('jenis', 20);
                $table->string('kod', 20);
                $table->text('nama');
                $table->string('kumpulan_institusi', 100)->nullable();
                $table->integer('kategori')->nullable();
                $table->timestamp('created_at');
                $table->unique(['jenis', 'kod']);
            });
        }

        if (! $this->targetHasTable('mqr_mapping_duplicate_audits')) {
            $schema->create($qualified('mqr_mapping_duplicate_audits'), function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('run_id', 26)->nullable()->index();
                $table->string('kod_mqa', 100);
                $table->unsignedBigInteger('kod_dikekalkan');
                $table->unsignedBigInteger('kod_dinyahaktifkan')->unique();
                $table->string('kod_institusi_lama', 10)->nullable();
                $table->string('kod_pengkhususan_lama', 11)->nullable();
                $table->string('kod_institusi_akhir', 10)->nullable();
                $table->string('kod_pengkhususan_akhir', 11)->nullable();
                $table->string('hasil', 40);
                $table->timestamp('created_at');
            });
        }

        $connection = DB::connection('mqr_mapping');
        $connection->table($qualified('ref_institusi'))->whereNull('KUMPULAN_INSTITUSI')
            ->orderBy('KOD')->get()->each(function ($row) use ($connection, $qualified): void {
                $group = MQRInstitutionGroup::resolve($row->DISKRIPSI);
                if ($group !== null) {
                    $connection->table($qualified('ref_institusi'))->where('KOD', $row->KOD)
                        ->whereNull('KUMPULAN_INSTITUSI')->update(['KUMPULAN_INSTITUSI' => $group]);
                }
            });

        $duplicates = $connection->table($qualified('padanan_institusi_pengkhususan'))
            ->selectRaw('UPPER(TRIM(kod_mqa)) AS normalized_code')
            ->where('is_deleted', 0)->whereNotNull('kod_mqa')
            ->whereRaw("TRIM(kod_mqa) <> ''")
            ->groupByRaw('UPPER(TRIM(kod_mqa))')
            ->havingRaw('COUNT(*) > 1')->pluck('normalized_code');

        foreach ($duplicates as $code) {
            $connection->transaction(function () use ($connection, $code, $qualified): void {
                $rows = $connection->table($qualified('padanan_institusi_pengkhususan'))
                    ->where('is_deleted', 0)->whereRaw('UPPER(TRIM(kod_mqa)) = ?', [$code])
                    ->orderBy('kod')->lockForUpdate()->get();
                $kept = $rows->first();
                if ($kept === null) {
                    return;
                }
                foreach ($rows->skip(1) as $row) {
                    $connection->table($qualified('mqr_mapping_duplicate_audits'))->insert([
                        'kod_mqa' => $code,
                        'kod_dikekalkan' => $kept->kod,
                        'kod_dinyahaktifkan' => $row->kod,
                        'kod_institusi_lama' => $row->kod_institusi,
                        'kod_pengkhususan_lama' => $row->kod_pengkhususan,
                        'kod_institusi_akhir' => $kept->kod_institusi,
                        'kod_pengkhususan_akhir' => $kept->kod_pengkhususan,
                        'hasil' => 'MENUNGGU_PADANAN',
                        'created_at' => now(),
                    ]);
                    $connection->table($qualified('padanan_institusi_pengkhususan'))->where('kod', $row->kod)
                        ->update(['is_deleted' => 1, 'tarikh_ubahsuai' => now()]);
                }
            });
        }

        if (! $this->targetHasColumn('padanan_institusi_pengkhususan', 'mqr_kod_mqa_aktif')) {
            $schema->table($qualified('padanan_institusi_pengkhususan'), function (Blueprint $table): void {
                $table->string('mqr_kod_mqa_aktif', 100)->nullable()
                    ->virtualAs("CASE WHEN is_deleted = 0 AND kod_mqa IS NOT NULL AND TRIM(kod_mqa) <> '' THEN UPPER(TRIM(kod_mqa)) ELSE NULL END")
                    ->unique('mqr_kod_mqa_aktif_unique');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('mqr_mapping');
        $namespace = trim((string) config('services.mqr.mapping_schema'));
        $qualified = fn (string $name): string => $namespace === '' ? $name : $namespace.'.'.$name;
        if ($this->targetHasTable('mqr_mapping_duplicate_audits')
            && DB::connection('mqr_mapping')->table($qualified('mqr_mapping_duplicate_audits'))->exists()) {
            throw new \RuntimeException('Audit kod MQA pendua perlu disimpan; rollback migrasi ini tidak dibenarkan.');
        }
        if ($this->targetHasTable('padanan_institusi_pengkhususan') && $this->targetHasColumn('padanan_institusi_pengkhususan', 'mqr_kod_mqa_aktif')) {
            $schema->table($qualified('padanan_institusi_pengkhususan'), fn (Blueprint $table) => $table->dropColumn('mqr_kod_mqa_aktif'));
        }
        $schema->dropIfExists($qualified('mqr_mapping_duplicate_audits'));
        $schema->dropIfExists($qualified('mqr_reference_registrations'));
        if ($this->targetHasTable('ref_institusi') && $this->targetHasColumn('ref_institusi', 'KUMPULAN_INSTITUSI')) {
            $schema->table($qualified('ref_institusi'), fn (Blueprint $table) => $table->dropColumn('KUMPULAN_INSTITUSI'));
        }
    }

    private function targetHasTable(string $name): bool
    {
        $connection = DB::connection('mqr_mapping');
        if ($connection->getDriverName() !== 'mysql') {
            return Schema::connection('mqr_mapping')->hasTable($name);
        }

        return $connection->table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $this->targetSchema())
            ->where('TABLE_NAME', $name)->exists();
    }

    private function targetHasColumn(string $table, string $column): bool
    {
        $connection = DB::connection('mqr_mapping');
        if ($connection->getDriverName() !== 'mysql') {
            return Schema::connection('mqr_mapping')->hasColumn($table, $column);
        }

        return $connection->table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $this->targetSchema())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)->exists();
    }

    private function targetSchema(): string
    {
        return trim((string) config('services.mqr.mapping_schema'))
            ?: DB::connection('mqr_mapping')->getDatabaseName();
    }
};
