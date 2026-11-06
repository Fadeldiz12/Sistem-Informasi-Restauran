@extends('layouts.auth')

@section('title', 'Password Baru')

@section('form')
    <form class="form" action="{{ route('password.update') }}" method="post">
      @csrf
      <h1>Buat Password Baru</h1>
      <p class="lead">Kode OTP benar. Buat password baru untuk <b>{{ \App\Services\OtpService::samarkan($email) }}</b>, minimal 8 karakter dan berisi huruf serta angka.</p>
      <div class="gap"></div>
      @include('partials.auth.status')
      <div class="stack">
        @include('partials.auth.password', ['name' => 'password', 'placeholder' => 'Password baru', 'autocomplete' => 'new-password', 'min' => true, 'autofocus' => true])
        @include('partials.auth.password', ['name' => 'password_confirmation', 'placeholder' => 'Konfirmasi password baru', 'autocomplete' => 'new-password'])
      </div>
      <p class="hint" id="cocok" aria-live="polite">&nbsp;</p>
      <div class="gap s"></div>
      <button class="btn" type="submit">Simpan Password</button>
      <p class="foot"><a href="{{ route('login') }}">Kembali ke halaman masuk</a></p>
    </form>
@endsection

@push('scripts')
<script>
  (function () {
    var a = document.querySelector('[name=password]'), b = document.querySelector('[name=password_confirmation]'), h = document.getElementById('cocok');
    function cek() {
      if (!a.value && !b.value) { h.innerHTML = '&nbsp;'; return; }
      if (a.value.length < 8) { h.textContent = 'Password minimal 8 karakter (' + a.value.length + '/8)'; return; }
      if (!/[A-Za-z]/.test(a.value) || !/\d/.test(a.value)) { h.textContent = 'Password harus berisi huruf dan angka'; return; }
      h.textContent = !b.value ? 'Ulangi password di kolom konfirmasi' : (a.value === b.value ? '✓ Password cocok' : '✗ Konfirmasi password belum sama');
    }
    a.addEventListener('input', cek); b.addEventListener('input', cek);
  })();
</script>
@endpush
