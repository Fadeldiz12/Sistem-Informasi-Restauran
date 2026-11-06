@extends('layouts.auth')

@section('title', 'Masuk')

@section('form')
    <form class="form" action="{{ route('login.submit') }}" method="post">
      @csrf
      <h1>Masuk ke<br>Resto Flow</h1>
      <p class="lead">Pesan pizza &amp; pasta untuk <b>Delivery</b> (diantar), <b>Take Away</b> (bawa pulang), atau <b>Dine In</b> (makan di tempat), lalu pantau pesananmu. Staf restoran juga masuk di sini.</p>
      <div class="gap s"></div>
      @include('partials.auth.status')
      <div class="stack">
        <label class="field">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
          <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" autocomplete="email" required>
        </label>
        @include('partials.auth.password', ['name' => 'password', 'placeholder' => 'Password'])
      </div>
      <div class="login-opsi">
        <label class="agree" style="margin-top:0"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Ingat saya</label>
        <a class="forgot" style="margin-top:0" href="{{ route('password.request') }}">Lupa password?</a>
      </div>
      <div class="gap s"></div>
      <button class="btn" type="submit">Masuk</button>
      <a class="btn sekunder" href="{{ route('registrasi') }}">Daftar &amp; Pesan Sekarang</a>
      <p class="foot" style="margin-top:12px">Pelanggan baru? Daftar gratis dalam 1 menit.</p>
    </form>
@endsection
