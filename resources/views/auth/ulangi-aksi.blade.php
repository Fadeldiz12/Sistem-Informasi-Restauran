@extends('layouts.auth')

@section('title', 'Menyimpan…')

{{-- tanpa referer: redirect "kembali" sesudah aksi memakai halaman asal yang dicatat di sesi, bukan halaman ini --}}
@push('styles')<meta name="referrer" content="no-referrer">@endpush

@section('form')
    {{-- Identitas sudah dikonfirmasi: aksi yang tadi tertahan dikirim ulang otomatis --}}
    <form class="form" id="fUlang" method="post" action="{{ $aksi['url'] }}">
      @csrf
      @if (strtoupper($aksi['method']) !== 'POST')<input type="hidden" name="_method" value="{{ strtoupper($aksi['method']) }}">@endif
      @foreach ($isian as $nama => $nilai)<input type="hidden" name="{{ $nama }}" value="{{ $nilai }}">@endforeach
      <h1>Identitas<br>Terkonfirmasi</h1>
      <p class="lead">Menyimpan perubahanmu…</p>
      <noscript><button class="btn" type="submit">Lanjutkan</button></noscript>
    </form>
@endsection

@push('scripts')
<script>document.getElementById('fUlang').submit();</script>
@endpush
