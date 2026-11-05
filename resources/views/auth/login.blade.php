@extends('layouts.auth')

@section('title', 'Masuk')

@section('form')
    <form class="form" action="{{ route('login.submit') }}" method="post">
      @csrf
      <h1>Selamat Datang Kembali!</h1>
      @if (session('status'))<div class="alert ok" role="status">{{ session('status') }}</div>@endif
      @include('partials.auth-alert')
      <label class="field">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
        <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" autocomplete="email" required>
      </label>
      <label class="field">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
        <input type="password" name="password" placeholder="Password" autocomplete="current-password" required>
      </label>
      <a class="forgot" href="{{ route('password.request') }}">Lupa password?</a>
      <button class="btn" type="submit">Masuk</button>
      <div class="divider">atau</div>
      <div class="social">
        <a href="{{ route('auth.google') }}"><svg viewBox="0 0 24 24" fill="#97173A"><path d="M21.6 10.2H12v3.9h5.5c-.5 2.5-2.7 4.1-5.5 4.1a6.2 6.2 0 1 1 4-10.9l2.8-2.8A10 10 0 1 0 12 22c5.8 0 9.8-4.1 9.8-9.8 0-.7-.1-1.3-.2-2z"/></svg>Masuk dengan Google</a>
      </div>
      <p class="signup">Belum punya akun? <a href="{{ route('registrasi') }}">Daftar</a></p>
    </form>
@endsection
