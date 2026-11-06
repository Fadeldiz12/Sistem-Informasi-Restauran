# Rencana Perbaikan QA — Resto Flow

Sumber: 4 laporan QA (A1 Akun, A2 Pelanggan, A3 Kasir & Dapur, A4 Kurir/Admin/Owner).
Total **91 temuan** (High 8 · Medium 37 · Low 46). Beberapa temuan muncul di lebih dari satu laporan, jadi digabung (ditandai `=`).

Prinsip urutan: **uang & data rusak dulu → alur pesanan macet → keamanan/akun → pengalaman pelanggan → UX staf → bahasa/istilah**.
Satu fase = satu (atau beberapa) commit. Setiap fase selesai → saya lapor & tanya sebelum lanjut.

---

## Fase 0 — Persiapan
- [x] Laravel Boost (sesuai CLAUDE.md)
- [x] Jalankan test yang ada sebagai baseline (102 test lulus)
- [x] Test dijalankan di SQLite **dan** MariaDB 10.11 (laporan diuji di MySQL) — keduanya lulus

## Fase 1 — Uang & integritas data (13 temuan)
- [ ] **A3-#1 (High)** Kasir bisa melunasi pesanan Delivery COD / yang belum dikonfirmasi → uang hilang
- [ ] A3-#13 `=` daftar "Belum Lunas" ikut memuat pesanan Menunggu Konfirmasi (scope `belumLunas` yang sama)
- [ ] **A4-#1 (High)** Hapus/ubah role kurir yang masih bawa uang COD → setoran hilang
- [ ] **A3-#2 (High)** Hapus bahan baku yang dipakai resep → resep & 31 riwayat mutasi ikut terhapus (cascade)
- [ ] A3-#11 Ubah satuan bahan (kg→gram) tanpa konversi → stok salah 1000×
- [ ] A3-#5 Struk: uang diterima tidak disimpan, `?tunai=` bisa dimanipulasi, tidak bisa cetak ulang
- [ ] A3-#19 Kembalian sangat besar (salah ketik nol) tanpa konfirmasi
- [ ] A3-#23 `=` A4-#17 Harga besar → error 500 overflow kolom; "12,5" jadi 125; angka maksimal tanpa pemisah ribuan
- [ ] A4-#3 Harga menu Rp 0 lolos
- [ ] A4-#4 Zona: radius tumpang tindih & tarif Rp 0 lolos
- [ ] A4-#16 Nama menu duplikat lolos
- [ ] A3-#15 Bahan baku duplikat beda huruf, satuan bebas, tanggal mutasi 2001

## Fase 2 — Alur pesanan yang macet / salah (9 temuan)
- [ ] **A2-#1 (High)** Pesanan tanpa metode bayar: pelanggan lihat "Menunggu Konfirmasi", kasir tidak melihatnya → menggantung selamanya
- [ ] **A3-#3 (High)** Kasir tidak bisa membatalkan/mengoreksi pesanan (tiket dapur ikut batal)
- [ ] A3-#4 `=` A2-#21 Validasi nomor meja di server (rentang 01–16, meja sedang dipakai)
- [ ] A2-#3 Timeline: "Dimasak" sudah dicentang padahal masih dimasak
- [ ] A3-#6 Tolak pesanan tanpa alasan; pesan "sudah diproses" untuk pesanan yang ditolak
- [ ] A3-#7 Dashboard kasir tidak menampilkan pesan error
- [ ] A3-#8 Mulai masak saat stok kurang: toast sukses + banner merah bertentangan
- [ ] A4-#9 Jam operasional lewat tengah malam (16:00–02:00) tidak bisa diatur

## Fase 3 — Akun, login & keamanan (21 temuan)
- [ ] **A1-#21 (High)** Email OTP gagal terkirim → halaman debug 500, akun "yatim" tak bisa daftar ulang
- [ ] **A1-#3 (High)** `=` A4-#5 Halaman 429 bawaan Inggris, tanpa waktu tunggu, isian hilang
- [ ] A1-#4 Throttle login per IP untuk semua akun (login benar pun diblok)
- [ ] A1-#5 Tombol VERIFIKASI OTP macet saat kode < 6 digit
- [ ] A1-#6 `=` A2-#22 Tombol Back setelah logout menampilkan data (perlu `Cache-Control: no-store`)
- [ ] A1-#20 Mode debug & banner OTP (checklist deploy)
- [ ] A1-#8 `=` A2-#8 Halaman error 404/419/500/503 Indonesia + toast "Failed to fetch"/"Unauthenticated."
- [ ] A1-#14 Setelah login tidak kembali ke halaman tujuan, tanpa pesan "masuk dulu"
- [ ] A1-#19 Tidak ada "Ingat saya"
- [ ] A1-#11 Validasi Nomor HP (`- - -` diterima) & nama
- [ ] A1-#9 Checkbox S&K tercentang otomatis, S&K tak bisa dibaca
- [ ] A1-#10 Registrasi: petunjuk password, error per kolom, "(opsional)"
- [ ] A1-#12 Lupa password untuk email tak terdaftar → pesan menyesatkan
- [ ] A1-#13 Pesan OTP kedaluwarsa (nama tombol beda, tombol terkunci)
- [ ] A1-#15 Buka `/reset-password` langsung → pesan "sesi habis"
- [ ] A4-#2 Keterangan hak akses tidak berubah saat pilih role
- [ ] A4-#8 Turunkan role owner tanpa konfirmasi
- [ ] A4-#19 Password staf lemah (`11111111`) diterima

## Fase 4 — Pengalaman pelanggan (20 temuan)
- [ ] A2-#12 Navigasi HP: menu jauh di bawah, "Lacak Pesanan" tersembunyi, tidak ada bottom nav
- [ ] A2-#2 Lacak Pesanan di HP: detail tertimbun di bawah 30 riwayat
- [ ] A2-#4 Tombol Cari tidak berfungsi
- [ ] A2-#5 Promo/hero statis tidak bisa dipesan, panah slide mati, "Semua promo" `#`
- [ ] A2-#6 Pilihan layanan di beranda tidak terbawa ke checkout; teks Dine In kontradiktif
- [ ] A2-#7 Zona A terpilih otomatis, tidak ada panduan jarak
- [ ] A2-#9 Restoran tutup: tidak ada banner, footer jam hardcode
- [ ] A2-#10 Menu habis hilang diam-diam dari keranjang / beranda
- [ ] A2-#11 Tidak ada halaman profil pelanggan (ubah nama/HP/alamat)
- [ ] A2-#13 Batas qty 50 tanpa pesan, toast "ditambahkan" palsu, tombol − di qty 1
- [ ] A2-#14 Hapus item keranjang tanpa "Batalkan"
- [ ] A2-#15 Back setelah checkout → "Keranjang masih kosong"
- [ ] A2-#16 Teks VA/QRIS padahal nonaktif, label tombol tunai, pesan `MIDTRANS_SERVER_KEY kosong`
- [ ] A2-#17 Tombol "Ubah Pembayaran" mencolok saat pesanan berjalan
- [ ] A2-#19 Chip "Pakai alamat profil" aktif padahal kosong
- [ ] A2-#20 Teks status salah setelah selesai ("diteruskan ke dapur", "menuju alamatmu")
- [ ] A2-#23 Pesan "Restoran tutup" tampil dua kali
- [ ] A2-#24 Kategori di strip atas berbeda dengan chip (Salad hilang)
- [ ] A2-#25 Jam per langkah timeline kosong
- [ ] A2-#26 Link footer `#` mati, nomor telepon bukan `tel:`

## Fase 5 — UX staf: kasir, dapur, kurir, admin, owner (19 temuan)
- [ ] A3-#9 Mulai Masak / Terima Setoran tanpa konfirmasi, undo, loading; setoran tanpa jumlah dihitung
- [ ] A3-#12 HP: pilih item di daftar → balik ke atas, tombol aksi di luar layar
- [ ] A3-#14 Kasir tidak diberi tahu pesanan "Siap" di luar dashboard
- [ ] A3-#16 Pesan error "Items.0.jumlah"
- [ ] A3-#17 Tombol Simpan nonaktif tampak aktif; petunjuk "di sebelah kiri" di HP
- [ ] A3-#18 Qty input kasir tidak bisa diketik, tombol kecil, maksimal 99 diam
- [ ] A3-#20 Koneksi putus saat Simpan → keranjang kasir hilang
- [ ] A3-#21 Nama bahan sangat panjang mendorong kolom aksi keluar
- [ ] A4-#6 Laporan owner di HP: kolom Laba terpotong
- [ ] A4-#10 Toast peringatan kurir pakai ikon ✓
- [ ] A4-#11 Upload bukti: file bukan gambar diterima di pratinjau
- [ ] A4-#12 Nominal tagih tunai tidak jelas termasuk ongkir
- [ ] A4-#13 `=` A1-#16 Logout tanpa konfirmasi (kurir/pelanggan), tanpa pesan "Anda sudah keluar"
- [ ] A4-#15 Tombol ubah/hapus kategori hanya muncul saat hover
- [ ] A4-#20 Label sidebar "Pengeluaran Operasiona" terpotong
- [ ] A4-#21 Singkatan "M" vs "jt", "RUGI −Rp"
- [ ] A4-#22 Pesan "berformat Y-m-d"
- [ ] A4-#24 Status kurir "Diterima" sudah terpilih otomatis

## Fase 6 — Bahasa, istilah & kesan pertama (9 temuan)
- [ ] A1-#18 `=` A2-#18 `=` A3-#10 `=` A3-#22 `=` A4-#7 `=` A4-#14 `=` A4-#23 Glosarium & sapu istilah: FIFO, ROP, Navbar, Logout/Keluar, OTP, Dine In/Take Away, "Selesai" makna ganda, ORD vs T-xxxx
- [ ] A1-#7 Halaman pertama "Selamat Datang Kembali!" tanpa penjelasan aplikasi
- [ ] A1-#17 Target sentuh kecil di login/registrasi

---
Rekap: F1 13 · F2 9 · F3 21 · F4 20 · F5 19 · F6 9 = **91**
