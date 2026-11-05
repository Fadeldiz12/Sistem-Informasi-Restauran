<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Akses Ditolak · {{ config('resto.nama') }}</title>
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
  a { display: inline-block; margin-top: 22px; padding: 12px 26px; border-radius: 999px; background: #97173A; color: #F8F5E6; font: 600 15px Poppins, sans-serif; text-decoration: none; }
</style>
</head>
<body>
  <div class="box">
    <img src="{{ asset('images/logo-192.png') }}" alt="Logo {{ config('resto.nama') }}">
    <div class="kode">403</div>
    <h1>Akses Ditolak</h1>
    <p>{{ $exception->getMessage() ?: 'Anda tidak memiliki akses ke halaman ini.' }}@auth Peran akun Anda saat ini: <b>{{ auth()->user()->roleLabel() }}</b>.@endauth</p>
    @auth
      <a href="{{ route(auth()->user()->homeRoute()) }}">Buka halaman saya</a>
    @else
      <a href="{{ route('login') }}">Masuk</a>
    @endauth
  </div>
</body>
</html>
