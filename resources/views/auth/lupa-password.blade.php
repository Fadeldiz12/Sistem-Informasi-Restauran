@extends('layouts.auth')

@section('title', 'Lupa Password')

@section('form')
    <form class="form" action="{{ route('password.email') }}" method="post">
      @csrf
      <h1>Lupa Password?</h1>
      <p class="lead">Masukkan email akunmu. Kami kirimkan kode OTP 6 digit untuk membuat password baru.</p>
      <div class="gap"></div>
      @include('partials.auth.status')
      <label class="field">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
        <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" autocomplete="email" required autofocus>
      </label>
      <div class="gap"></div>
      <button class="btn" type="submit">Kirim Kode OTP</button>
      <p class="foot">Ingat password? <a href="{{ route('login') }}">Masuk</a></p>
    </form>
@endsection
