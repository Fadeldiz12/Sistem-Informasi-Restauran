<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title') · Resto Flow</title>
@include('partials.favicon')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Raleway:wght@600;700;800&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/panel.css') }}?v={{ filemtime(public_path('css/panel.css')) }}">
@stack('styles')
</head>
<body>
@include('partials.panel.icons')
<div class="app">
  @include('partials.panel.sidebar-'.auth()->user()->role)

  <main class="main">
    <div class="topbar">
      <div><div class="crumb">@yield('crumb')</div><h1>@yield('heading')</h1><div class="sub">@yield('sub')</div></div>
      <div class="tb-right">
        @yield('topbar')
        @include('partials.profile-menu', ['trigger' => 'me'])
      </div>
    </div>

    @if (session('error'))
      <div class="alert" role="alert">{{ session('error') }}</div>
    @endif

    @yield('content')
  </main>
</div>

@stack('modals')

<div class="ovl" id="mHapus"><div class="modal sm">
  <div class="big-ic" style="background:var(--m);color:var(--cr)"><svg class="ic"><use href="#i-trash"/></svg></div>
  <h3 style="font:700 24px var(--f-title)">@yield('delete-title', 'Hapus data ini?')</h3>
  <div class="pill p" style="margin-top:12px;font-size:13px;padding:7px 14px" id="delName"></div>
  <p style="opacity:.75;margin:12px 28px 22px">@yield('delete-text', 'Data akan dihapus permanen dan tidak dapat dikembalikan.')</p>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:0 28px 28px"><button class="btn o lg" data-close>Tidak</button><button class="btn lg" id="delOk">Ya, Hapus</button></div>
</div></div>
<div class="ovl" id="mLogout"><div class="modal sm">
  <div class="big-ic" style="background:var(--bl)"><svg class="ic"><use href="#i-logout"/></svg></div>
  <h3 style="font:700 24px var(--f-title)">Logout dari sistem?</h3>
  <p style="opacity:.75;margin:10px 28px 22px">Sesi login akan dihapus dan Anda diarahkan kembali ke halaman login.</p>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:0 28px 28px"><button class="btn o lg" data-close>Batal</button><a class="btn lg" href="#" data-logout>Ya, Logout</a></div>
</div></div>
<div class="toast" id="toast"><svg class="ic"><use href="#i-check"/></svg><span id="toastMsg">Tersimpan</span></div>

{{-- data untuk panel.js: pesan sukses dan dialog yang dibuka ulang saat validasi gagal --}}
<div id="panel-boot" hidden data-toast="{{ session('status') }}" data-reopen="{{ $errors->any() ? old('_modal') : '' }}"></div>
<script src="{{ asset('js/panel.js') }}?v={{ filemtime(public_path('js/panel.js')) }}"></script>
@stack('scripts')
@include('partials.logout')
</body>
</html>
