# Laporan Info — SIT

Live SIT box: host `eSPP2F1`, IP `10.29.53.228`, app `/var/www/myspp_integration`, `.env` last written **7 Aug 2026**. Apache vhost `spp_integration.conf`, PHP **8.3.22**. Use the **www** `.env`, not `/home/sppadmin/myspp_integration/.env`.

Staging values are not on this host. `myspp-stg.spp.gov.my.conf` is a MySPP vhost on the same server, not a second integration deploy.

---

## Hub (shared)

Hub rows below are the same for every endpoint. Only **URL / Endpoint** and **Versi API / service** change per API.

| Medan | SIT |
|-------|-----|
| Application Server | Ubuntu 24.04, Apache, `10.29.53.228` (`eSPP2F1`) |
| APP_NAME / APP_ENV | `Integration` / `local` (`APP_DEBUG=false`) |
| APP_URL | `http://localhost` (not set to the public/SIT URL) |
| Database | MySQL `integrasi_dev` @ `10.29.53.234:3306` |
| Middleware | Laravel Sanctum (no extra gateway in `.env`) |
| Network | `10.29.53.228` |
| Versi aplikasi | Laravel 11.45.1, PHP 8.3.22 |
| Tarikh deployment | `.env` 7 Ogos 2026 |

Extra DBs on this host (not per-API unless noted):

| Connection | Host | Database |
|------------|------|----------|
| iSCAF (`iscaf_db`) | `10.29.50.225:3306` | `iscaf_test` |
| MQR mapping | `10.29.53.234` | `spp_ref` (schema empty) |

---

## 1. JPN MyIdentity

### Hub

| Medan | SIT |
|-------|-----|
| URL / Endpoint | `http://10.29.53.228/api/jpn/semak` |
| Application Server | Ubuntu 24.04, Apache, `10.29.53.228` (`eSPP2F1`) |
| APP_NAME / APP_ENV | `Integration` / `local` (`APP_DEBUG=false`) |
| APP_URL | `http://localhost` (not set to the public/SIT URL) |
| Database | MySQL `integrasi_dev` @ `10.29.53.234:3306` |
| Middleware | Laravel Sanctum (no extra gateway in `.env`) |
| Network | `10.29.53.228` |
| Versi aplikasi | Laravel 11.45.1, PHP 8.3.22 |
| Versi API / service | `GET /api/jpn/semak` |
| Tarikh deployment | `.env` 7 Ogos 2026 |

### Agency (JPN)

| Medan | SIT |
|-------|-----|
| URL / Endpoint | `http://10.29.25.144/myspp/module/jpn_api.php` (`jpn_url`) |
| Application Server | `10.29.25.144` (MySPP JPN module) |
| Database | Tiada DB JPN di sisi hub |
| Middleware / API Gateway | Header `X-API-KEY` (`jpn_key`) |
| Network | Hub `10.29.53.228` → `10.29.25.144` (rangkaian dalaman SPP) |
| Versi API / service | `GET {jpn_url}?ic_number=` |
| Tarikh deployment | **Perlu diisi** (agensi) |

---

## 2. KPM ePenempatan / eOperasi

### Hub

| Medan | SIT |
|-------|-----|
| URL / Endpoint | `http://10.29.53.228/api/kpm` |
| Application Server | Ubuntu 24.04, Apache, `10.29.53.228` (`eSPP2F1`) |
| APP_NAME / APP_ENV | `Integration` / `local` (`APP_DEBUG=false`) |
| APP_URL | `http://localhost` (not set to the public/SIT URL) |
| Database | MySQL `integrasi_dev` @ `10.29.53.234:3306` |
| Middleware | Laravel Sanctum (no extra gateway in `.env`) |
| Network | `10.29.53.228` |
| Versi aplikasi | Laravel 11.45.1, PHP 8.3.22 |
| Versi API / service | `GET /api/kpm` |
| Tarikh deployment | `.env` 7 Ogos 2026 |

### Agency (KPM)

| Medan | SIT |
|-------|-----|
| URL / Endpoint | `https://integrasi.moe.gov.my/Guru-prod/sppguru` (`kpm_url`) |
| Application Server | KPM / MOE (`integrasi.moe.gov.my`) |
| Database | Tiada DB KPM di sisi hub |
| Middleware / API Gateway | Header `APIKEY` (`kpm_key`) |
| Network | Hub `10.29.53.228` → HTTPS `integrasi.moe.gov.my` |
| Versi API / service | `GET {kpm_url}?nric=` |
| Tarikh deployment | **Perlu diisi** (agensi) |

---

## 3. KPT eSIS

### Hub

| Medan | SIT |
|-------|-----|
| URL / Endpoint | `http://10.29.53.228/api/kpt/esis` |
| Application Server | Ubuntu 24.04, Apache, `10.29.53.228` (`eSPP2F1`) |
| APP_NAME / APP_ENV | `Integration` / `local` (`APP_DEBUG=false`) |
| APP_URL | `http://localhost` (not set to the public/SIT URL) |
| Database | MySQL `integrasi_dev` @ `10.29.53.234:3306` |
| Middleware | Laravel Sanctum (no extra gateway in `.env`) |
| Network | `10.29.53.228` |
| Versi aplikasi | Laravel 11.45.1, PHP 8.3.22 |
| Versi API / service | `GET /api/kpt/esis` |
| Tarikh deployment | `.env` 7 Ogos 2026 |

### Agency (KPT eSIS)

| Medan | SIT |
|-------|-----|
| URL / Endpoint | `https://api.mypolycc.edu.my` (`kpt_esis_url`) |
| Token | `POST https://api.mypolycc.edu.my/consumer/login` |
| Data | `GET https://api.mypolycc.edu.my/esis/spp/{no_kp}` |
| Application Server | MyPolyCC / KPT (`api.mypolycc.edu.my`) |
| Database | Tiada DB eSIS di sisi hub |
| Middleware / API Gateway | Username/password → Bearer `token` |
| Network | Hub `10.29.53.228` → HTTPS `api.mypolycc.edu.my` |
| Versi API / service | `/consumer/login` + `/esis/spp/{no_kp}` |
| Tarikh deployment | **Perlu diisi** (agensi) |

---

## 4. KPT SKPG

### Hub

| Medan | SIT |
|-------|-----|
| URL / Endpoint | `http://10.29.53.228/api/kpt/skpg` |
| Application Server | Ubuntu 24.04, Apache, `10.29.53.228` (`eSPP2F1`) |
| APP_NAME / APP_ENV | `Integration` / `local` (`APP_DEBUG=false`) |
| APP_URL | `http://localhost` (not set to the public/SIT URL) |
| Database | MySQL `integrasi_dev` @ `10.29.53.234:3306` |
| Middleware | Laravel Sanctum (no extra gateway in `.env`) |
| Network | `10.29.53.228` |
| Versi aplikasi | Laravel 11.45.1, PHP 8.3.22 |
| Versi API / service | `GET /api/kpt/skpg` |
| Tarikh deployment | `.env` 7 Ogos 2026 |

### Agency (KPT SKPG)

| Medan | SIT |
|-------|-----|
| URL / Endpoint | `https://amgs.mohe.gov.my` (`kpt_skpg_url`) |
| Token | `POST https://amgs.mohe.gov.my/Login` |
| Data | `POST https://amgs.mohe.gov.my/MOHE/Student` |
| Application Server | AMGS MOHE (`amgs.mohe.gov.my`) |
| Database | Tiada DB SKPG di sisi SPP |
| Middleware / API Gateway | Username/password → Bearer `data.auth_Token` |
| Network | Hub `10.29.53.228` → HTTPS `amgs.mohe.gov.my` |
| Versi API / service | `/Login` + `/MOHE/Student` (tiada nombor versi). Filter SPP: `level_course_id` `2–7` |
| Tarikh deployment | **Perlu diisi** (agensi) |

---

## 5. LPM SVK SPM

### Hub

| Medan | SIT |
|-------|-----|
| URL / Endpoint | `http://10.29.53.228/api/lpm/svkspm` |
| Application Server | Ubuntu 24.04, Apache, `10.29.53.228` (`eSPP2F1`) |
| APP_NAME / APP_ENV | `Integration` / `local` (`APP_DEBUG=false`) |
| APP_URL | `http://localhost` (not set to the public/SIT URL) |
| Database | MySQL `integrasi_dev` @ `10.29.53.234:3306` |
| Middleware | Laravel Sanctum (no extra gateway in `.env`) |
| Network | `10.29.53.228` |
| Versi aplikasi | Laravel 11.45.1, PHP 8.3.22 |
| Versi API / service | `POST /api/lpm/svkspm` |
| Tarikh deployment | `.env` 7 Ogos 2026 |

### Agency (LPM SVK SPM)

| Medan | SIT |
|-------|-----|
| URL / Endpoint | `https://svk.moe.gov.my` (`lpm_svk_url`) |
| Token | `POST https://svk.moe.gov.my/api/auth/login` |
| Data | `POST https://svk.moe.gov.my/api/verification/spm` |
| Application Server | LPM / MOE (`svk.moe.gov.my`) |
| Database | Tiada DB LPM di sisi hub |
| Middleware / API Gateway | Email/password → Bearer `user._token` |
| Network | Hub `10.29.53.228` → HTTPS `svk.moe.gov.my` |
| Versi API / service | `/api/auth/login` + `/api/verification/spm` |
| Tarikh deployment | **Perlu diisi** (agensi) |

---

## 6. LPM SPM Ulangan

### Hub

| Medan | SIT |
|-------|-----|
| URL / Endpoint | `http://10.29.53.228/api/lpm/svkspmulangan` |
| Application Server | Ubuntu 24.04, Apache, `10.29.53.228` (`eSPP2F1`) |
| APP_NAME / APP_ENV | `Integration` / `local` (`APP_DEBUG=false`) |
| APP_URL | `http://localhost` (not set to the public/SIT URL) |
| Database | MySQL `integrasi_dev` @ `10.29.53.234:3306` |
| Middleware | Laravel Sanctum (no extra gateway in `.env`) |
| Network | `10.29.53.228` |
| Versi aplikasi | Laravel 11.45.1, PHP 8.3.22 |
| Versi API / service | `POST /api/lpm/svkspmulangan` |
| Tarikh deployment | `.env` 7 Ogos 2026 |

### Agency (LPM SPM Ulangan)

| Medan | SIT |
|-------|-----|
| URL / Endpoint | `https://svk.moe.gov.my` (`lpm_svk_url`) |
| Token | `POST https://svk.moe.gov.my/api/auth/login` |
| Data | `POST https://svk.moe.gov.my/api/verification/spmu` |
| Application Server | LPM / MOE (`svk.moe.gov.my`) |
| Database | Tiada DB LPM di sisi hub |
| Middleware / API Gateway | Email/password → Bearer `user._token` |
| Network | Hub `10.29.53.228` → HTTPS `svk.moe.gov.my` |
| Versi API / service | `/api/auth/login` + `/api/verification/spmu` |
| Tarikh deployment | **Perlu diisi** (agensi) |

---

## 7. LPM APKV / SVM

### Hub

| Medan | SIT |
|-------|-----|
| URL / Endpoint | `http://10.29.53.228/api/lpm/svm` |
| Application Server | Ubuntu 24.04, Apache, `10.29.53.228` (`eSPP2F1`) |
| APP_NAME / APP_ENV | `Integration` / `local` (`APP_DEBUG=false`) |
| APP_URL | `http://localhost` (not set to the public/SIT URL) |
| Database | MySQL `integrasi_dev` @ `10.29.53.234:3306` |
| Middleware | Laravel Sanctum (no extra gateway in `.env`) |
| Network | `10.29.53.228` |
| Versi aplikasi | Laravel 11.45.1, PHP 8.3.22 |
| Versi API / service | `POST /api/lpm/svm` |
| Tarikh deployment | `.env` 7 Ogos 2026 |

### Agency (LPM APKV / SVM)

| Medan | SIT |
|-------|-----|
| URL / Endpoint | `https://apkv.moe.gov.my/api/spp/student/results` (`lpm_apkv_url`) |
| Application Server | LPM / MOE (`apkv.moe.gov.my`) |
| Database | Tiada DB APKV di sisi hub |
| Middleware / API Gateway | Header `Authorization` statik (`lpm_apkv_bearer`) |
| Network | Hub `10.29.53.228` → HTTPS `apkv.moe.gov.my` |
| Versi API / service | `POST` body `noKadPengenalan`, `angkaGiliran` |
| Tarikh deployment | **Perlu diisi** (agensi) |

LPM legacy (tidak di-route): `https://integrasispa.spa.gov.my/lpm-exam` (`lpm_url`).

---

## 8. MyGDX MUET

### Hub

| Medan | SIT |
|-------|-----|
| URL / Endpoint | `http://10.29.53.228/api/mygdx/muet` |
| Application Server | Ubuntu 24.04, Apache, `10.29.53.228` (`eSPP2F1`) |
| APP_NAME / APP_ENV | `Integration` / `local` (`APP_DEBUG=false`) |
| APP_URL | `http://localhost` (not set to the public/SIT URL) |
| Database | MySQL `integrasi_dev` @ `10.29.53.234:3306` |
| Middleware | Laravel Sanctum (no extra gateway in `.env`) |
| Network | `10.29.53.228` |
| Versi aplikasi | Laravel 11.45.1, PHP 8.3.22 |
| Versi API / service | `POST /api/mygdx/muet` |
| Tarikh deployment | `.env` 7 Ogos 2026 |

### Agency (MyGDX MUET)

| Medan | SIT |
|-------|-----|
| URL / Endpoint | `https://spp.mygdx.gov.my` (hardcode; tidak dalam `.env`) |
| Application Server | MyGDX Security Server (`spp.mygdx.gov.my`) |
| Database | Tiada DB MyGDX di sisi hub |
| Middleware / API Gateway | X-Road 4.0 + client cert `espp.crt` / `espp.key`. Subsystem `ESPP_STG` |
| Network | Hub `10.29.53.228` → HTTPS `spp.mygdx.gov.my` |
| Versi API / service | SOAP v1, service `MPM-SSKSM-R-MPMFikMUET` |
| Tarikh deployment | **Perlu diisi** (agensi) |

---

## 9. MyGDX STPM

### Hub

| Medan | SIT |
|-------|-----|
| URL / Endpoint | `http://10.29.53.228/api/mygdx/stpm` |
| Application Server | Ubuntu 24.04, Apache, `10.29.53.228` (`eSPP2F1`) |
| APP_NAME / APP_ENV | `Integration` / `local` (`APP_DEBUG=false`) |
| APP_URL | `http://localhost` (not set to the public/SIT URL) |
| Database | MySQL `integrasi_dev` @ `10.29.53.234:3306` |
| Middleware | Laravel Sanctum (no extra gateway in `.env`) |
| Network | `10.29.53.228` |
| Versi aplikasi | Laravel 11.45.1, PHP 8.3.22 |
| Versi API / service | `POST /api/mygdx/stpm` |
| Tarikh deployment | `.env` 7 Ogos 2026 |

### Agency (MyGDX STPM)

| Medan | SIT |
|-------|-----|
| URL / Endpoint | `https://spp.mygdx.gov.my` (hardcode; tidak dalam `.env`) |
| Application Server | MyGDX Security Server (`spp.mygdx.gov.my`) |
| Database | Tiada DB MyGDX di sisi hub |
| Middleware / API Gateway | X-Road 4.0 + client cert `espp.crt` / `espp.key`. Subsystem `ESPP_STG` |
| Network | Hub `10.29.53.228` → HTTPS `spp.mygdx.gov.my` |
| Versi API / service | SOAP v1, service `MPM-SSKSM-R-MPMFikSTPM` |
| Tarikh deployment | **Perlu diisi** (agensi) |

---

## 10. iSCAF

### Hub

| Medan | SIT |
|-------|-----|
| URL / Endpoint | `http://10.29.53.228/api/iscaf/{status,daftar,informasi,kemas-kini}` |
| Application Server | Ubuntu 24.04, Apache, `10.29.53.228` (`eSPP2F1`) |
| APP_NAME / APP_ENV | `Integration` / `local` (`APP_DEBUG=false`) |
| APP_URL | `http://localhost` (not set to the public/SIT URL) |
| Database | MySQL `integrasi_dev` @ `10.29.53.234:3306` |
| Middleware | Laravel Sanctum (no extra gateway in `.env`) |
| Network | `10.29.53.228` |
| Versi aplikasi | Laravel 11.45.1, PHP 8.3.22 |
| Versi API / service | `POST /api/iscaf/*` |
| Tarikh deployment | `.env` 7 Ogos 2026 |

### Agency (iSCAF)

| Medan | SIT |
|-------|-----|
| URL / Endpoint | `http://10.29.59.123/api/v1` (`ISCAF_URL`) |
| Status | `GET` `/status/{ic_no}/{cawangan}` (`ISCAF_STATUS_METHOD=GET`) |
| Daftar | `POST /daftar` |
| Informasi | `GET /informasi/{ic_no}/{cawangan}` |
| Kemas kini | `POST /kemas-kini` |
| Application Server | `10.29.59.123` |
| Database | Connection wujud: `iscaf_test` @ `10.29.50.225:3306`. Service semasa tidak query DB ini |
| Middleware / API Gateway | Header `X-Api-Key` (`ISCAF_API_KEY`) |
| Network | Hub `10.29.53.228` → `10.29.59.123` (rangkaian dalaman SPP) |
| Versi API / service | **v1** |
| Tarikh deployment | **Perlu diisi** (agensi) |

---

## 11. MQR

### Hub

| Medan | SIT |
|-------|-----|
| URL / Endpoint | `http://10.29.53.228/integrasi/mqr` (UI web; tiada `/api` awam) |
| Application Server | Ubuntu 24.04, Apache, `10.29.53.228` (`eSPP2F1`) |
| APP_NAME / APP_ENV | `Integration` / `local` (`APP_DEBUG=false`) |
| APP_URL | `http://localhost` (not set to the public/SIT URL) |
| Database | MySQL `integrasi_dev` @ `10.29.53.234:3306` (`mqr_data`). Padanan: `spp_ref` @ `10.29.53.234` |
| Middleware | Laravel Sanctum session (web) |
| Network | `10.29.53.228` |
| Versi aplikasi | Laravel 11.45.1, PHP 8.3.22 |
| Versi API / service | UI `/integrasi/mqr` |
| Tarikh deployment | `.env` 7 Ogos 2026 |

### Agency (MQR)

| Medan | SIT |
|-------|-----|
| URL / Endpoint | `http://10.29.216.151/api/SPP/request-MQR` (`MQR_URL`) |
| Application Server | `10.29.216.151` |
| Database | Tiada DB MQR di sisi agensi yang dikonfigurasi. Hub tulis snapshot ke `mqr_data`; padanan ke `spp_ref` |
| Middleware / API Gateway | HTTP GET, tiada auth dalam kod |
| Network | Hub `10.29.53.228` → `10.29.216.151` (rangkaian dalaman SPP) |
| Versi API / service | `GET /api/SPP/request-MQR` (payload `dataMQR`) |
| Tarikh deployment | **Perlu diisi** (agensi) |
