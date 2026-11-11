@extends('layouts.auth')

@section('title', 'Konfirmasi Identitas')

@section('form')
    <form class="form" method="post" action="{{ route('konfirmasi.submit') }}">
      @csrf
      <h1>Konfirmasi<br>Identitas</h1>
      <p class="lead">Aksi ini sensitif (data pengguna, pengaturan, keamanan, atau laporan keuangan). {{ $pakaiWajah ? 'Verifikasi wajah lalu masukkan password' : 'Masukkan password' }} untuk melanjutkan. Berlaku {{ $berlakuMenit }} menit.</p>
      <div class="gap s"></div>
      @include('partials.auth.status')
      @if ($pakaiWajah)
        @include('partials.kamera-wajah', ['tantangan' => $tantangan, 'tantanganBaru' => route('wajah.tantangan'), 'setelah' => 'aktifkan'])
        <div class="gap s"></div>
      @endif
      @include('partials.auth.password', ['name' => 'password', 'placeholder' => 'Password kamu'])
      <div class="gap s"></div>
      <button class="btn" type="submit" @disabled($pakaiWajah)>Konfirmasi</button>
      <p class="foot" style="margin-top:14px"><a href="{{ $kembali }}">Batal</a></p>
    </form>
@endsection
