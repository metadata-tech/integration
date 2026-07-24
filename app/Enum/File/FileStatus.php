<?php
    namespace App\Enum\File;

	use App\Traits\Enum;

    enum FileStatus: int {
		use Enum;

        //old status
        // S- STOR(TIDAK AKTIF)
        // O-FAIL KELUAR (AKTIF)
        // I-FAIL MASUK (AKTIF)
        // L-LUPUS (TIDAK AKTIF)
        // A-ARKIB (AKTIF)
        // L-LUPUS (AKTIF)

        case STOR                   = 10;
        case DALAM_PERMOHONAN       = 15; // boleh
        case FAIL_KELUAR            = 20; // boleh dalam pinjaman
        case FAIL_MASUK             = 30; // boleh sedia dipinjam
        case HILANG                 = 31;
        case TUTUP                  = 32;
        case LEWAT                  = 33; // boleh
        case SEDIA_LUPUS            = 35; // boleh
        case DALAM_PROSES_PELUPUSAN = 37;
        case LUPUS                  = 40;
        case ARKIB                  = 50;

        /**
         * pengeluarann status
         */
        case PROCESS_PENNGELUARAN = 60;

        public static function badge(int $status): string {
            switch ($status) {
                case self::STOR->value:
                    return '<div class="rounded small fw-medium shadow-sm p-1 text-center" style="text-align: center; color: rgb(68, 74, 92); background-color: thistle;">STOR</div>';
                case self::FAIL_KELUAR->value:
                    return '<div class="rounded small fw-medium shadow-sm p-1 text-center" style="text-align: center; color: white; background-color: rgb(80, 165, 241);">DALAM PINJAMAN</div>';
                case self::FAIL_MASUK->value:
                    return '<div class="rounded small fw-medium shadow-sm p-1 text-center" style="text-align: center; color: white; background-color: rgb(40, 199, 111);">SEDIA DIPINJAM</div>';
                case self::SEDIA_LUPUS->value:
                    return '<div class="rounded small fw-medium shadow-sm p-1 text-center" style="text-align: center; color: white; background-color: rgb(233, 30, 99);">SEDIA LUPUS</div>';
                case self::DALAM_PROSES_PELUPUSAN->value:
                    return '<div class="rounded small fw-medium shadow-sm p-1 text-center" style="text-align: center; color: #643600; background-color: #FAE3CD;">DALAM PROSES PELUPUSAN</div>';
                case self::LUPUS->value:
                    return '<div class="rounded small fw-medium shadow-sm p-1 text-center" style="text-align: center; color: white; background-color: rgb(233, 30, 99);">LUPUS</div>';
                case self::ARKIB->value:
                    return '<div class="rounded small fw-medium shadow-sm p-1 text-center" style="text-align: center; color: white; background-color: rgb(156, 39, 176);">ARKIB</div>';
                case self::DALAM_PERMOHONAN->value:
                    return '<div class="rounded small fw-medium shadow-sm p-1 text-center" style="text-align: center; color: white; background-color: rgb(5, 148, 113);">DALAM PERMOHONAN</div>';
                case self::PROCESS_PENNGELUARAN->value:
                    return '<div class="rounded small fw-medium shadow-sm p-1 text-center" style="text-align: center; color: white; background-color: rgb(5, 148, 110);">PROSES PENGELUARAN</div>';
                case self::HILANG->value:
                    return '<div class="rounded small fw-medium shadow-sm p-1 text-center" style="text-align: center; color: white; background-color: rgb(82, 93, 87);">TIADA DILOKASI</div>';
                case self::TUTUP->value:
                    return '<div class="rounded small fw-medium shadow-sm p-1 text-center" style="text-align: center; color: white; background-color: rgb(70, 101, 16);">FAIL TUTUP</div>';
                case self::LEWAT->value:
                    return '<div class="rounded small fw-medium shadow-sm p-1 text-center" style="text-align: center; color: white; background-color: rgb(244, 106, 106);">LEWAT</div>';
                default:
                    return '<div class="rounded small fw-medium shadow-sm p-1 text-center" style="text-align: center; color: white; background-color: rgb(206, 212, 218);">-</div>';
            }

        }

        public function description(): string {
            return match ($this) {
                self::STOR => 'STOR',
                self::DALAM_PERMOHONAN => 'DALAM PERMOHONAN',
                self::FAIL_KELUAR => 'DALAM PINJAMAN',
                self::FAIL_MASUK => 'SEDIA DIPINJAM',
                self::HILANG => 'TIADA DILOKASI',
                self::TUTUP => 'FAIL TUTUP',
                self::LEWAT => 'LEWAT',
                self::SEDIA_LUPUS => 'SEDIA LUPUS',
                self::LUPUS => 'LUPUS',
                self::ARKIB => 'ARKIB',
                self::PROCESS_PENNGELUARAN => 'PROSES PENGELUARAN'
            };
        }

        public static function filterStatus()
        {
            $filter = collect([
                'SEDIA DIPINJAM' => self::FAIL_MASUK,
                'DALAM PINJAMAN' => self::FAIL_KELUAR,
                'FAIL TUTUP' => self::TUTUP,
                'LUPUS' => self::LUPUS,
                'HILANG' => self::HILANG,
                'DALAM PROSES PELUPUSAN' => self::SEDIA_LUPUS,
                'PROSES PENGELUARAN' => self::PROCESS_PENNGELUARAN,
                'ARKIB' => self::ARKIB,
            ]);

            return $filter;
        }
	}

