@extends('layouts.auth')

@section('title', 'Verifikasi 2 Langkah')

@section('form')
    <form class="form" method="post" action="{{ route('dua-faktor.submit') }}">
      @csrf
      <h1>Verifikasi<br>2 Langkah</h1>
      <p class="lead">Buka aplikasi authenticator di HP kamu (Google Authenticator, Microsoft Authenticator, atau Authy), lalu masukkan kode 6 angka untuk <b>{{ config('resto.nama', 'Resto Flow') }}</b>.</p>
      <div class="gap s"></div>
      @include('partials.auth.status')
      <label class="field">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="2" width="12" height="20" rx="2"/><path d="M11 18h2"/></svg>
        <input name="kode" inputmode="numeric" autocomplete="one-time-code" maxlength="20" placeholder="Kode 6 angka" autofocus required>
      </label>
      <p class="hint">HP hilang? Masukkan salah satu <b>kode cadangan</b> (format XXXXX-XXXXX) di kolom yang sama.</p>
      <div class="gap s"></div>
      <button class="btn" type="submit" style="width:100%">Verifikasi &amp; masuk</button>
      <p class="foot" style="margin-top:14px"><a href="{{ route('login') }}">Batal, kembali ke halaman masuk</a></p>
    </form>
@endsection
