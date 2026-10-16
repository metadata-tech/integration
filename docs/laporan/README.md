# Laporan Integrasi API SPP

Ringkasan maklumat teknikal untuk setiap API dalam repositori `htdocs/integration` (hub Laravel **SPP Integration**).

Dokumen ini disusun untuk laporan persekitaran (Development / SIT / Staging). Sumber: kod aplikasi, `config/services.php`, `config/integrations.php`, `routes/api.php`, serta rujukan pengguna di eSPP dan MySPP.

**Tarikh ekstrak:** 23 Ogos 2026  
**Kod terkini dalam repo:** commit `335a9f6` (16 Ogos 2026)

Jadual SIT untuk setiap endpoint: [Laporan-Info.md](Laporan-Info.md).

---

## Cara baca

Terdapat dua lapisan:

1. **Hub SPP Integration** — aplikasi Laravel yang didedahkan kepada eSPP / MySPP (`/api/...`, auth Sanctum).
2. **API agensi** — perkhidmatan luar yang dipanggil oleh hub (JPN, KPM, KPT, LPM, MyGDX, iSCAF, MQR).

Medan bertanda **Perlu diisi** tidak wujud dalam repositori (biasanya hanya pada `.env` pelayan atau dokumen agensi). Jangan campur token / kata laluan ke dalam fail ini.

---

## Senarai API

| # | Agensi / Sistem | Fail | Protokol | Laluan inbound (hub) | Status katalog UI |
|---|-----------------|------|----------|----------------------|-------------------|
| 0 | Hub SPP Integration | [00-hub-spp-integration.md](00-hub-spp-integration.md) | REST JSON | `/api/*` | Aplikasi induk |
| 1 | JPN MyIdentity | [01-jpn-myidentity.md](01-jpn-myidentity.md) | HTTP GET | `GET /api/jpn/semak` | Ada |
| 2 | KPM ePenempatan / eOperasi | [02-kpm-epenempatan.md](02-kpm-epenempatan.md) | HTTP GET | `GET /api/kpm` | Ada |
| 3 | KPT eSIS | [03-kpt-esis.md](03-kpt-esis.md) | REST + Bearer | `GET /api/kpt/esis` | Ada |
| 4 | KPT SKPG | [04-kpt-skpg.md](04-kpt-skpg.md) | REST + Bearer | `GET /api/kpt/skpg` | Ada |
| 5 | LPM SVK SPM | [05-lpm-svk-spm.md](05-lpm-svk-spm.md) | REST + Bearer | `POST /api/lpm/svkspm` | Ada |
| 6 | LPM SPM Ulangan | [06-lpm-spm-ulangan.md](06-lpm-spm-ulangan.md) | REST + Bearer | `POST /api/lpm/svkspmulangan` | Ada |
| 7 | LPM APKV / SVM | [07-lpm-apkv-svm.md](07-lpm-apkv-svm.md) | HTTP POST + Bearer | `POST /api/lpm/svm` | Ada |
| 8 | MyGDX MUET | [08-mygdx-muet.md](08-mygdx-muet.md) | SOAP / X-Road 4.0 | `POST /api/mygdx/muet` | Ada |
| 9 | MyGDX STPM | [09-mygdx-stpm.md](09-mygdx-stpm.md) | SOAP / X-Road 4.0 | `POST /api/mygdx/stpm` | Ada |
| 10 | iSCAF | [10-iscaf.md](10-iscaf.md) | REST + X-Api-Key | `POST /api/iscaf/*` | Ada |
| 11 | MQR | [11-mqr.md](11-mqr.md) | HTTP GET | Tiada laluan `/api` awam (sync dari UI) | Ada |
| 12 | MySPP dokumen | [00-hub-spp-integration.md](00-hub-spp-integration.md) | HTTP GET binary | `GET /api/myspp/dokumen` | Tiada |

---

## Persekitaran hub (ringkas)

| Medan | Development / SIT | Staging | Catatan |
|-------|-------------------|---------|---------|
| URL / Endpoint | `http://10.29.53.228/api` | `http://integrasi.spp.gov.my/api` (fallback eSPP) | Local: `http://localhost` |
| Application Server | `10.29.53.228` | **Perlu diisi** (hostname `integrasi.spp.gov.my`) | MySPP hardcode IP SIT |
| Database | `myspp_integration` (contoh local `.env`) | **Perlu diisi** | MySQL |
| Middleware / API Gateway | Laravel Sanctum (`auth:sanctum`) | Sama | Tiada API Gateway berasingan dalam kod |
| Network | Rangkaian dalaman SPP `10.29.x.x` | **Perlu diisi** (awam / VPN?) | SSL verify dimatikan pada panggilan keluar |
| Versi aplikasi | Laravel 11.45.1, PHP ^8.2, Livewire 3, Sanctum 4 | Sama (jika deploy dari repo ini) | |
| Versi API / service | REST `/api` (tiada nombor versi path) | Sama | |
| Tarikh deployment | **Perlu diisi** | **Perlu diisi** | Repo bermula 26 Mei 2025; commit terakhir 16 Ogos 2026 |

---

## Pengguna inbound

| Sistem | Cara sambung | Sumber |
|--------|--------------|--------|
| eSPP | `Constant::$integrasiApiBaseUrl` + `Authorization: Bearer` Sanctum; fallback `http://integrasi.spp.gov.my/api` | `integrasi/class/EsisClient.php`, `IscafClient.php`, `integrasi_proxy.php` |
| MySPP | Hardcode `http://10.29.53.228/api/...` + Bearer token | `_mapps/akademik/sql_akademik.php` |
| UI hub | Livewire di `/integrasi/{slug}` (login web) | `config/integrations.php` |

---

## Medan laporan (template setiap API)

Setiap fail agensi mengikut struktur ini:

- Development / SIT Environment
- Staging
- URL / Endpoint
- Application Server
- Database
- Middleware / API Gateway (jika ada)
- Network
- Versi aplikasi
- Versi API / service
- Tarikh deployment

Ditambah: autentikasi, parameter, pengguna eSPP/MySPP, dan rujukan fail kod.

---

## Peringatan

- URL agensi JPN, KPM, KPT, LPM **tidak** committed; hanya nama pembolehubah `.env`.
- MyGDX menggunakan subsystem default `ESPP_STG` — konfigurasi semasa ialah **staging**.
- iSCAF ada dua URL: SIT dalaman `http://10.29.59.123/api/v1` dan production `https://iscaf.spp.gov.my/api/v1`.
- MQR dipanggil terus ke `http://10.29.216.151/api/SPP/request-MQR` (juga dari eSPP `MqrClient`).
- `LPMService` (lama, `x-api-key`) masih wujud tetapi **tidak** di-route; SVK menggantikannya.
- JPN SOAP MyIdentity di MySPP (`wsdl/crsservice.wsdl`) ialah laluan berasingan, bukan melalui hub ini.
