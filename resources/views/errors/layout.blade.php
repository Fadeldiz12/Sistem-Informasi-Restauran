{{--
  Kerangka halaman error berbahasa Indonesia. Section: kode, judul, pesan, aksi (opsional).
--}}
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('judul') · {{ config('resto.nama') }}</title>
@include('partials.favicon')
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; margin: 0; }
  body { min-height: 100vh; display: grid; place-items: center; padding: 24px; background: #F8F5E6; color: #97173A; font: 400 15px/1.6 'Open Sans', sans-serif; text-align: center; }
  .box { max-width: 440px; }
  img { width: 84px; height: 84px; border-radius: 50%; margin: 0 auto 18px; display: block; }
  .kode { font: 700 64px/1 Poppins, sans-serif; }
  h1 { font: 700 24px Poppins, sans-serif; margin: 6px 0 10px; }
  p { opacity: .8; }
  .aksi { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin-top: 22px; }
  .aksi a, .aksi button { display: inline-block; padding: 12px 26px; border-radius: 999px; background: #97173A; color: #F8F5E6; font: 600 15px Poppins, sans-serif; text-decoration: none; border: 0; cursor: pointer; }
  .aksi .o { background: transparent; color: #97173A; box-shadow: inset 0 0 0 1.5px #97173A; }
</style>
</head>
<body>
  <div class="box">
    <img src="{{ asset('images/logo-192.png') }}" alt="Logo {{ config('resto.nama') }}">
    <div class="kode">@yield('kode')</div>
    <h1>@yield('judul')</h1>
    <p>@yield('pesan')</p>
    <div class="aksi">
      @hasSection('aksi')
        @yield('aksi')
      @else
        @auth
          <a href="{{ route(auth()->user()->homeRoute()) }}">Buka halaman saya</a>
        @else
          <a href="{{ route('login') }}">Ke halaman masuk</a>
        @endauth
      @endif
    </div>
  </div>
  @stack('scripts')
</body>
</html>
