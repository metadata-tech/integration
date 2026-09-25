# LPM SVK SPM

Semakan keputusan SPM melalui Lembaga Peperiksaan (sistem SVK).

| Medan | Nilai |
|-------|--------|
| Kod katalog | `lpm-svk-spm` / `LPM` |
| Service | `App\Services\LPMSVKService` |
| Inbound hub | `POST /api/lpm/svkspm` |
| Outbound | Login + `POST {lpm_svk_url}/api/verification/spm` |

---

## Development / SIT Environment

| Medan | Nilai |
|-------|--------|
| URL / Endpoint (hub) | `http://10.29.53.228/api/lpm/svkspm` |
| URL / Endpoint (agensi) | **Perlu diisi** — `.env` `lpm_svk_url` + path `/api/verification/spm` |
| Application Server | Hub: `10.29.53.228`. Agensi LPM: **Perlu diisi** |
| Database | Tiada DB LPM di sisi SPP. Log: `api_logs` (URL mengandungi `/api/verification/spm`) |
| Middleware / API Gateway | Hub: Sanctum. Agensi: `POST /api/auth/login` → Bearer `user._token` |
| Network | Hub `10.29.x.x`. Laluan ke LPM: **Perlu diisi** |
| Versi aplikasi | Hub Laravel 11.45.1 |
| Versi API / service | Path agensi `/api/verification/spm` (tiada nombor versi) |
| Tarikh deployment | **Perlu diisi** (LPM mula aktif dalam repo ~7 Jun 2025) |

MySPP SIT: `callSvkspmApi()` dalam `sql_akademik.php`.

---

## Staging

| Medan | Nilai |
|-------|--------|
| URL / Endpoint (hub) | `{base hub}/lpm/svkspm` |
| URL / Endpoint (agensi) | **Perlu diisi** — `lpm_svk_url` staging |
| Application Server | **Perlu diisi** |
| Database | **Perlu diisi** |
| Middleware / API Gateway | Sama |
| Network | **Perlu diisi** |
| Versi aplikasi | **Perlu diisi** |
| Versi API / service | `/api/verification/spm` |
| Tarikh deployment | **Perlu diisi** |

---

## Kontrak panggilan

**Inbound**

- Method: `POST` JSON
- Body: `no_kp`, `tahun` (integer), `angka_giliran`
- Auth: Sanctum Bearer

**Outbound — 1. Token**

- `POST {lpm_svk_url}/api/auth/login`
- Body: `email` (`lpm_svk_email`), `password` (`lpm_svk_password`)
- Token: `user._token`

**Outbound — 2. Semakan**

- `POST {lpm_svk_url}/api/verification/spm`
- Body: `{ "tahun", "nic", "akg" }` (`akg` = angka giliran)
- Header: `Authorization: Bearer {token}`

## Nota

- `LPMService` lama (`lpm_url` + header `x-api-key`, action `keputusan_spm`) **tidak** di-route lagi.
- Health check katalog guna `lpm_svk_url`; log ditapis supaya tidak campur SPM Ulangan (`/api/verification/spmu`).

## Rujukan kod

- `app/Services/LPMSVKService.php` (`callAPI`)
- `app/Http/Controllers/Api/LpmController.php` (`svkspm`)
- `app/Livewire/LPMForm.php`
- MySPP `_mapps/akademik/sql_akademik.php`
