# Hub SPP Integration

Aplikasi Laravel yang menjadi perantara eSPP / MySPP dengan API agensi. UI semakan di `/integrasi/{slug}`, log di `api_logs`.

| Medan | Nilai |
|-------|--------|
| Kod | `htdocs/integration` |
| Nama aplikasi (`.env`) | `SPP Integration` |
| Framework | Laravel 11.45.1, PHP ^8.2, Livewire 3, Jetstream 5, Sanctum 4 |
| Timezone kod | `APP_TIMEZONE=UTC` (`.env` local); eSPP/MySPP guna Asia/Kuala_Lumpur |

---

## Development / SIT Environment

| Medan | Nilai | Sumber |
|-------|--------|--------|
| URL / Endpoint | `http://10.29.53.228/api` | MySPP `_mapps/akademik/sql_akademik.php`; eSPP pautan `http://10.29.53.228/myspp` |
| Application Server | `10.29.53.228` | Hardcode pengguna inbound |
| Database | MySQL `myspp_integration` @ `127.0.0.1:3306` (contoh local) | `.env.example` / `.env` local |
| Middleware / API Gateway | Laravel Sanctum Bearer token (`auth:sanctum`). Tiada API Gateway berasingan dalam kod | `routes/api.php` |
| Network | Rangkaian dalaman SPP `10.29.x.x` | IP SIT + rujukan tapisan `10.29.53.234` |
| Versi aplikasi | Laravel 11.45.1 | `composer.lock` |
| Versi API / service | REST tanpa prefix versi (`/api/{agensi}`) | `routes/api.php` |
| Tarikh deployment | **Perlu diisi** | Repo first commit 26 Mei 2025 |

Local development: `APP_URL=http://localhost`, `APP_ENV=local`.

---

## Staging

| Medan | Nilai | Sumber |
|-------|--------|--------|
| URL / Endpoint | `http://integrasi.spp.gov.my/api` | Fallback eSPP `EsisClient` / `IscafClient` jika `Constant` / env kosong |
| Application Server | **Perlu diisi** (hostname `integrasi.spp.gov.my`) | |
| Database | **Perlu diisi** (nama schema production) | |
| Middleware / API Gateway | Sanctum (sama) | |
| Network | **Perlu diisi** — awam / DMZ / VPN? | |
| Versi aplikasi | Sama dengan build yang di-deploy | |
| Versi API / service | Sama | |
| Tarikh deployment | **Perlu diisi** | |

eSPP membaca `INTEGRASI_API_BASE_URL` / `ESIS_API_BASE_URL` atau `Constant::$integrasiApiBaseUrl` (fail `common/Constant.php` gitignored).

---

## Laluan inbound (`routes/api.php`)

Semua di belakang `auth:sanctum` kecuali kesihatan aplikasi `GET /up`.

| Method | Path | Controller |
|--------|------|------------|
| GET | `/api/user` | Closure (user semasa) |
| GET | `/api/jpn/semak` | `JpnController@semakMaklumat` |
| GET | `/api/kpm` | `KpmController@penempatanOperasi` |
| GET | `/api/kpt/esis` | `KptController@esis` |
| GET | `/api/kpt/skpg` | `KptController@skpg` |
| POST | `/api/lpm/svkspm` | `LpmController@svkspm` |
| POST | `/api/lpm/svkspmulangan` | `LpmController@svkspmulangan` |
| POST | `/api/lpm/svm` | `LpmController@svm` |
| POST | `/api/mygdx/muet` | `MyGDXController@muet` |
| POST | `/api/mygdx/stpm` | `MyGDXController@stpm` |
| POST | `/api/iscaf/status` | `IscafController@status` |
| POST | `/api/iscaf/daftar` | `IscafController@daftar` |
| POST | `/api/iscaf/informasi` | `IscafController@informasi` |
| POST | `/api/iscaf/kemas-kini` | `IscafController@kemasKini` |
| GET | `/api/myspp/dokumen` | `MysppController@dokumen` (binary proxy ke MySPP `espp_dokumen.php`) |

UI web (bukan API): `/dashboard`, `/integrasi/{slug}`, `/log_api`, pengurusan token admin.

---

## Database (hub)

| Connection | Guna | Default |
|------------|------|---------|
| `mysql` (default) | users, sessions, tokens, `api_logs`, `mqr_data`, `mqr_mapping_runs` | `myspp_integration` |
| `mqr_mapping` | Padanan institusi/pengkhususan | `spamenu` local; production boleh `spa8i` + schema `spp_ref` |
| `iscaf_db` | Connection wujud; iSCAF semasa **tidak** query DB terus | `DB_*_iscaf` |

---

## Autentikasi inbound

- **Sanctum personal access token** (`Authorization: Bearer …`)
- Token dijana dari UI admin `/admin/{user}/tokens`
- eSPP: `Constant::$integrasiApiToken` atau env `INTEGRASI_API_TOKEN`
- MySPP: token hardcoded dalam `sql_akademik.php` (SIT)

---

## Rujukan kod

- `routes/api.php`, `routes/web.php`
- `config/services.php`, `config/integrations.php`, `config/database.php`
- `app/Http/Controllers/Api/*`
- `app/Services/*`
- `app/Models/APILog.php`
