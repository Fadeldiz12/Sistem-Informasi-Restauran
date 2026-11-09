@extends('layouts.pelanggan')

@section('title', 'Cek Pesanan')

@push('styles')
@include('partials.pelanggan.gaya-dasar')
<style>
  .cek { max-width: 520px; margin: 0 auto; padding: clamp(20px, 3vw, 30px); }
  .cek h2 { font: 700 22px var(--f-title); }
  .cek > p { font-size: 14px; opacity: .8; margin: 6px 0 18px; }
  .cek form { display: grid; gap: 14px; }
  .cek label { display: block; font: 800 11px var(--f-accent); letter-spacing: .1em; text-transform: uppercase; margin-bottom: 6px; }
  .cek .kotak { display: flex; align-items: center; gap: 10px; min-height: 50px; padding: 0 16px; border-radius: 16px; background: var(--pp); }
  .cek .kotak input { all: unset; flex: 1; min-width: 0; font: 600 15px var(--f-body); color: var(--m); }
  .cek .salah { font: 600 13px var(--f-body); padding: 12px 14px; border-radius: 14px; background: var(--m); color: var(--cr); }
</style>
@endpush

@section('content')
<main class="wrap" style="padding-top:clamp(20px,4vw,40px);padding-bottom:40px">
  <section class="card cek">
    <h2>Cek Pesanan</h2>
    <p>Pesan tanpa akun dan kehilangan link pesanan? Masukkan nomor pesanan dan nomor HP yang dipakai saat memesan.</p>
    <form method="post" action="{{ route('pelanggan.cek.store') }}">@csrf
      @if ($errors->any())<div class="salah" role="alert">{{ $errors->first() }}</div>@endif
      <div><label for="nomor">Nomor pesanan</label><div class="kotak"><svg class="ic"><use href="#i-receipt"/></svg><input id="nomor" name="nomor" value="{{ old('nomor') }}" maxlength="20" placeholder="Contoh ORD-0007" autocomplete="off" required></div></div>
      <div><label for="noHp">Nomor HP</label><div class="kotak"><svg class="ic"><use href="#i-phone"/></svg><input id="noHp" name="no_hp" value="{{ old('no_hp') }}" maxlength="25" type="tel" inputmode="tel" placeholder="Contoh 081234567890" autocomplete="tel" required></div></div>
      <button class="btn" type="submit">Cari Pesanan <svg class="ic"><use href="#i-arr"/></svg></button>
    </form>
    <p style="margin:16px 0 0;font-size:13px">Pesanan dengan akun? <a href="{{ route('login') }}" style="font-weight:700;text-decoration:underline">Masuk</a> untuk melihat riwayatmu.</p>
  </section>
</main>
@endsection
