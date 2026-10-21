# KPT SKPG

Semakan maklumat pelajar melalui sistem KPT SKPG (MOHE Student). Hub menapis tahap kursus dan status pelajar serta membuang rekod pendua.

| Medan | Nilai |
|-------|--------|
| Kod katalog | `kpt-skpg` / `KPT` |
| Service | `App\Services\KPTSKPGService` |
| Inbound hub | `GET /api/kpt/skpg` (body JSON `no_kp` turut diterima oleh MySPP) |
| Outbound | Login + `POST {kpt_skpg_url}/MOHE/Student` |

---

## Development / SIT Environment

Live SIT box: host `eSPP2F1`, IP `10.29.53.228`, app `/var/www/myspp_integration`, `.env` last written **7 Aug 2026**. Apache vhost `spp_integration.conf`, PHP **8.3.22**. Use the **www** `.env`, not `/home/sppadmin/myspp_integration/.env`.

### Hub

| Medan | SIT |
|-------|-----|
| URL / Endpoint | `http://10.29.53.228/api/kpt/skpg` |
| Application Server | Ubuntu 24.04, Apache, `10.29.53.228` (`eSPP2F1`) |
| APP_NAME / APP_ENV | `Integration` / `local` (`APP_DEBUG=false`) |
| APP_URL | `http://localhost` (not set to the public/SIT URL) |
| Database | MySQL `integrasi_dev` @ `10.29.53.234:3306` |
| Middleware | Laravel Sanctum (no extra gateway in `.env`) |
| Network | `10.29.53.228` |
| Versi aplikasi | Laravel 11.45.1, PHP 8.3.22 |
| Versi API / service | `GET /api/kpt/skpg` |
| Tarikh deployment | `.env` 7 Ogos 2026 |

### Agency (KPT SKPG)

| Medan | SIT |
|-------|-----|
| URL / Endpoint | `https://amgs.mohe.gov.my` (`kpt_skpg_url`) |
| Token | `POST https://amgs.mohe.gov.my/Login` |
| Data | `POST https://amgs.mohe.gov.my/MOHE/Student` |
| Application Server | AMGS MOHE (`amgs.mohe.gov.my`) |
| Database | Tiada DB SKPG di sisi SPP. Extra hub DBs: iSCAF `iscaf_test` @ `10.29.50.225`; MQR mapping `spp_ref` @ `10.29.53.234` |
| Middleware / API Gateway | Username/password → Bearer `data.auth_Token` |
| Network | Hub `10.29.53.228` → HTTPS `amgs.mohe.gov.my` |
| Versi API / service | `/Login` + `/MOHE/Student` (tiada nombor versi). Filter SPP: `level_course_id` `2–7` dan senarai `status_pelajar` di bawah |
| Tarikh deployment | **Perlu diisi** (agensi) |

MySPP memanggil SIT terus: `_mapps/akademik/sql_akademik.php` → `callKptSkpgApi()`.

---

## Staging

| Medan | Nilai |
|-------|--------|
| URL / Endpoint (hub) | `{base hub}/kpt/skpg` |
| URL / Endpoint (agensi) | **Perlu diisi** — `kpt_skpg_url` staging |
| Application Server | **Perlu diisi** |
| Database | **Perlu diisi** |
| Middleware / API Gateway | Sama |
| Network | **Perlu diisi** |
| Versi aplikasi | **Perlu diisi** |
| Versi API / service | **Perlu diisi** |
| Tarikh deployment | **Perlu diisi** |

---

## Kontrak panggilan

**Inbound**

- Method: `GET` (MySPP hantar JSON body `{"no_kp":"..."}` pada GET)
- Parameter: `no_kp`
- Auth: Sanctum Bearer

**Outbound — 1. Token**

- `POST {kpt_skpg_url}/Login`
- Body: `username`, `password`
- Token diambil dari `data.auth_Token`

**Outbound — 2. Data**

- `POST {kpt_skpg_url}/MOHE/Student`
- Body: `{ "ic_no": "{no_kp}" }`
- Header: `Authorization: Bearer {token}`

**Tapisan di hub**

- Hanya rekod dengan `level_course_id` ∈ `2, 3, 4, 5, 6, 7`
- Hanya rekod dengan `status_pelajar` berikut diterima (huruf besar/kecil dan ruang di tepi diabaikan): `Aktif`, `Aktif (Kes Khas)`, `Aktif (Outbound Exchange)`, `Tangguh Pengajian`, `Gap Year Khidmat Negara`, `Gap Year Kesukarelawan`, `Gap Year Umum`, `Gantung`, dan `Tamat Pengajian`.
- Status lain, termasuk `Aktif (Inbound Exchange)` dan `Tamat Pengajian Dengan Sebab Lain`, serta rekod tanpa status ditolak.
- Rekod dengan `CGPA` bernilai `-1`, `-2` atau `-200` ditolak. Nilai nombor dan rentetan nombor dipadankan secara angka; kod negatif lain tidak ditapis oleh syarat ini.
- Pendua dibuang (hash kanonikal)
- Mesej `TOTAL ROW : N` dikemas kini

## Rujukan kod

- `app/Services/KPTSKPGService.php`
- `app/Http/Controllers/Api/KptController.php` (`skpg`)
- `app/Livewire/KPTSKPGForm.php`
- `tests/Feature/KPTSKPGServiceTest.php`
- MySPP `_mapps/akademik/sql_akademik.php`
