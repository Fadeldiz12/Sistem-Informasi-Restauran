@extends('layouts.auth')

@section('title', 'Masuk')

@section('form')
    <form class="form" action="{{ route('login.submit') }}" method="post">
      @csrf
      <h1>Selamat Datang<br>Kembali!</h1>
      <div class="gap"></div>
      @include('partials.auth.status')
      <div class="stack">
        <label class="field">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
          <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" autocomplete="email" required>
        </label>
        @include('partials.auth.password', ['name' => 'password', 'placeholder' => 'Password'])
      </div>
      <a class="forgot" href="{{ route('password.request') }}">Lupa password?</a>
      <div class="gap s"></div>
      <button class="btn" type="submit">Masuk</button>
      <p class="foot">Belum punya akun? <a href="{{ route('registrasi') }}">Daftar</a></p>
    </form>
@endsection
