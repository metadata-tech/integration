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
- Laporan muat turun: `/mqr/reports/{run}/{all|success|failure}`

## Rujukan kod

- `app/Services/MQRService.php`
- `app/Livewire/MQRForm.php`
- `app/Http/Controllers/MQRReportController.php`
- eSPP `integrasi/class/MqrClient.php`
- `.env.example` (`MQR_MAPPING_*`)
