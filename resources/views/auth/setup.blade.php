@extends('layouts.auth')

@section('title', 'Pemasangan Pertama')

@section('form')
    <form class="form" action="{{ route('setup.submit') }}" method="post">
      @csrf
      <h1>Buat Akun<br>Owner Pertama</h1>
      <p class="lead">Belum ada akun Owner. Halaman ini hanya bisa dibuka dari komputer server dan hilang otomatis setelah Owner dibuat. Akun lain (Admin, Kasir, Dapur, Kurir) dibuat Owner dari menu <b>Pengguna &amp; Role</b>.</p>
      <div class="gap s"></div>
      @include('partials.auth-alert')
      <div class="stack">
        <label class="field"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
          <input name="nama" value="{{ old('nama') }}" placeholder="Nama lengkap Owner" maxlength="100" required autofocus></label>
        <label class="field"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
          <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" autocomplete="email" required></label>
        <label class="field"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/></svg>
          <input name="no_hp" value="{{ old('no_hp') }}" placeholder="Nomor HP" inputmode="tel" required></label>
        @include('partials.auth.password', ['name' => 'password', 'placeholder' => 'Password (min. 8 karakter)'])
        @include('partials.auth.password', ['name' => 'password_confirmation', 'placeholder' => 'Ulangi password'])
      </div>
      <div class="gap s"></div>
      <button class="btn" type="submit">Buat Akun Owner</button>
    </form>
@endsection
