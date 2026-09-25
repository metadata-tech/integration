# KPM ePenempatan / eOperasi

Semakan maklumat penempatan dan operasi guru melalui KPM.

| Medan | Nilai |
|-------|--------|
| Kod katalog | `kpm` / `KPM` |
| Service | `App\Services\KPMService` |
| Inbound hub | `GET /api/kpm?no_kp=` |
| Outbound | `GET {kpm_url}?nric=` |

---

## Development / SIT Environment

| Medan | Nilai |
|-------|--------|
| URL / Endpoint (hub) | `http://10.29.53.228/api/kpm` |
| URL / Endpoint (agensi) | **Perlu diisi** — `.env` `kpm_url` pada pelayan SIT |
| Application Server | Hub: `10.29.53.228`. Agensi KPM: **Perlu diisi** |
| Database | Tiada DB KPM di sisi SPP. Log: `api_logs` |
| Middleware / API Gateway | Hub: Sanctum. Agensi: header `APIKEY` (`kpm_key`) |
| Network | Hub `10.29.x.x`. Laluan ke KPM: **Perlu diisi** |
| Versi aplikasi | Hub Laravel 11.45.1 |
| Versi API / service | **Perlu diisi** (agensi) |
| Tarikh deployment | **Perlu diisi** |

---

## Staging

| Medan | Nilai |
|-------|--------|
| URL / Endpoint (hub) | `{base hub}/kpm` |
| URL / Endpoint (agensi) | **Perlu diisi** — `kpm_url` staging |
| Application Server | **Perlu diisi** |
| Database | **Perlu diisi** |
| Middleware / API Gateway | Sama (`APIKEY`) |
| Network | **Perlu diisi** |
| Versi aplikasi | **Perlu diisi** |
| Versi API / service | **Perlu diisi** |
| Tarikh deployment | **Perlu diisi** |

---

## Kontrak panggilan

**Inbound**

- Method: `GET`
- Query: `no_kp` (wajib)
- Auth: Sanctum Bearer

**Outbound**

- Method: `GET`
- Query: `nric`
- Header: `APIKEY`
- SSL verify: dimatikan

eSPP: `perkhidmatan/integrasi_kpm.php` dan `integrasi/scan_anggota_integrasi.php`.

## Rujukan kod

- `app/Services/KPMService.php`
- `app/Http/Controllers/Api/KpmController.php`
- `app/Livewire/KPMForm.php`
