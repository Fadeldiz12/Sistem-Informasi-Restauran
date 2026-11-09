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
@stack('styles')
<style>
  /* tambahan bersama versi dinamis */
  [hidden] { display: none !important; }
  .flash { margin: 18px 0 0; padding: 12px 18px; border-radius: 16px; background: var(--m); color: var(--cr); font: 600 14px/1.5 var(--f-body); }
  .flash.ok { background: var(--bl); color: var(--m); }
  .flash ul { margin: 0; padding-left: 18px; }
  .p-toast { position: fixed; z-index: 120; left: 50%; bottom: 28px; transform: translate(-50%, 20px); opacity: 0; visibility: hidden; background: var(--m); color: var(--cr);
             border-radius: 999px; padding: 12px 20px; font: 600 14px var(--f-accent); transition: .3s; max-width: calc(100vw - 32px); text-align: center; }

  /* logo gambar + teks */
  .logo { flex-direction: row !important; align-items: center; gap: 10px; }
  .logo .logo-mk { width: 46px; height: 46px; border-radius: 50%; flex: none; }
  .logo .logo-tx { display: flex; flex-direction: column; line-height: 1; }
  .logo .logo-tx b { white-space: nowrap; }
  @media (max-width: 520px) { .logo .logo-mk { width: 38px; height: 38px; } .logo .logo-tx b { font-size: 17px; } .logo .logo-tx small { display: none; } }
  @media (max-width: 400px) { .hd-right > .icon-btn { display: none; } }
  .masuk-btn { display: inline-flex; align-items: center; gap: 8px; height: 44px; padding: 0 16px; border-radius: 999px; box-shadow: inset 0 0 0 1.5px var(--m); color: var(--m); font: 700 14px var(--f-accent); }
  .masuk-btn:hover { background: var(--pp); }
  .masuk-btn .ic { width: 18px; height: 18px; }
  @media (max-width: 640px) { .masuk-btn { width: 44px; padding: 0; justify-content: center; } .masuk-btn span { display: none; } }
  .ajak-akun { display: flex; gap: 14px; align-items: center; flex-wrap: wrap; padding: 16px 18px; border-radius: 20px; background: var(--bl); color: var(--m); }
  .ajak-akun b { display: block; font: 700 16px var(--f-title); }
  .ajak-akun p { font-size: 13.5px; opacity: .85; margin-top: 2px; }
  .ajak-akun .btn { height: 44px; padding: 0 20px; margin-left: auto; }
  .p-toast.on { transform: translate(-50%, 0); opacity: 1; visibility: visible; }
</style>
</head>
<body>
@include('partials.pelanggan.icons')
@include('partials.pelanggan.topbar')
<header><div class="wrap">
  <a class="logo" href="{{ route('pelanggan.beranda') }}"><img class="logo-mk" src="{{ asset('images/logo-192.png') }}" alt=""><span class="logo-tx"><b>RESTO FLOW</b><small>PIZZA &amp; PASTA HOUSE</small></span></a>
  @include('partials.pelanggan.nav')
  <div class="hd-right">
    <a class="icon-btn" href="{{ route('pelanggan.beranda', ['cari' => 1]) }}#menu" aria-label="Cari menu"><svg class="ic"><use href="#i-search"/></svg></a>
    @auth
      @include('partials.profile-menu', ['trigger' => 'user'])
    @else
      {{-- pesan tanpa login: akun opsional --}}
      <a class="masuk-btn" href="{{ route('login') }}"><svg class="ic"><use href="#i-user"/></svg><span>Masuk</span></a>
    @endauth
    @include('partials.pelanggan.cart-button')
  </div>
</div>@include('partials.pelanggan.catstrip')
</header>

@yield('content')

@include('partials.pelanggan.footer')
@include('partials.pelanggan.bottomnav')
@stack('modals')
<div class="p-toast" id="pToast" role="status"></div>
<div id="p-boot" hidden data-toast="{{ session('status') }}"></div>
<script src="{{ asset('js/pelanggan.js') }}?v={{ filemtime(public_path('js/pelanggan.js')) }}"></script>
@stack('scripts')
@include('partials.logout')
{{-- halaman dari cache Back/Forward dimuat ulang supaya sesi yang sudah keluar tidak menampilkan data --}}
<script>window.addEventListener('pageshow', function (e) { if (e.persisted) location.reload(); });</script>
</body>
</html>
