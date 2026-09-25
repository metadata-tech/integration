# iSCAF

Semakan status, pendaftaran dan kemas kini fail anggota (HQ / Sabah / Sarawak).

| Medan | Nilai |
|-------|--------|
| Kod katalog | `iscaf` / `ISC` |
| Service | `App\Services\IscafService` |
| Inbound hub | `POST /api/iscaf/{status,daftar,informasi,kemas-kini}` |
| Outbound | REST `{ISCAF_URL}/...` + header `X-Api-Key` |

---

## Development / SIT Environment

| Medan | Nilai |
|-------|--------|
| URL / Endpoint (hub) | `http://10.29.53.228/api/iscaf/...` |
| URL / Endpoint (agensi) | `http://10.29.59.123/api/v1` (default `config/services.php` jika `ISCAF_URL` kosong) |
| Application Server | Hub: `10.29.53.228`. iSCAF SIT: `10.29.59.123` |
| Database | iSCAF: sistem luar (hub **tidak** query `iscaf_db`). Connection `iscaf_db` wujud tetapi tidak digunakan oleh service semasa |
| Middleware / API Gateway | Hub: Sanctum. Agensi: `X-Api-Key` (`ISCAF_API_KEY`). Tiada gateway berasingan dalam kod |
| Network | Kedua-dua host dalam `10.29.x.x` (rangkaian dalaman SPP) |
| Versi aplikasi | Hub Laravel 11.45.1 |
| Versi API / service | **v1** (`/api/v1`) |
| Tarikh deployment | **Perlu diisi** |

Nota kod: *“Prod doc says POST, UAT currently uses GET”* untuk `/status`. Dikawal oleh `ISCAF_STATUS_METHOD` (default `POST`).

---

## Staging / Production

| Medan | Nilai |
|-------|--------|
| URL / Endpoint (hub) | `{base hub}/iscaf/...` — fallback eSPP `http://integrasi.spp.gov.my/api` |
| URL / Endpoint (agensi) | `https://iscaf.spp.gov.my/api/v1` (`.env.example` dan agent doc) |
| Application Server | Hostname `iscaf.spp.gov.my` — **spesifikasi pelayan perlu diisi** |
| Database | **Perlu diisi** (di sisi iSCAF) |
| Middleware / API Gateway | `X-Api-Key` |
| Network | HTTPS awam / dalaman — **Perlu diisi** |
| Versi aplikasi | **Perlu diisi** |
| Versi API / service | v1 |
| Tarikh deployment | **Perlu diisi** |

`.env` local semasa juga set `ISCAF_URL=https://iscaf.spp.gov.my/api/v1` (tanpa key).

---

## Kontrak panggilan

**Inbound (semua POST, Sanctum)**

| Path | Parameter |
|------|-----------|
| `/api/iscaf/status` | `no_kp`, `cawangan` (`HQ`/`S`/`Q`), `nt` optional `0\|1` |
| `/api/iscaf/daftar` | `mykad_no`, `cawangan`, `no_pemerolehan` optional, `data[]` (`nama`, `ic_no`, `position_id`, `volume`, `nt`) |
| `/api/iscaf/informasi` | `no_kp`, `cawangan`, `nt` optional |
| `/api/iscaf/kemas-kini` | `mykad_no`, `cawangan`, `ic_no`, `aktiviti` (`1` masuk / `2` keluar), `nt` optional |

**Outbound**

| Method | Path |
|--------|------|
| GET atau POST (`ISCAF_STATUS_METHOD`) | `/status/{ic_no}/{cawangan}/{nt?}` |
| POST JSON | `/daftar` |
| GET | `/informasi/{ic_no}/{cawangan}/{nt?}` |
| POST JSON | `/kemas-kini` |

Cawangan: `HQ` Ibu Pejabat, `S` Sabah, `Q` Sarawak (`branch_id` 1/2/3).

eSPP: `IscafClient` + rate limit / circuit breaker (`MpgIscafConfig`). Proxy: `integrasi_proxy.php`.

## Rujukan kod

- `app/Services/IscafService.php`
- `app/Http/Controllers/Api/IscafController.php`
- `app/Livewire/IscafForm.php`, `IscafSearch.php`
- `.github/agents/iscaf.agent.md`
- eSPP `integrasi/class/IscafClient.php`
