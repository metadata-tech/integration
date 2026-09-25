# JPN MyIdentity

Semakan maklumat individu berdasarkan nombor kad pengenalan.

| Medan | Nilai |
|-------|--------|
| Kod katalog | `jpn` / `JPN` |
| Service | `App\Services\JPNService` |
| Inbound hub | `GET /api/jpn/semak?no_kp=` |
| Outbound | `GET {jpn_url}?ic_number=` |

---

## Development / SIT Environment

| Medan | Nilai |
|-------|--------|
| URL / Endpoint (hub) | `http://10.29.53.228/api/jpn/semak` |
| URL / Endpoint (agensi) | **Perlu diisi** — `.env` `jpn_url` pada pelayan SIT |
| Application Server | Hub: `10.29.53.228`. Agensi JPN: **Perlu diisi** |
| Database | Tiada pangkalan JPN di sisi SPP. Log: `api_logs` pada `myspp_integration` |
| Middleware / API Gateway | Hub: Sanctum. Agensi: header `X-API-KEY` (`jpn_key`) |
| Network | Hub dalam `10.29.x.x`. Laluan ke JPN: **Perlu diisi** (VPN / leased line?) |
| Versi aplikasi | Hub Laravel 11.45.1 |
| Versi API / service | **Perlu diisi** (agensi) |
| Tarikh deployment | **Perlu diisi** |

---

## Staging

| Medan | Nilai |
|-------|--------|
| URL / Endpoint (hub) | `{integrasi.spp.gov.my atau Constant}/jpn/semak` |
| URL / Endpoint (agensi) | **Perlu diisi** — `jpn_url` pada pelayan staging |
| Application Server | **Perlu diisi** |
| Database | **Perlu diisi** (jika berbeza dari SIT) |
| Middleware / API Gateway | Sama (`X-API-KEY`) |
| Network | **Perlu diisi** |
| Versi aplikasi | **Perlu diisi** |
| Versi API / service | **Perlu diisi** |
| Tarikh deployment | **Perlu diisi** |

---

## Kontrak panggilan

**Inbound (eSPP / MySPP → hub)**

- Method: `GET`
- Query: `no_kp` (wajib)
- Auth: `Authorization: Bearer {sanctum}`

**Outbound (hub → JPN)**

- Method: `GET`
- Query: `ic_number`
- Header: `X-API-KEY`
- SSL verify: dimatikan (`verify => false`)

**Respons yang dijangka eSPP** (`integrasi/integrasi_jpn.php`): `ReplyIndicator`, `RecordStatus`, `ICNumber`, `Name`, `DateOfBirth`, `Gender`, `DateOfDeath`, `Message`.

---

## Nota

- MySPP ada laluan JPN SOAP berasingan: `https://10.29.25.144/myspp/wsdl/crsservice.wsdl` dan `http://10.29.58.50/myspp/wsdl/crsservice.wsdl`. Itu **bukan** API hub ini.
- Semakan IC legacy: `http://10.29.25.144:8080/verification/verify.asm`.
- eSPP memanggil hub: `EsisClient::getApiBaseUrl() . '/jpn/semak'`.

## Rujukan kod

- `app/Services/JPNService.php`
- `app/Http/Controllers/Api/JpnController.php`
- `app/Livewire/JPNForm.php`
- eSPP `integrasi/integrasi_jpn.php`
