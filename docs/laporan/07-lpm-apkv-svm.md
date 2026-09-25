# LPM APKV / SVM

Semakan keputusan SVM / APKV melalui Lembaga Peperiksaan.

| Medan | Nilai |
|-------|--------|
| Kod katalog | `lpm-apkv` / `LPM` |
| Service | `App\Services\APKVService` |
| Inbound hub | `POST /api/lpm/svm` |
| Outbound | `POST {lpm_apkv_url}` |

---

## Development / SIT Environment

| Medan | Nilai |
|-------|--------|
| URL / Endpoint (hub) | `http://10.29.53.228/api/lpm/svm` |
| URL / Endpoint (agensi) | **Perlu diisi** — `.env` `lpm_apkv_url` (URL penuh) |
| Application Server | Hub: `10.29.53.228`. Agensi LPM: **Perlu diisi** |
| Database | Log: `api_logs` |
| Middleware / API Gateway | Hub: Sanctum. Agensi: header `Authorization` statik (`lpm_apkv_bearer`) — tiada login semula |
| Network | Hub `10.29.x.x`. Laluan ke LPM: **Perlu diisi** |
| Versi aplikasi | Hub Laravel 11.45.1 |
| Versi API / service | **Perlu diisi** (agensi) |
| Tarikh deployment | **Perlu diisi** |

MySPP SIT: `http://10.29.53.228/api/lpm/svm`.

---

## Staging

| Medan | Nilai |
|-------|--------|
| URL / Endpoint (hub) | `{base hub}/lpm/svm` |
| URL / Endpoint (agensi) | **Perlu diisi** — `lpm_apkv_url` staging |
| Application Server | **Perlu diisi** |
| Database | **Perlu diisi** |
| Middleware / API Gateway | Bearer statik (`lpm_apkv_bearer`) |
| Network | **Perlu diisi** |
| Versi aplikasi | **Perlu diisi** |
| Versi API / service | **Perlu diisi** |
| Tarikh deployment | **Perlu diisi** |

---

## Kontrak panggilan

**Inbound**

- Method: `POST` JSON
- Body: `no_kp`, `angka_giliran`
- Auth: Sanctum Bearer

**Outbound**

- Method: `POST`
- Body: `{ "noKadPengenalan", "angkaGiliran" }`
- Header: `Authorization: {lpm_apkv_bearer}` (nilai penuh dari env; tiada prefix `Bearer` ditambah dalam kod)

## Rujukan kod

- `app/Services/APKVService.php`
- `app/Http/Controllers/Api/LpmController.php` (`svm`)
- `app/Livewire/LPMAPKVForm.php`
- MySPP `_mapps/akademik/sql_akademik.php`
