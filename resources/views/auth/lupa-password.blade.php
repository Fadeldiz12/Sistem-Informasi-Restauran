@extends('layouts.auth')

@section('title', 'Lupa Password')

@section('form')
    <form class="form" action="{{ route('password.email') }}" method="post">
      @csrf
      <h1>Lupa Password?</h1>
      <p class="lead">Masukkan email akunmu. Kami kirimkan tautan untuk membuat password baru.</p>
      @if (session('status'))<div class="alert ok" role="status">{{ session('status') }}</div>@endif
      @include('partials.auth-alert')
      <label class="field">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
        <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" autocomplete="email" required autofocus>
      </label>
      <button class="btn" type="submit">Kirim Tautan Reset</button>
      <p class="signup">Ingat password? <a href="{{ route('login') }}">Masuk</a></p>
    </form>
@endsection
