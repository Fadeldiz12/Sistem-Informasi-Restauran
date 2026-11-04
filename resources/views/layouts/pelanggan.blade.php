<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title') · SIM Resto</title>
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
  .p-toast.on { transform: translate(-50%, 0); opacity: 1; visibility: visible; }
</style>
</head>
<body>
@include('partials.pelanggan.icons')
@include('partials.pelanggan.topbar')
<header><div class="wrap">
  <a class="logo" href="{{ route('pelanggan.beranda') }}"><img class="logo-mk" src="{{ asset('images/logo-192.png') }}" alt=""><span class="logo-tx"><b>SIM RESTO</b><small>PIZZA &amp; PASTA HOUSE</small></span></a>
  @include('partials.pelanggan.nav')
  <div class="hd-right">
    <a class="icon-btn" href="{{ route('pelanggan.beranda') }}#menu" aria-label="Lihat menu"><svg class="ic"><use href="#i-search"/></svg></a>
    @include('partials.profile-menu', ['trigger' => 'user'])
    @include('partials.pelanggan.cart-button')
  </div>
</div>@include('partials.pelanggan.catstrip')
</header>

@yield('content')

@include('partials.pelanggan.footer')
@stack('modals')
<div class="p-toast" id="pToast" role="status"></div>
<div id="p-boot" hidden data-toast="{{ session('status') }}"></div>
<script src="{{ asset('js/pelanggan.js') }}?v={{ filemtime(public_path('js/pelanggan.js')) }}"></script>
@stack('scripts')
@include('partials.logout')
</body>
</html>
