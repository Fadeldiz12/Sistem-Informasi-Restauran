@extends('layouts.panel')

@section('title', 'Verifikasi 2 Langkah')
@section('crumb', $user->roleLabel().' / Verifikasi 2 Langkah')
@section('heading', 'Verifikasi 2 Langkah')
@section('sub', 'Selain password, masuk juga butuh kode dari HP kamu. Password yang bocor saja tidak cukup untuk membobol akun.')

@push('styles')
<style>
  .tfa-grid { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(0, .9fr); gap: 18px; align-items: start; }
  .tfa-qr { display: flex; gap: 18px; align-items: center; flex-wrap: wrap; margin: 6px 0 14px; }
  .tfa-qr img { width: 168px; height: 168px; background: #fff; padding: 8px; border-radius: 14px; }
  .tfa-kunci { font: 700 17px/1.5 ui-monospace, monospace; letter-spacing: .06em; padding: 12px 14px; border-radius: 14px; background: var(--pp); word-break: break-all; }
  .tfa-cadangan { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; font: 700 15px ui-monospace, monospace; letter-spacing: .06em; }
  .tfa-cadangan span { padding: 8px 10px; border-radius: 10px; background: var(--pp); text-align: center; }
  .tfa-langkah { padding-left: 18px; display: grid; gap: 8px; font-size: 14px; line-height: 1.5; }
  @media (max-width: 900px) { .tfa-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
  @if ($kodeCadangan)
    <div class="card" style="margin-bottom:18px;box-shadow:inset 0 0 0 2px var(--m)">
      <div class="card-head"><div><h2>Simpan Kode Cadangan Ini Sekarang</h2><p>Hanya ditampilkan sekali. Tiap kode bisa dipakai satu kali untuk masuk bila HP hilang atau rusak. Cetak atau tulis, simpan di tempat aman (bukan di HP yang sama).</p></div></div>
      <div class="tfa-cadangan">@foreach ($kodeCadangan as $k)<span>{{ $k }}</span>@endforeach</div>
      <button type="button" class="btn o sm" style="margin-top:14px" onclick="window.print()"><svg class="ic"><use href="#i-printer"/></svg>Cetak</button>
    </div>
  @endif

  @if ($aktif)
    <div class="tfa-grid">
      <div class="card"><div class="card-head"><div><h2>Status</h2><p>Aktif sejak {{ $user->dua_faktor_aktif_at->translatedFormat('d F Y, H.i') }}</p></div><span class="pill b">Aktif</span></div>
        <div class="note"><svg class="ic"><use href="#i-check"/></svg><span>Setiap kali masuk, setelah password benar kamu diminta kode 6 angka dari aplikasi authenticator. Sisa kode cadangan: <b>{{ $sisaCadangan }}</b> dari {{ \App\Support\DuaFaktor::JUMLAH_CADANGAN }}.</span></div>
        <form method="post" action="{{ route('akun.dua-faktor.cadangan') }}" style="margin-top:14px">@csrf
          <button class="btn o" type="submit" style="width:100%"><svg class="ic"><use href="#i-plus"/></svg>Buat kode cadangan baru</button></form>
      </div>
      <div class="card"><div class="card-head"><div><h2>Matikan</h2><p>Tidak disarankan. Akun Owner/Admin bisa mengubah data penting.</p></div></div>
        <form method="post" action="{{ route('akun.dua-faktor.matikan') }}" style="display:grid;gap:10px">
          @csrf @method('DELETE')
          <div class="in"><svg class="ic"><use href="#i-lock"/></svg><input type="password" name="password" placeholder="Password kamu" autocomplete="current-password" required></div>
          <button class="btn o" type="submit">Matikan verifikasi 2 langkah</button>
        </form>
      </div>
    </div>
  @else
    <div class="tfa-grid">
      <div class="card"><div class="card-head"><div><h2>Aktifkan</h2><p>Butuh sekitar 2 menit dan HP dengan aplikasi authenticator.</p></div><span class="pill m">Belum aktif</span></div>
        <ol class="tfa-langkah">
          <li>Pasang <b>Google Authenticator</b>, <b>Microsoft Authenticator</b>, atau <b>Authy</b> dari Play Store / App Store.</li>
          <li>Di aplikasi, pilih tambah akun lalu {{ $qr ? 'pindai QR di bawah, atau' : '' }} pilih <b>masukkan kunci penyiapan</b> dan ketik kunci di bawah (jenis: berbasis waktu).</li>
        </ol>
        <div class="tfa-qr">
          @if ($qr)<img src="{{ $qr }}" alt="QR verifikasi 2 langkah">@endif
          <div style="flex:1;min-width:200px"><div style="font-size:12.5px;opacity:.7;margin-bottom:6px">Kunci penyiapan · akun {{ $user->email }}</div><div class="tfa-kunci">{{ $rahasia }}</div>
            <a href="{{ $uri }}" style="display:inline-block;margin-top:8px;font-size:13px"><b>Buka langsung di aplikasi</b> (bila halaman ini dibuka di HP)</a></div>
        </div>
        <form method="post" action="{{ route('akun.dua-faktor.aktifkan') }}" style="display:grid;gap:10px">
          @csrf
          <label style="font-size:14px"><b>3. Masukkan kode 6 angka</b> yang muncul di aplikasi</label>
          <div style="display:flex;gap:10px;flex-wrap:wrap">
            <div class="in" style="flex:1;min-width:160px"><svg class="ic"><use href="#i-lock"/></svg><input name="kode" inputmode="numeric" autocomplete="one-time-code" maxlength="8" placeholder="123456" required></div>
            <button class="btn" type="submit"><svg class="ic"><use href="#i-check"/></svg>Aktifkan</button>
          </div>
        </form>
      </div>
      <div class="card"><div class="card-head"><div><h2>Penting</h2></div></div>
        <ul class="tfa-langkah" style="list-style:disc">
          <li>Jangan bagikan kunci penyiapan atau QR ini. Siapa pun yang memilikinya bisa membuat kode untuk akunmu.</li>
          <li>Pastikan jam HP diatur <b>otomatis</b>; kode hanya berlaku 30 detik.</li>
          <li>Setelah aktif kamu mendapat <b>{{ \App\Support\DuaFaktor::JUMLAH_CADANGAN }} kode cadangan</b> untuk dipakai bila HP hilang.</li>
          <li>HP hilang dan kode cadangan tidak ada? {{ $user->hasRole(\App\Models\User::ROLE_ADMIN) ? 'Owner bisa mereset verifikasi 2 langkah akunmu.' : 'Owner lain bisa mereset, atau jalankan "php artisan resto:matikan-2fa email" di server.' }}</li>
        </ul>
      </div>
    </div>
  @endif
@endsection
