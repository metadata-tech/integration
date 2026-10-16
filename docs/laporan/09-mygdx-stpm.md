# MyGDX STPM

Semakan keputusan STPM melalui MyGDX (X-Road) ke MPM SSKSM.

| Medan | Nilai |
|-------|--------|
| Kod katalog | `mygdx-stpm` (`/integrasi/mygdx-stpm`) |
| Service | `App\Services\MYGDXService` (`getSTPM`) |
| Inbound hub | `POST /api/mygdx/stpm` |
| Outbound | `POST https://spp.mygdx.gov.my` (SOAP) |

Infrastruktur sama dengan MUET; hanya `serviceCode` berbeza.

---

## Development / SIT Environment

| Medan | Nilai |
|-------|--------|
| URL / Endpoint (hub) | `http://10.29.53.228/api/mygdx/stpm` |
| URL / Endpoint (agensi) | `https://spp.mygdx.gov.my` |
| Application Server | Hub: `10.29.53.228`. Security server MyGDX: `spp.mygdx.gov.my` |
| Database | Log: `api_logs` |
| Middleware / API Gateway | Hub: Sanctum. Agensi: X-Road / MyGDX Security Server |
| Network | Client cert `espp.crt` / `espp.key`. Subsystem default `ESPP_STG` |
| Versi aplikasi | Hub Laravel 11.45.1 |
| Versi API / service | X-Road **4.0**, serviceVersion **v1**, service `MPM-SSKSM-R-MPMFikSTPM` |
| Tarikh deployment | **Perlu diisi** |

MySPP SIT: `callStpmMyGdxApi()` → `http://10.29.53.228/api/mygdx/stpm`.

---

## Staging

| Medan | Nilai |
|-------|--------|
| URL / Endpoint (hub) | `{base hub}/mygdx/stpm` |
| URL / Endpoint (agensi) | `https://spp.mygdx.gov.my` |
| Application Server | Security server MyGDX |
| Database | Tiada |
| Middleware / API Gateway | X-Road 4.0 |
| Network | `MYGDX/GOV/SPP/{ESPP_STG}` → `MYGDX/GOV/MPM/SSKSM` |
| Versi aplikasi | Sama |
| Versi API / service | v1 |
| Tarikh deployment | **Perlu diisi** |

---

## Kontrak panggilan

**Inbound**

- Method: `POST` JSON
- Body: `no_kp`, `tahun`
- Auth: Sanctum Bearer

**Outbound SOAP**

- Service code: `MPM-SSKSM-R-MPMFikSTPM`
- Envelope sama seperti MUET (`resources/MyGDX/stpm.xml`)

## Rujukan kod

- `app/Services/MYGDXService.php` (`getSTPM`)
- `app/Livewire/StpmForm.php` (borang semakan)
- `app/Http/Controllers/Api/MyGDXController.php` (`stpm`)
- `resources/MyGDX/stpm.xml`
- MySPP `_mapps/akademik/sql_akademik.php`
