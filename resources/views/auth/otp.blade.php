@extends('layouts.auth')

@section('title', $reset ? 'Kode OTP Reset Password' : 'Verifikasi Akun')

@section('form')
    <form class="form" action="{{ route('otp.verifikasi') }}" method="post" id="fOtp">
      @csrf
      <h1>{{ $reset ? 'Masukkan Kode OTP' : 'Verifikasi Email' }}</h1>
      <p class="lead">Kami mengirim kode 6 digit ke <b>{{ $email }}</b>. Kode berlaku {{ $berlaku }} menit.</p>
      <div class="gap"></div>
      @include('partials.auth.status')
      @if (session('otp_dev'))
        <div class="alert dev" role="note">Mode pengembangan (email belum diatur): kode OTP <b>{{ session('otp_dev') }}</b></div>
      @endif
      <input type="hidden" name="kode" id="kode">
      <div class="otp" role="group" aria-label="Kode OTP 6 digit">
        @for ($i = 0; $i < 6; $i++)
          <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]" autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}" aria-label="Digit {{ $i + 1 }}" @if ($i === 0) autofocus @endif>
        @endfor
      </div>
      <p class="hint">Tidak ada di kotak masuk? Periksa folder spam.</p>
      <div class="gap s"></div>
      <button class="btn" type="submit" id="btnOtp">{{ $reset ? 'Lanjutkan' : 'Verifikasi' }}</button>
    </form>
    <form class="form" action="{{ route('otp.ulang') }}" method="post" style="margin-top:0">
      @csrf
      <p class="foot">Belum menerima kode? <button type="submit" id="ulang" data-jeda="{{ $jeda }}">Kirim ulang</button></p>
      <p class="foot" style="margin-top:10px"><a href="{{ $reset ? route('password.request') : route('registrasi') }}">{{ $reset ? 'Ganti email' : 'Salah email? Daftar ulang' }}</a></p>
    </form>
@endsection

@push('scripts')
<script>
  (function () {
    var kotak = [].slice.call(document.querySelectorAll('.otp input')), kode = document.getElementById('kode'), form = document.getElementById('fOtp');
    function sinkron() { kode.value = kotak.map(function (k) { return k.value; }).join(''); kotak.forEach(function (k) { k.classList.toggle('isi', !!k.value); }); }
    function isi(dari, teks) {
      var d = teks.replace(/\D/g, '').split('');
      for (var i = dari; i < kotak.length && d.length; i++) kotak[i].value = d.shift();
      sinkron(); var kosong = kotak.find(function (k) { return !k.value; });
      (kosong || kotak[kotak.length - 1]).focus();
      if (kode.value.length === 6) form.requestSubmit ? form.requestSubmit() : form.submit();
    }
    kotak.forEach(function (k, i) {
      k.addEventListener('input', function () { var v = k.value; k.value = ''; if (v) isi(i, v); else sinkron(); });
      k.addEventListener('keydown', function (e) {
        if (e.key === 'Backspace' && !k.value && i > 0) { kotak[i - 1].value = ''; kotak[i - 1].focus(); sinkron(); e.preventDefault(); }
        if (e.key === 'ArrowLeft' && i > 0) kotak[i - 1].focus();
        if (e.key === 'ArrowRight' && i < 5) kotak[i + 1].focus();
      });
      k.addEventListener('paste', function (e) { e.preventDefault(); isi(i, (e.clipboardData || window.clipboardData).getData('text')); });
      k.addEventListener('focus', function () { k.select(); });
    });
    form.addEventListener('submit', function (e) { sinkron(); if (kode.value.length !== 6) { e.preventDefault(); var k = kotak.find(function (x) { return !x.value; }); if (k) k.focus(); } });

    // hitung mundur kirim ulang
    var ulang = document.getElementById('ulang'), sisa = +ulang.dataset.jeda;
    function tik() {
      if (sisa > 0) { ulang.disabled = true; ulang.textContent = 'Kirim ulang (' + Math.floor(sisa / 60) + ':' + String(sisa % 60).padStart(2, '0') + ')'; sisa--; setTimeout(tik, 1000); }
      else { ulang.disabled = false; ulang.textContent = 'Kirim ulang'; }
    }
    tik();
  })();
</script>
@endpush
