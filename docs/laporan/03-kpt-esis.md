# KPT eSIS

Semakan maklumat pelajar institusi pengajian tinggi melalui KPT eSIS.

| Medan | Nilai |
|-------|--------|
| Kod katalog | `kpt-esis` / `KPT` |
| Service | `App\Services\KPTService` |
| Inbound hub | `GET /api/kpt/esis?no_kp=` |
| Outbound | Login + `GET {kpt_esis_url}/esis/spp/{no_kp}` |

---

## Development / SIT Environment

| Medan | Nilai |
|-------|--------|
| URL / Endpoint (hub) | `http://10.29.53.228/api/kpt/esis` |
| URL / Endpoint (agensi) | **Perlu diisi** — `.env` `kpt_esis_url` (base, tanpa path) |
| Application Server | Hub: `10.29.53.228`. Agensi KPT: **Perlu diisi** |
| Database | Tiada DB eSIS di sisi SPP. Log: `api_logs` (termasuk panggilan login) |
| Middleware / API Gateway | Hub: Sanctum. Agensi: login username/password → Bearer token |
| Network | Hub `10.29.x.x`. Laluan ke KPT: **Perlu diisi** |
| Versi aplikasi | Hub Laravel 11.45.1 |
| Versi API / service | **Perlu diisi** (agensi) |
| Tarikh deployment | **Perlu diisi** |

---

## Staging

| Medan | Nilai |
|-------|--------|
| URL / Endpoint (hub) | `{base hub}/kpt/esis` |
| URL / Endpoint (agensi) | **Perlu diisi** — `kpt_esis_url` staging |
| Application Server | **Perlu diisi** |
| Database | **Perlu diisi** |
| Middleware / API Gateway | Sama (login + Bearer) |
| Network | **Perlu diisi** |
| Versi aplikasi | **Perlu diisi** |
| Versi API / service | **Perlu diisi** |
| Tarikh deployment | **Perlu diisi** |

---

## Kontrak panggilan

**Inbound**

- Method: `GET`
- Query: `no_kp`
- Auth: Sanctum Bearer

**Outbound — 1. Token**

- `POST {kpt_esis_url}/consumer/login`
- Body JSON: `username`, `password` (`kpt_esis_username`, `kpt_esis_password`)
- Headers: `Content-Type` / `Accept: application/json`
- Respons: `{ token }`

**Outbound — 2. Data**

- `GET {kpt_esis_url}/esis/spp/{no_kp}`
- Header: `Authorization: Bearer {token}`

Jika token kosong, service pulangkan `null`.

eSPP: `EsisClient::fetchByNoKp()` → `{base}/kpt/esis?no_kp=`.

## Rujukan kod

- `app/Services/KPTService.php`
- `app/Http/Controllers/Api/KptController.php` (`esis`)
- `app/Livewire/KPTForm.php`
- eSPP `integrasi/class/EsisClient.php`
