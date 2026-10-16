<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ExportMqrPadananMariaDb extends Command
{
    protected $signature = 'mqr:export-padanan-mariadb {--output= : Lokasi fail SQL keluaran}';

    protected $description = 'Eksport jadual padanan MQR sebagai satu fail SQL yang boleh diimport ke MariaDB';

    /** @var array<int, string> */
    private const COLUMNS = [
        'kod', 'kod_institusi', 'kod_pengkhususan', 'id_peringkat_kelulusan',
        'peringkat_kelulusan', 'kategori', 'status', 'kod_mqa',
        'tarikh_cipta', 'tarikh_ubahsuai', 'id_pencipta',
        'id_pengubahsuai', 'is_deleted',
    ];

    public function handle(): int
    {
        $requested = trim((string) $this->option('output'));
        $output = $requested === ''
            ? storage_path('app/mqr_exports/padanan_mariadb_'.now()->format('Ymd_His').'.sql')
            : (str_starts_with($requested, '/') ? $requested : base_path($requested));
        $directory = dirname($output);

        try {
            if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
                throw new RuntimeException("Direktori keluaran tidak dapat dicipta: {$directory}");
            }
            if (file_exists($output)) {
                throw new RuntimeException("Fail keluaran sudah wujud: {$output}");
            }

            $definition = file_get_contents(database_path('sql/create_padanan_institusi_pengkhususan_mariadb.sql'));
            if ($definition === false) {
                throw new RuntimeException('Definisi jadual MariaDB tidak dapat dibaca.');
            }

            $schema = trim((string) config('services.mqr.mapping_schema'));
            if ($schema !== '' && preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/', $schema) !== 1) {
                throw new RuntimeException('Nama skema jadual padanan MQR tidak sah.');
            }
            $sourceTable = $schema === ''
                ? 'padanan_institusi_pengkhususan'
                : $schema.'.padanan_institusi_pengkhususan';
            $connection = DB::connection('mqr_mapping');
            $temporary = tempnam($directory, '.mqr-padanan-');
            if ($temporary === false) {
                throw new RuntimeException('Fail sementara eksport tidak dapat dicipta.');
            }

            $handle = fopen($temporary, 'wb');
            if ($handle === false) {
                unlink($temporary);
                throw new RuntimeException('Fail sementara eksport tidak dapat dibuka.');
            }

            try {
                $count = 0;
                $connection->transaction(function () use ($connection, $sourceTable, $definition, $handle, &$count): void {
                    $this->write($handle, "-- Salinan penuh padanan MQR; jalankan pada pangkalan data MariaDB sasaran.\n");
                    $this->write($handle, "SET NAMES utf8;\nDROP TABLE IF EXISTS `padanan_institusi_pengkhususan`;\n");
                    $this->write($handle, $definition."\n");

                    $columns = implode(', ', array_map(fn (string $column): string => '`'.$column.'`', self::COLUMNS));
                    $connection->table($sourceTable)->select(self::COLUMNS)
                        ->chunkById(250, function ($rows) use ($connection, $handle, $columns, &$count): void {
                            $values = [];
                            foreach ($rows as $row) {
                                $cells = [];
                                foreach (self::COLUMNS as $column) {
                                    $value = $row->{$column};
                                    if ($value === null) {
                                        $cells[] = 'NULL';

                                        continue;
                                    }
                                    $quoted = $connection->getPdo()->quote((string) $value);
                                    if ($quoted === false) {
                                        throw new RuntimeException("Nilai {$column} tidak dapat dikodkan untuk eksport.");
                                    }
                                    $cells[] = $quoted;
                                }
                                $values[] = '('.implode(', ', $cells).')';
                            }
                            $this->write($handle, 'INSERT INTO `padanan_institusi_pengkhususan` ('.$columns.") VALUES\n".implode(",\n", $values).";\n");
                            $count += count($rows);
                        }, 'kod');
                });

                $this->write($handle, "-- Jumlah rekod: {$count}\n");
                fclose($handle);
                if (! rename($temporary, $output)) {
                    throw new RuntimeException('Fail eksport tidak dapat disimpan.');
                }
            } catch (Throwable $exception) {
                if (is_resource($handle)) {
                    fclose($handle);
                }
                @unlink($temporary);
                throw $exception;
            }

            $this->info("Eksport {$count} rekod siap: {$output}");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    /** @param resource $handle */
    private function write($handle, string $content): void
    {
        if (fwrite($handle, $content) !== strlen($content)) {
            throw new RuntimeException('Fail eksport tidak dapat ditulis sepenuhnya.');
        }
    }
}
