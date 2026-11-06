@extends('layouts.pelanggan')

@section('title', 'Profil Saya')

@push('styles')
@include('partials.pelanggan.gaya-dasar')
<style>
  .profil { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: clamp(18px, 2.4vw, 32px); align-items: start; }
  .fstack { display: grid; gap: 14px; }
  .err { font: 600 12.5px var(--f-body); margin-top: 6px; }
  .in.ro { opacity: .7; }
  @media (max-width: 860px) { .profil { grid-template-columns: minmax(0, 1fr); } }
</style>
@endpush

@section('content')
<main class="wrap">
  <div class="page-head"><div><div class="crumb"><a href="{{ route('pelanggan.beranda') }}">Beranda</a><span>/</span><span>Profil</span></div><h1>Profil Saya</h1></div></div>
  @if (session('status'))<div class="flash ok" role="status" style="margin:0 0 18px">{{ session('status') }}</div>@endif

  <div class="profil">
    <form class="card" method="post" action="{{ route('pelanggan.profil.update') }}" novalidate>
      @csrf @method('PUT')
      <div class="card-head" style="margin-bottom:14px"><div><h2>Data diri</h2><p style="font-size:13.5px;opacity:.7">Dipakai untuk pesanan dan dihubungi kurir saat pengantaran.</p></div></div>
      <div class="fstack">
        <div class="field"><label for="nama">Nama lengkap</label><div class="in"><svg class="ic"><use href="#i-user"/></svg><input id="nama" name="nama" value="{{ old('nama', $u->nama) }}" maxlength="100" required></div>@error('nama')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="field"><label for="hp">Nomor HP</label><div class="in"><svg class="ic"><use href="#i-phone"/></svg><input id="hp" name="no_hp" value="{{ old('no_hp', $u->no_hp) }}" maxlength="20" inputmode="tel" placeholder="Contoh 081234567890" required></div>@error('no_hp')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="field"><label>Email</label><div class="in ro"><svg class="ic"><use href="#i-mail"/></svg><input value="{{ $u->email }}" readonly></div><div style="font-size:12px;opacity:.65;margin-top:6px">Email tidak bisa diubah.</div></div>
        <div class="field"><label for="alamat">Alamat pengantaran (opsional)</label><div class="in ta"><svg class="ic" style="margin-top:2px"><use href="#i-pin"/></svg><textarea id="alamat" name="alamat" rows="3" maxlength="1000" placeholder="Nama jalan, nomor rumah, patokan">{{ old('alamat', $u->alamat) }}</textarea></div>@error('alamat')<div class="err">{{ $message }}</div>@enderror</div>
        <button class="btn" type="submit"><svg class="ic"><use href="#i-check"/></svg>Simpan Profil</button>
      </div>
    </form>

    <form class="card soft" method="post" action="{{ route('pelanggan.profil.password') }}" novalidate>
      @csrf @method('PUT')
      <div class="card-head" style="margin-bottom:14px"><div><h2>Ganti password</h2><p style="font-size:13.5px;opacity:.7">Minimal 8 karakter, berisi huruf dan angka.</p></div></div>
      <div class="fstack">
        <div class="field"><label for="pl">Password lama</label><div class="in"><svg class="ic"><use href="#i-lock"/></svg><input type="password" id="pl" name="password_lama" autocomplete="current-password" required></div>@error('password_lama')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="field"><label for="pb">Password baru</label><div class="in"><svg class="ic"><use href="#i-lock"/></svg><input type="password" id="pb" name="password" autocomplete="new-password" required></div>@error('password')<div class="err">{{ $message }}</div>@enderror</div>
        <div class="field"><label for="pk">Ulangi password baru</label><div class="in"><svg class="ic"><use href="#i-lock"/></svg><input type="password" id="pk" name="password_confirmation" autocomplete="new-password" required></div></div>
        <button class="btn o" type="submit">Ganti Password</button>
        <p style="font-size:12.5px;opacity:.7">Lupa password lama? Keluar lalu pilih <b>Lupa password?</b> di halaman masuk.</p>
      </div>
    </form>
  </div>
</main>
@endsection
