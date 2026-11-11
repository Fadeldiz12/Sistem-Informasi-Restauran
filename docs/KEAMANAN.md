# Keamanan Resto Flow

Ringkasan lapisan keamanan, checklist **OWASP Top 10 (2021)**, dan cara menjalankan **scan OWASP ZAP**.
Semua butir di bawah memiliki tes otomatis (`php artisan test`) kecuali yang ditandai *manual*.

## Lapisan keamanan

| Lapis | Isi | Tempat mengatur |
|---|---|---|
| 1. Akun & sesi | Audit log, kunci akun setelah 10× password salah (+ email Owner), satu akun staf satu perangkat, logout otomatis saat diam | Owner → Log Aktivitas, Pengguna & Role; Admin → Pengaturan (batas diam) |
| 2. Lokasi, perangkat, shift | IP WiFi outlet / GPS + radius, perangkat terdaftar, jadwal shift (sekaligus absensi), kode darurat Owner | Owner → Keamanan Outlet |
| 3. Kriptografi | Password bcrypt; no. HP, alamat & data wajah AES-256 (APP_KEY); signature Midtrans; backup AES-256-GCM | `.env` (APP_KEY, BACKUP_KEY), `php artisan resto:backup` |
| 4. Wajah | Verifikasi 1:1 + liveness acak (face-api.js), 3× gagal = kunci, persetujuan Owner/Admin yang hadir, konfirmasi wajah + password untuk aksi sensitif | Owner → Keamanan Outlet → Verifikasi Wajah |
| 5. Dokumen | Tanda tangan digital (HMAC-SHA256) di struk & laporan PDF + halaman cek keaslian publik | otomatis; QR butuh `composer require bacon/bacon-qr-code` |
| 6. Aplikasi | Security headers + CSP, kepemilikan data, validasi input/upload, harga dihitung server, `.env` tidak bisa diakses | otomatis; lihat Owner → Keamanan Outlet → Pemeriksaan Server |

## Checklist OWASP Top 10 (2021)

| # | Risiko | Kontrol di Resto Flow | Tes |
|---|---|---|---|
| A01 | Broken Access Control | Middleware role per area; pelanggan A tidak bisa membuka pesanan B; tamu hanya lewat tautan terenkripsi; ID di URL terenkripsi (tidak bisa ditebak); akun owner hanya bisa diatur owner; foto bukti kirim privat | `UrlTerenkripsiTest`, `PesanTanpaLoginTest`, `AdminTest`, `OwnerTest`, `KeamananAkunTest` |
| A02 | Cryptographic Failures | bcrypt untuk password & OTP; AES-256 untuk HP/alamat/wajah; HSTS saat HTTPS; cookie sesi `SESSION_SECURE_COOKIE=true` saat HTTPS; backup AES-256-GCM | `EnkripsiDataTest`, `KeamananAplikasiTest` |
| A03 | Injection | Query Eloquent/Query Builder (parameter terikat); Blade `{{ }}` meng-escape output; validasi semua input | seluruh tes form (validasi) |
| A04 | Insecure Design | Total, pajak & ongkir dihitung di server; status lunas Midtrans hanya dari webhook bersignature + nominal cocok; EOD; kode darurat tercatat | `MidtransTest`, `PesananPelangganTest`, `TutupHariTest` |
| A05 | Security Misconfiguration | Security headers + CSP; `APP_DEBUG=false` di produksi; `.htaccess` memblokir `.env`, `storage`, `vendor`, `.git`; daftar Pemeriksaan Server untuk Owner | `KeamananAplikasiTest` (+ *manual*: cek server produksi) |
| A06 | Vulnerable Components | Laravel 13, dependensi lewat Composer; face-api.js disimpan lokal dengan versi tetap | *manual*: `composer audit` berkala |
| A07 | Identification & Authentication Failures | Batas percobaan login, kunci akun, OTP email pelanggan, sesi diganti saat login, satu perangkat, logout otomatis, verifikasi wajah, konfirmasi ulang untuk aksi sensitif | `KeamananAkunTest`, `AksesOutletTest`, `VerifikasiWajahTest`, `AkunTest` |
| A08 | Software & Data Integrity Failures | Signature Midtrans; tanda tangan digital struk & laporan; token CSRF di semua form | `MidtransTest`, `KeamananAplikasiTest` |
| A09 | Security Logging & Monitoring Failures | Log Aktivitas (tidak bisa diubah dari aplikasi): login, gagal, kunci, luar outlet, kode darurat, spoofing, ubah harga, hapus data, lunas, unduh laporan; email Owner saat akun terkunci | `KeamananAkunTest`, `AksesOutletTest` |
| A10 | Server-Side Request Forgery | Server hanya menghubungi Midtrans (alamat tetap dari konfigurasi); unduh QRIS hanya dari URL milik transaksi itu | `MidtransTest` |

## Scan OWASP ZAP (*manual*)

Jalankan saat aplikasi sudah berjalan (mis. `php artisan serve --host=0.0.0.0 --port=8000`). Dengan Docker:

```bash
docker run --rm -t ghcr.io/zaproxy/zaproxy:stable zap-baseline.py -t http://host.docker.internal:8000 -r laporan-zap.html
```

Tanpa Docker: pasang [ZAP Desktop](https://www.zaproxy.org/download/), pilih **Automated Scan**, isi URL aplikasi, lalu jalankan.
Temuan yang sudah diketahui dan diterima:

- **CSP `unsafe-inline`**: skrip & gaya inline masih dipakai di tampilan. Sumber skrip dari luar tetap dibatasi (hanya `unpkg.com` untuk peta).
- **Cookie tanpa `Secure`** saat diuji lewat `http://`: aktif otomatis setelah HTTPS + `SESSION_SECURE_COOKIE=true`.

## Sebelum dipakai sungguhan

1. `APP_ENV=production`, `APP_DEBUG=false`, HTTPS aktif, `SESSION_SECURE_COOKIE=true`.
2. Simpan salinan `APP_KEY` (dan `BACKUP_KEY`) di tempat aman: tanpa kunci itu, no. HP, alamat, data wajah, dan backup tidak bisa dibuka.
3. Jadwalkan `php artisan schedule:run` tiap menit (Task Scheduler Windows / cron) agar backup malam berjalan.
4. Buka Owner → Keamanan Outlet → **Pemeriksaan Server** dan pastikan semua "Aman".

## Batasan yang perlu diketahui

- Website tidak bisa mencegah kamera virtual atau pemalsuan data dari browser sepenuhnya. Itu sebabnya wajah tidak pernah menjadi satu-satunya kunci (selalu bersama password, lokasi outlet, dan perangkat terdaftar).
- Cek tekstur anti-cetakan/layar belum ada (butuh model anti-spoofing di server).
- Cek IP outlet hanya andal bila internet outlet memakai IP statis.
