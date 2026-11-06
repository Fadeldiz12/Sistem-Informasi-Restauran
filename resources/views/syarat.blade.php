{{-- Draf syarat & ketentuan. Isi silakan disesuaikan pemilik restoran. --}}
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Syarat &amp; Ketentuan · {{ config('resto.nama') }}</title>
@include('partials.favicon')
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; margin: 0; }
  body { background: #F8F5E6; color: #282828; font: 400 15px/1.7 'Open Sans', sans-serif; padding: 28px 16px 48px; }
  main { max-width: 720px; margin: 0 auto; }
  header { display: flex; gap: 14px; align-items: center; margin-bottom: 22px; color: #97173A; }
  header img { width: 52px; height: 52px; border-radius: 50%; }
  h1 { font: 700 26px Poppins, sans-serif; color: #97173A; }
  h2 { font: 600 17px Poppins, sans-serif; color: #97173A; margin: 22px 0 6px; }
  ol, ul { padding-left: 22px; }
  li { margin: 4px 0; }
  .note { margin-top: 26px; padding: 14px 16px; border-radius: 14px; background: #EFE7CF; font-size: 13.5px; }
  a.btn { display: inline-block; margin-top: 24px; padding: 12px 26px; border-radius: 999px; background: #97173A; color: #F8F5E6; font: 600 15px Poppins, sans-serif; text-decoration: none; }
</style>
</head>
<body>
<main>
  <header><img src="{{ asset('images/logo-192.png') }}" alt="Logo {{ config('resto.nama') }}"><div><h1>Syarat &amp; Ketentuan</h1><div style="font-size:13px;opacity:.8">{{ config('resto.nama') }} · {{ config('resto.alamat') }}</div></div></header>

  <p>Dengan membuat akun dan memesan di aplikasi {{ config('resto.nama') }}, kamu menyetujui ketentuan berikut.</p>

  <h2>1. Akun &amp; data pribadi</h2>
  <ul>
    <li>Data yang kamu isi (nama, nomor HP, email, alamat) dipakai hanya untuk memproses pesanan, menghubungi kamu saat pengantaran, dan mengirim kode verifikasi.</li>
    <li>Data tidak dijual atau dibagikan ke pihak lain di luar keperluan pesanan.</li>
    <li>Jaga kerahasiaan password. Pesanan yang dibuat dari akunmu dianggap dibuat olehmu.</li>
  </ul>

  <h2>2. Pesanan</h2>
  <ul>
    <li>Pesanan diproses setelah dikonfirmasi kasir, atau setelah pembayaran Virtual Account / QRIS lunas.</li>
    <li>Restoran dapat menolak pesanan (misalnya menu habis, alamat di luar jangkauan, atau restoran akan tutup). Alasannya ditampilkan di halaman Lacak Pesanan.</li>
    <li>Pesanan hanya bisa dibuat pada jam operasional.</li>
  </ul>

  <h2>3. Pembayaran</h2>
  <ul>
    <li>Pembayaran tunai dilakukan di kasir (Dine In / Take Away) atau kepada kurir saat pesanan tiba (Delivery).</li>
    <li>Total yang dibayar sudah termasuk pajak, biaya layanan, dan ongkos kirim (untuk Delivery).</li>
  </ul>

  <h2>4. Pengantaran &amp; ongkos kirim</h2>
  <ul>
    <li>Ongkos kirim mengikuti zona pengiriman sesuai jarak alamat dari restoran. Pilih zona yang sesuai; bila zona yang dipilih tidak sesuai, restoran dapat menghubungi kamu untuk penyesuaian.</li>
    <li>Pastikan alamat dan nomor HP benar agar kurir bisa menghubungi kamu.</li>
  </ul>

  <h2>5. Pembatalan</h2>
  <ul>
    <li>Pesanan yang belum dikonfirmasi dapat ditolak restoran. Pesanan yang sudah dimasak tidak dapat dibatalkan oleh pelanggan.</li>
    <li>Bila sudah membayar lalu pesanan dibatalkan restoran, hubungi restoran di {{ config('resto.telepon') }} untuk pengembalian dana.</li>
  </ul>

  <div class="note">Pertanyaan tentang ketentuan ini? Hubungi kami di {{ config('resto.telepon') }}.</div>
  <a class="btn" href="{{ url()->previous() !== url()->current() ? url()->previous() : route('registrasi') }}">Kembali</a>
</main>
</body>
</html>
