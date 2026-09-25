# MyGDX MUET

Semakan keputusan MUET melalui perkhidmatan MyGDX (X-Road) ke MPM SSKSM.

| Medan | Nilai |
|-------|--------|
| Kod katalog | `mygdx-muet` / `GDX` |
| Service | `App\Services\MYGDXService` (`getMUET`) |
| Inbound hub | `POST /api/mygdx/muet` |
| Outbound | `POST https://spp.mygdx.gov.my` (SOAP) |

---

## Development / SIT Environment

| Medan | Nilai |
|-------|--------|
| URL / Endpoint (hub) | `http://10.29.53.228/api/mygdx/muet` |
| URL / Endpoint (agensi) | `https://spp.mygdx.gov.my` (hardcode dalam `MYGDXService`) |
| Application Server | Hub: `10.29.53.228`. Security server MyGDX: **Perlu diisi** (hostname `spp.mygdx.gov.my`) |
| Database | Tiada DB MyGDX. Log: `api_logs` (body XML) |
| Middleware / API Gateway | Hub: Sanctum. Agensi: **X-Road / MyGDX Security Server** (bukan API Gateway REST) |
| Network | Client cert `resources/MyGDX/espp.crt` + `espp.key`. Header `x-road-client: MYGDX/GOV/SPP/{subsystem}` |
| Versi aplikasi | Hub Laravel 11.45.1 |
| Versi API / service | X-Road protocol **4.0**, serviceVersion **v1**, service `MPM-SSKSM-R-MPMFikMUET` |
| Tarikh deployment | **Perlu diisi**. Contoh SOAP `xro:id` = `27082025` (27 Ogos 2025) |

Subsystem default: `ESPP_STG` — kod semasa menunjuk ke **staging MyGDX**, termasuk dari SIT.

MySPP SIT: `http://10.29.53.228/api/mygdx/muet`.

---

## Staging

| Medan | Nilai |
|-------|--------|
| URL / Endpoint (hub) | `{base hub}/mygdx/muet` |
| URL / Endpoint (agensi) | `https://spp.mygdx.gov.my` |
| Application Server | Security server MyGDX (SPP member) |
| Database | Tiada |
| Middleware / API Gateway | X-Road 4.0 |
| Network | Member `MYGDX/GOV/SPP`, subsystem `ESPP_STG` (env `MYGDX_SUBSYSTEM_CODE`) |
| Versi aplikasi | Sama |
| Versi API / service | v1 / protocol 4.0 |
| Tarikh deployment | **Perlu diisi** |

Production subsystem (contoh `ESPP`) **tidak** ada dalam kod; perlu tukar `MYGDX_SUBSYSTEM_CODE` semasa go-live.

---

## Kontrak panggilan

**Inbound**

- Method: `POST` JSON
- Body: `no_kp`, `tahun`
- Auth: Sanctum Bearer

**Outbound SOAP**

- Client: `MYGDX / GOV / SPP / {subsystemCode}`
- Service: `MYGDX / GOV / MPM / SSKSM / MPM-SSKSM-R-MPMFikMUET / v1`
- Body: `<nokp>`, `<tahun>`
- Content-Type: `application/soap+xml` (header) / `text/xml` (body)

## Rujukan kod

- `app/Services/MYGDXService.php`
- `app/Http/Controllers/Api/MyGDXController.php` (`muet`)
- `app/Livewire/MuetForm.php`
- `resources/MyGDX/muet.xml`
