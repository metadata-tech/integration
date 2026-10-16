# MQR (Malaysian Qualifications Register)

Penarikan data rujukan MQR dan automasi padanan kelayakan ke jadual rujukan SPP (`padanan_institusi_pengkhususan`).

| Medan | Nilai |
|-------|--------|
| Kod katalog | `mqr` / `MQR` |
| Service | `App\Services\MQRService`, `MQRReportService` |
| Inbound hub | Tiada laluan `/api` awam. Sync / padanan dari UI `/integrasi/mqr` |
| Outbound | `GET {MQR_URL}` — eSPP hardcode `http://10.29.216.151/api/SPP/request-MQR` |

---

## Development / SIT Environment

| Medan | Nilai |
|-------|--------|
| URL / Endpoint (hub UI) | `http://10.29.53.228/integrasi/mqr` (web, bukan `/api`) |
| URL / Endpoint (agensi) | `http://10.29.216.151/api/SPP/request-MQR` (eSPP `MqrClient`; hub guna env `MQR_URL`) |
| Application Server | Hub: `10.29.53.228`. API MQR: `10.29.216.151` |
| Database | Snapshot: `mqr_data` pada DB hub (`myspp_integration`). Padanan: connection `mqr_mapping` → local `spamenu` (jadual `ref_institusi`, `ref_pengkhususan`, `ref_peringkat_kelulusan`, `padanan_institusi_pengkhususan`) |
| Middleware / API Gateway | UI: session Sanctum/Jetstream. Agensi: HTTP GET, **tiada** auth dalam kod |
| Network | `10.29.x.x` (MQR `10.29.216.151`) |
| Versi aplikasi | Hub Laravel 11.45.1 |
| Versi API / service | Path `/api/SPP/request-MQR` (tiada nombor versi). Payload `dataMQR` |
| Tarikh deployment | **Perlu diisi**. Migrasi snapshot: `2026_08_06_000001_create_mqr_data_table` |

---

## Staging / Production

| Medan | Nilai |
|-------|--------|
| URL / Endpoint (hub) | `{APP_URL}/integrasi/mqr` |
| URL / Endpoint (agensi) | **Perlu sahkan** sama ada `10.29.216.151` kekal atau URL staging berasingan (`MQR_URL`) |
| Application Server | **Perlu diisi** |
| Database | Production comment: `database=spa8i` + `schema=spp_ref` (`MQR_MAPPING_DB_*`) |
| Middleware / API Gateway | Tiada gateway dalam kod |
| Network | **Perlu diisi** |
| Versi aplikasi | Sama |
| Versi API / service | `request-MQR` |
| Tarikh deployment | **Perlu diisi** |

---

## Kontrak panggilan

**Outbound sync** (`MQRService::syncData`)

- Method: `GET`
- Timeout HTTP: 120s; `set_time_limit(0)`
- Respons: `{ dataMQR: [ ... ] }`
- Upsert ke `mqr_data` pada `kod_rujukan_akreditasi_penuh`

Medan yang dipetakan: `KodRujukanAkreditasiPenuh`, `IdInstitusi`, `NamaInstitusi`, `KodNEC`, `BidangNEC`, `NamaKelayakanBM/BI`, `NamaKelayakanBaruBM/BI`, `PeringkatKelayakan`, `Tahap`, tarikh akreditasi.

eSPP `MqrClient` menolak payload jika `< 1000` rekod.

**Padanan**

- Tulis ke `padanan_institusi_pengkhususan` pada connection `mqr_mapping`
- Padanan institusi dan pengkhususan menggunakan nama tepat selepas trim dan tanpa mengira huruf besar/kecil. Nama baharu didaftarkan secara automatik ke jadual rujukan, lalu dipadankan dalam batch yang sama. Kod rujukan baharu ialah kod angka terbesar tambah satu.
- Cawangan mempunyai rujukan institusi sendiri; `KUMPULAN_INSTITUSI` menghubungkan keluarga yang jelas seperti `UITM`, `IPGM`, `POLI` dan `UNIKL`. Kumpulan yang tidak dapat dipastikan dibiarkan kosong.
- `JENIS_INSTITUSI` bagi institusi baharu mengikuti nilai rujukan sedia ada dalam kumpulan yang sama. Nama rasmi institusi awam tertentu yang tiada kumpulan turut dikenal pasti; rujukan lain menggunakan jenis `2`, selaras dengan pola rujukan sedia ada. Migrasi susulan mengisi nilai kosong pada institusi yang sudah didaftarkan oleh proses MQR tanpa mengubah nilai yang telah diisi.
- `TARIKH_CIPTA` dan `TARIKH_UBAHSUAI` diisi serentak semasa institusi atau pengkhususan didaftarkan. Migrasi susulan menggunakan `created_at` dalam audit pendaftaran untuk mengisi tarikh kosong pada rujukan yang didaftarkan sebelum pembetulan, tanpa menimpa tarikh yang sedia ada.
- Setiap kod MQA yang berbeza disimpan sebagai rekod padanan berasingan walaupun kod institusi dan pengkhususan sama. Satu kod MQA tidak boleh muncul pada lebih daripada satu padanan aktif. Rekod pendua lama disatukan kepada `kod` terkecil, selebihnya dinyahaktifkan dan diaudit.
- Pengkhususan baharu mendapat `KATEGORI=1` jika mana-mana rekod nama sama menggunakan NEC `0110`–`0114`; selain itu `KATEGORI=2`.
- Untuk `PeringkatKelayakan` MQR `9`, `id_peringkat_kelulusan` ialah `1` bagi NEC `0110`–`0114`, atau `2` bagi NEC lain. Kolum `peringkat_kelulusan` tidak diubah.
- Laporan muat turun: `/mqr/reports/{run}/{all|success|failure|references|duplicates}`. Laporan pendua memaparkan ID yang dikekalkan/dinyahaktifkan, pasangan lama/akhir dan hasil padanan.

### Pemindahan jadual padanan daripada MySQL ke MariaDB

Navicat Data Transfer boleh menjana `mqr_kod_mqa_aktif ... VIRTUAL NULL` daripada metadata MySQL. MariaDB menolak `NULL` selepas `VIRTUAL`, walaupun lajur terjana itu sendiri boleh menghasilkan nilai NULL. Definisi yang sesuai disediakan dalam `database/sql/create_padanan_institusi_pengkhususan_mariadb.sql`.

Untuk satu fail eksport yang boleh terus dijalankan pada MariaDB, gunakan `php artisan mqr:export-padanan-mariadb`. Lokasi fail dipaparkan selepas eksport; `--output=/lokasi/fail.sql` membenarkan lokasi khusus. Fail ini menggugurkan dan mencipta semula **jadual padanan sahaja**, kemudian memasukkan semua rekod tanpa lajur terjana. Data jadual sasaran yang sedia ada akan digantikan apabila fail dijalankan.

Jika menggunakan Navicat Data Transfer pula:

1. Cipta jadual pada MariaDB sasaran menggunakan skrip tersebut.
2. Dalam Navicat Data Transfer, nyahpilih **Create tables** dan **Drop target objects before create**, tetapi kekalkan **Create records**. Pilih mod **Advanced → Custom fields** untuk jadual ini dan pindahkan semua lajur biasa kecuali `mqr_kod_mqa_aktif`; MariaDB akan menjana nilainya sendiri.
3. Selepas pemindahan, semak jumlah rekod dan semak bahawa kod MQA aktif tidak mempunyai pendua dengan kumpulan `UPPER(TRIM(kod_mqa))`.

Pemindahan semula ke jadual sasaran yang telah berisi akan menambah rekod; kosongkan jadual sasaran dahulu hanya jika ia memang salinan yang hendak diganti.

## Rujukan kod

- `app/Services/MQRService.php`
- `app/Livewire/MQRForm.php`
- `app/Http/Controllers/MQRReportController.php`
- eSPP `integrasi/class/MqrClient.php`
- `.env.example` (`MQR_MAPPING_*`)
