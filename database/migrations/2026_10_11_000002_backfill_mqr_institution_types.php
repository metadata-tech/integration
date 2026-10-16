<?php

use App\Services\MQRInstitutionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection('mqr_mapping');
        $schema = trim((string) config('services.mqr.mapping_schema'));
        $qualified = fn (string $table): string => $schema === '' ? $table : $schema.'.'.$table;
        $database = $schema ?: $connection->getDatabaseName();
        $hasTable = fn (string $table): bool => $connection->getDriverName() === 'mysql'
            ? $connection->table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->where('TABLE_NAME', $table)->exists()
            : Schema::connection('mqr_mapping')->hasTable($table);

        if (! $hasTable('ref_institusi') || ! $hasTable('mqr_reference_registrations')) {
            if (app()->environment('testing')) {
                return;
            }

            throw new \RuntimeException('Jadual rujukan MQR belum tersedia pada pangkalan data sasaran.');
        }

        $referenceTable = $qualified('ref_institusi');
        $registrationsTable = $qualified('mqr_reference_registrations');
        $groupTypes = MQRInstitutionType::groupTypes(
            $connection->table($referenceTable)->whereNotNull('JENIS_INSTITUSI')
                ->get(['KUMPULAN_INSTITUSI', 'JENIS_INSTITUSI'])
        );

        $connection->transaction(function () use ($connection, $referenceTable, $registrationsTable, $groupTypes): void {
            $newInstitutions = $connection->table($registrationsTable.' as r')
                ->join($referenceTable.' as i', 'i.KOD', '=', 'r.kod')
                ->where('r.jenis', 'institution')->whereNull('i.JENIS_INSTITUSI')
                ->get(['i.KOD', 'i.DISKRIPSI', 'i.KUMPULAN_INSTITUSI']);

            foreach ($newInstitutions as $institution) {
                $type = MQRInstitutionType::resolve(
                    $institution->DISKRIPSI,
                    $institution->KUMPULAN_INSTITUSI,
                    $groupTypes
                );
                $connection->table($referenceTable)->where('KOD', $institution->KOD)
                    ->whereNull('JENIS_INSTITUSI')->update(['JENIS_INSTITUSI' => $type]);
            }
        });
    }

    public function down(): void
    {
        // Nilai yang sudah ditetapkan mungkin telah digunakan oleh sistem lain.
    }
};
