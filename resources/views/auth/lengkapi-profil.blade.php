@extends('layouts.auth')

@section('title', 'Lengkapi Profil')

@push('styles')
<style>
  .field.ta { height: auto; align-items: flex-start; padding-top: 14px; padding-bottom: 14px; border-radius: 26px; }
  .field.ta textarea { all: unset; flex: 1; min-width: 0; color: var(--m); font: inherit; min-height: 48px; line-height: 1.5; }
  .field.ta textarea::placeholder { color: rgba(151, 23, 58, .55); }
  .logout { background: none; border: 0; color: var(--m); font: 800 1em var(--f-accent); text-decoration: underline; cursor: pointer; padding: 0; }
</style>
@endpush

@section('form')
    <form class="form" action="{{ route('profil.lengkapi.simpan') }}" method="post">
      @csrf
      <h1>Satu Langkah Lagi</h1>
      <p class="lead">Halo, <b>{{ auth()->user()->namaDepan() }}</b>! Isi nomor HP agar kasir dan kurir bisa menghubungimu soal pesanan.</p>
      @include('partials.auth-alert')
      <label class="field">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2z"/></svg>
        <input name="hp" value="{{ old('hp') }}" maxlength="20" inputmode="tel" placeholder="Nomor HP" autocomplete="tel" required autofocus>
      </label>
      <label class="field ta">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
        <textarea name="alamat" rows="2" placeholder="Alamat pengantaran (opsional)">{{ old('alamat', auth()->user()->alamat) }}</textarea>
      </label>
      <button class="btn" type="submit">Simpan &amp; Lanjutkan</button>
      <p class="signup">Bukan akunmu? <button type="button" class="logout" data-logout>Keluar</button></p>
    </form>
    @include('partials.logout')
@endsection
