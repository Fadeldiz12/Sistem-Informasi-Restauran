@extends('errors.layout')

@php $detik = (int) ($exception->getHeaders()['Retry-After'] ?? 60); @endphp
@section('kode', '429')
@section('judul', 'Terlalu Banyak Percobaan')
@section('pesan')Demi keamanan, coba lagi dalam <b id="sisa">{{ $detik }}</b> detik. Akun Anda tidak diblokir.@endsection
@section('aksi')
  <a href="{{ url()->previous() }}" id="lagi" class="o">Kembali</a>
  @guest<a href="{{ route('password.request') }}" class="o">Lupa password?</a>@endguest
@endsection

@push('scripts')
<script>
  (function () {
    var s = {{ $detik }}, el = document.getElementById('sisa');
    var t = setInterval(function () { s--; el.textContent = Math.max(0, s); if (s <= 0) { clearInterval(t); document.getElementById('lagi').textContent = 'Coba lagi'; } }, 1000);
  })();
</script>
@endpush
