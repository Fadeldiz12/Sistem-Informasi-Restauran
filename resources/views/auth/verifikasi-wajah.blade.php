@extends('layouts.auth')

@section('title', $judul)

@section('form')
    <form class="form" method="post" action="{{ $kirimKe }}">
      @csrf
      <h1>{{ $judul }}</h1>
      <p class="lead">Hadap lurus ke kamera, tetap tatap layar saat layar berkedip warna, lalu ikuti 3 gerakan yang muncul. Wajah hanya dicocokkan dengan akun yang sedang masuk; foto tidak dikirim atau disimpan.</p>
      <div class="gap s"></div>
      @include('partials.auth.status')
      @include('partials.kamera-wajah', ['tantangan' => $tantangan, 'tantanganBaru' => $tantanganBaru, 'setelah' => 'kirim'])
    </form>

    @if ($persetujuan)
      <details class="form" style="margin-top:6px" @if ($errors->has('email_penyetuju')) open @endif>
        <summary style="cursor:pointer;font-weight:700;text-align:center">Wajah tidak bisa terbaca? Minta persetujuan Owner/Admin</summary>
        <form method="post" action="{{ route('wajah.setujui') }}" style="margin-top:12px">
          @csrf
          <p class="lead" style="margin:0 0 10px">Owner atau Admin yang <b>hadir di outlet</b> memasukkan email &amp; password <b>mereka sendiri</b> di perangkat ini. Tercatat di Log Aktivitas.</p>
          <div class="stack">
            <label class="field">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
              <input type="email" name="email_penyetuju" value="{{ old('email_penyetuju') }}" placeholder="Email Owner/Admin" autocomplete="off" required>
            </label>
            @include('partials.auth.password', ['name' => 'password_penyetuju', 'placeholder' => 'Password Owner/Admin'])
          </div>
          <div class="gap s"></div>
          <button class="btn sekunder" type="submit" style="width:100%">Setujui masuk</button>
        </form>
      </details>
    @endif
    <p class="foot" style="margin-top:14px"><a href="{{ route('login') }}">Batal, kembali ke halaman masuk</a></p>
@endsection
