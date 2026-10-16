<?php

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

        if (! $hasTable('ref_institusi') || ! $hasTable('ref_pengkhususan') || ! $hasTable('mqr_reference_registrations')) {
            if (app()->environment('testing')) {
                return;
            }

            throw new \RuntimeException('Jadual rujukan MQR belum tersedia pada pangkalan data sasaran.');
        }

        $registrations = $qualified('mqr_reference_registrations');
        foreach (['institution' => ['ref_institusi', 'KOD'], 'specialization' => ['ref_pengkhususan', 'kod']] as $kind => [$table, $codeColumn]) {
            $reference = $qualified($table);
            $connection->transaction(function () use ($connection, $registrations, $reference, $kind, $codeColumn): void {
                $rows = $connection->table($registrations.' as r')
                    ->join($reference.' as x', 'x.'.$codeColumn, '=', 'r.kod')
                    ->where('r.jenis', $kind)
                    ->where(fn ($query) => $query->whereNull('x.TARIKH_CIPTA')->orWhereNull('x.TARIKH_UBAHSUAI'))
                    ->get([
                        'x.'.$codeColumn.' as reference_code',
                        'x.TARIKH_CIPTA as created_on',
                        'x.TARIKH_UBAHSUAI as updated_on',
                        'r.created_at as registered_on',
                    ]);

                foreach ($rows as $row) {
                    $values = [];
                    if ($row->created_on === null) {
                        $values['TARIKH_CIPTA'] = $row->registered_on;
                    }
                    if ($row->updated_on === null) {
                        $values['TARIKH_UBAHSUAI'] = $row->registered_on;
                    }
                    $connection->table($reference)->where($codeColumn, $row->reference_code)->update($values);
                }
            });
        }
    }

    public function down(): void
    {
        // Tarikh rujukan yang telah diisi mungkin telah digunakan oleh sistem lain.
    }
};
