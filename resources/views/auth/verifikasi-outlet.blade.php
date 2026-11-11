@extends('layouts.auth')

@section('title', 'Verifikasi Outlet')

@section('form')
    <div class="form">
      <h1>Verifikasi<br>Lokasi Outlet</h1>
      <p class="lead">
        Password benar, tapi akun staf hanya bisa dipakai
        @if (in_array('perangkat', $masalah, true) && in_array('lokasi', $masalah, true))
          <b>di outlet</b> dan dari <b>perangkat outlet yang terdaftar</b>. Perangkat ini belum terdaftar dan tidak terdeteksi di jaringan WiFi outlet.
        @elseif (in_array('perangkat', $masalah, true))
          dari <b>perangkat outlet yang terdaftar</b> (komputer/tablet kasir & dapur). Perangkat ini belum terdaftar.
        @else
          <b>di outlet</b>. Perangkat ini tidak terdeteksi di jaringan WiFi outlet.
        @endif
      </p>
      <div class="gap s"></div>
      @include('partials.auth.status')

      @if ($bisaGps)
        <form method="post" action="{{ route('outlet.verifikasi.submit') }}" id="fGps">
          @csrf
          <input type="hidden" name="lat" id="lat"><input type="hidden" name="lng" id="lng"><input type="hidden" name="akurasi" id="akurasi">
          <button class="btn" type="button" id="btnGps" style="width:100%">Periksa lokasi saya</button>
          <p class="hint" id="gpsInfo">Browser akan meminta izin lokasi. Kamu harus berada dalam {{ $radius }} m dari outlet.</p>
        </form>
        <div class="gap s"></div>
      @endif

      <form method="post" action="{{ route('outlet.verifikasi.submit') }}">
        @csrf
        <p class="lead" style="margin:6px 0 10px"><b>{{ $bisaGps ? 'Atau pakai' : 'Pakai' }} kode darurat dari Owner</b> bila internet/WiFi outlet sedang bermasalah. Setiap pemakaian tercatat.</p>
        <label class="field">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
          <input name="kode" inputmode="numeric" maxlength="9" placeholder="Kode darurat 8 angka, contoh 1234-5678" autocomplete="off" required>
        </label>
        <div class="gap s"></div>
        <button @class(['btn', 'sekunder' => $bisaGps]) type="submit" style="width:100%">Masuk dengan kode darurat</button>
      </form>
      <p class="foot" style="margin-top:14px"><a href="{{ route('login') }}">Batal, kembali ke halaman masuk</a></p>
    </div>
@endsection

@if ($bisaGps)
@push('scripts')
<script>
  (function () {
    var btn = document.getElementById('btnGps'), info = document.getElementById('gpsInfo');
    btn.addEventListener('click', function () {
      if (!navigator.geolocation) { info.textContent = 'Browser ini tidak mendukung lokasi. Pakai kode darurat dari Owner.'; return; }
      btn.disabled = true; btn.textContent = 'Membaca lokasi…';
      navigator.geolocation.getCurrentPosition(function (pos) {
        document.getElementById('lat').value = pos.coords.latitude;
        document.getElementById('lng').value = pos.coords.longitude;
        document.getElementById('akurasi').value = Math.round(pos.coords.accuracy);
        document.getElementById('fGps').submit();
      }, function (err) {
        btn.disabled = false; btn.textContent = 'Periksa lokasi saya';
        info.textContent = err.code === 1
          ? 'Akses lokasi ditolak. Izinkan lokasi untuk situs ini di pengaturan browser (ikon gembok di samping alamat), lalu coba lagi.'
          : 'Lokasi tidak terbaca. Pastikan GPS/Wi-Fi perangkat aktif lalu coba lagi, atau pakai kode darurat.';
      }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
    });
  })();
</script>
@endpush
@endif
