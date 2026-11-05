@extends('layouts.auth')

@section('title', 'Atur Password Baru')

@section('form')
    <form class="form" action="{{ route('password.update') }}" method="post">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">
      <h1>Password Baru</h1>
      <p class="lead">Buat password baru minimal 8 karakter.</p>
      @include('partials.auth-alert')
      <label class="field">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
        <input type="email" name="email" value="{{ old('email', $email) }}" placeholder="Email" autocomplete="email" required>
      </label>
      <label class="field">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
        <input type="password" name="password" placeholder="Password baru" autocomplete="new-password" minlength="8" required autofocus>
      </label>
      <label class="field">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
        <input type="password" name="password_confirmation" placeholder="Konfirmasi password baru" autocomplete="new-password" required>
      </label>
      <button class="btn" type="submit">Simpan Password</button>
      <p class="signup"><a href="{{ route('login') }}">Kembali ke halaman masuk</a></p>
    </form>
@endsection
