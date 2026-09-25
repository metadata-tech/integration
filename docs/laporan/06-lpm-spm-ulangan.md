# LPM SVK SPM Ulangan

Semakan keputusan SPM Ulangan melalui Lembaga Peperiksaan (SVK).

| Medan | Nilai |
|-------|--------|
| Kod katalog | `lpm-spm-ulangan` / `LPM` |
| Service | `App\Services\LPMSVKService` (`callAPISPMUlangan`) |
| Inbound hub | `POST /api/lpm/svkspmulangan` |
| Outbound | Login + `POST {lpm_svk_url}/api/verification/spmu` |

Perkhidmatan yang sama dengan SVK SPM; hanya path verification berbeza.

---

## Development / SIT Environment

| Medan | Nilai |
|-------|--------|
| URL / Endpoint (hub) | `http://10.29.53.228/api/lpm/svkspmulangan` |
| URL / Endpoint (agensi) | **Perlu diisi** — `lpm_svk_url` + `/api/verification/spmu` |
| Application Server | Hub: `10.29.53.228`. Agensi LPM: **Perlu diisi** |
| Database | Log: `api_logs` (URL mengandungi `/api/verification/spmu`) |
| Middleware / API Gateway | Hub: Sanctum. Agensi: login email/password → Bearer |
| Network | Hub `10.29.x.x`. Laluan ke LPM: **Perlu diisi** |
| Versi aplikasi | Hub Laravel 11.45.1 |
| Versi API / service | Path `/api/verification/spmu` |
| Tarikh deployment | **Perlu diisi** |

MySPP SIT: `callSvkspmulanganApi()`.

---

## Staging

| Medan | Nilai |
|-------|--------|
| URL / Endpoint (hub) | `{base hub}/lpm/svkspmulangan` |
| URL / Endpoint (agensi) | **Perlu diisi** — `lpm_svk_url` staging + `/api/verification/spmu` |
| Application Server | **Perlu diisi** |
| Database | **Perlu diisi** |
| Middleware / API Gateway | Sama |
| Network | **Perlu diisi** |
| Versi aplikasi | **Perlu diisi** |
| Versi API / service | `/api/verification/spmu` |
| Tarikh deployment | **Perlu diisi** |

---

## Kontrak panggilan

**Inbound**

- Method: `POST` JSON
- Body: `no_kp`, `tahun`, `angka_giliran`
- Auth: Sanctum Bearer

**Outbound**

- Token: sama seperti SVK SPM (`POST /api/auth/login`)
- Semakan: `POST {lpm_svk_url}/api/verification/spmu`
- Body: `{ "tahun", "nic", "akg" }`

## Rujukan kod

- `app/Services/LPMSVKService.php` (`callAPISPMUlangan`)
- `app/Http/Controllers/Api/LpmController.php` (`svkspmulangan`)
- `app/Livewire/LPMSVKSPMUlanganForm.php`
