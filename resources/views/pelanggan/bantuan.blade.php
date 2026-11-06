@extends('layouts.pelanggan')

@section('title', 'Bantuan')

@push('styles')
@include('partials.pelanggan.gaya-dasar')
<style>
  .bantuan { display: grid; gap: clamp(16px, 2vw, 24px); max-width: 860px; }
  .bantuan h2 { margin-bottom: 10px; }
  .bantuan section { scroll-margin-top: 130px; }
  .bantuan details { padding: 12px 0; border-bottom: 1.2px solid var(--m15); }
  .bantuan details:last-child { border-bottom: 0; }
  .bantuan summary { cursor: pointer; font: 600 15px var(--f-title); }
  .bantuan details p, .bantuan li { font-size: 14px; opacity: .85; margin-top: 6px; }
  .bantuan ul { padding-left: 20px; }
  .zrow { display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1.2px solid var(--m15); font-size: 14px; }
  .zrow:last-child { border-bottom: 0; }
</style>
@endpush

@php $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.'); $set = \App\Models\PengaturanSistem::aktif(); @endphp

@section('content')
<main class="wrap">
  <div class="page-head"><div><div class="crumb"><a href="{{ route('pelanggan.beranda') }}">Beranda</a><span>/</span><span>Bantuan</span></div><h1>Bantuan</h1></div></div>
  <div class="bantuan">
    <section class="card" id="faq"><h2>Pertanyaan Umum</h2>
      <details open><summary>Bagaimana cara memesan?</summary><p>Pilih menu di Beranda, buka Keranjang, lalu Checkout. Pilih jenis layanan (Delivery, Take Away, atau Dine In) dan metode pembayaran, lalu tekan Konfirmasi Pesanan.</p></details>
      <details><summary>Kapan pesanan saya dimasak?</summary><p>Pesanan bayar tunai diproses setelah dikonfirmasi kasir. Pesanan Virtual Account / QRIS diproses otomatis setelah pembayaran lunas. Status bisa dipantau di halaman Lacak Pesanan.</p></details>
      <details><summary>Jam berapa restoran buka?</summary><p>Setiap hari pukul {{ $set->jamLabel() }}. Di luar jam itu menu tetap bisa dilihat, tetapi pesanan belum bisa dibuat.</p></details>
      <details><summary>Pesanan saya ditolak, kenapa?</summary><p>Alasan penolakan tampil di halaman Lacak Pesanan (misalnya menu habis atau alamat di luar jangkauan). Bila perlu, hubungi kami di <a href="tel:{{ preg_replace('/\D/', '', config('resto.telepon')) }}" style="text-decoration:underline">{{ config('resto.telepon') }}</a>.</p></details>
      <details><summary>Bagaimana mengubah nomor HP atau alamat?</summary><p>Buka <a href="{{ route('pelanggan.profil') }}" style="text-decoration:underline">Profil saya</a> dari menu akun.</p></details>
    </section>

    <section class="card" id="pembayaran"><h2>Cara Pembayaran</h2>
      <ul>
        <li><b>Tunai</b>: bayar di kasir untuk Dine In / Take Away, atau ke kurir saat pesanan tiba untuk Delivery.</li>
        <li><b>Virtual Account (BCA, BNI, BRI, Permata)</b> dan <b>QRIS</b>: bayar sesuai nomor VA / kode QR di halaman pembayaran; status lunas dicek otomatis. (Bila belum diaktifkan restoran, opsi ini tidak bisa dipilih.)</li>
        <li>Total sudah termasuk pajak, biaya layanan, dan ongkos kirim.</li>
      </ul>
    </section>

    <section class="card" id="zona"><h2>Zona Pengiriman</h2>
      <p style="font-size:14px;opacity:.85;margin-bottom:8px">Jarak dihitung dari restoran: <b>{{ config('resto.alamat') }}</b>.</p>
      @php $dari = 0; @endphp
      @forelse ($zona as $z)
        <div class="zrow"><span><b>{{ $z->nama_zona }}</b> · {{ rtrim(rtrim(number_format($dari, 1, ',', '.'), '0'), ',') }}–{{ rtrim(rtrim(number_format((float) $z->radius_km, 1, ',', '.'), '0'), ',') }} km @if ($z->contoh_wilayah)<br><small style="opacity:.75">{{ $z->contoh_wilayah }}</small>@endif</span><b>{{ $z->ongkir() > 0 ? $rp($z->ongkir()) : 'Gratis' }}</b></div>
        @php $dari = (float) $z->radius_km; @endphp
      @empty
        <p style="font-size:14px">Delivery belum tersedia.</p>
      @endforelse
    </section>

    <section class="card soft"><h2>Hubungi Kami</h2>
      <p style="font-size:14px">Telepon / WhatsApp: <a href="tel:{{ preg_replace('/\D/', '', config('resto.telepon')) }}" style="font-weight:700;text-decoration:underline">{{ config('resto.telepon') }}</a> · <a href="{{ route('syarat') }}" style="text-decoration:underline">Syarat &amp; Ketentuan</a></p>
    </section>
  </div>
</main>
@endsection
