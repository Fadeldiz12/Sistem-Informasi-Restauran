@php $tab = $tab ?? 'tugas'; @endphp
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>@yield('title', 'Tugas Pengiriman') · Kurir · Resto Flow</title>
@include('partials.favicon')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Raleway:wght@600;700;800&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/kurir.css') }}?v={{ filemtime(public_path('css/kurir.css')) }}">
@stack('styles')
</head>
<body>
@include('partials.panel.icons')
<header class="hdr">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px">
    <div style="display:flex;gap:12px;align-items:center;min-width:0"><img src="{{ asset('images/logo-192.png') }}" alt="Logo Resto Flow" class="hdr-logo">
      <div style="min-width:0"><div class="eyebrow" style="opacity:.75">Kurir · Resto Flow</div><div class="hdr-nama">{{ auth()->user()->nama }}</div></div></div>
    <div style="display:flex;gap:8px;flex:none"><a class="icon-btn" href="{{ route('kurir.tugas') }}" style="box-shadow:inset 0 0 0 1.5px var(--cr)" aria-label="Tugas menunggu" title="Tugas menunggu"><svg class="ic"><use href="#i-bell"/></svg>@if ($jmlAntrean)<span class="dot" style="background:var(--bl);color:var(--m)">{{ $jmlAntrean }}</span>@endif</a><span class="av" style="width:44px;height:44px;background:var(--cr)">{{ auth()->user()->inisial() }}</span></div>
  </div>
  <div class="hdr-stat">
    <a href="{{ route('kurir.tugas') }}"><div class="eyebrow">Antrean</div><b>{{ $jmlAntrean }}</b></a>
    <a href="{{ route('kurir.status') }}"><div class="eyebrow">Diantar</div><b>{{ $jmlDiantar }}</b></a>
    <a href="{{ route('kurir.riwayat') }}" title="Selesai diantar hari ini"><div class="eyebrow">Selesai</div><b>{{ $jmlSelesai }}</b></a>
  </div>
</header>

<main class="wrapm">
  @if (session('error'))<div class="alert" role="alert"><svg class="ic"><use href="#i-alert"/></svg><span>{{ session('error') }}</span></div>@endif
  @if ($errors->any())<div class="alert" role="alert"><svg class="ic"><use href="#i-alert"/></svg><span>{{ $errors->first() }}</span></div>@endif
  @if (($tunaiDibawa ?? 0) > 0)
    <div class="note" style="margin-bottom:14px"><svg class="ic"><use href="#i-cash"/></svg><span>Uang tunai yang kamu bawa: <b>Rp {{ number_format($tunaiDibawa, 0, ',', '.') }}</b>. Setorkan ke kasir setelah kembali ke restoran.</span></div>
  @endif
  @yield('content')
</main>

<nav class="tabbar">
  <a @class(['on' => $tab === 'tugas']) href="{{ route('kurir.tugas') }}"><svg class="ic" style="width:21px;height:21px"><use href="#i-receipt"/></svg>Tugas</a>
  <a @class(['on' => $tab === 'status']) href="{{ route('kurir.status') }}"><span style="position:relative"><svg class="ic" style="width:21px;height:21px"><use href="#i-truck"/></svg>@if ($jmlDiantar)<i class="tb-dot">{{ $jmlDiantar }}</i>@endif</span>Status</a>
  <a @class(['on' => $tab === 'riwayat']) href="{{ route('kurir.riwayat') }}"><svg class="ic" style="width:21px;height:21px"><use href="#i-check"/></svg>Riwayat</a>
  <a href="#" data-logout><svg class="ic" style="width:21px;height:21px"><use href="#i-logout"/></svg>Keluar</a>
</nav>
<div class="toast" id="toast"><svg class="ic"><use href="#i-check"/></svg><span id="toastMsg"></span></div>
<script>
  // toast(m) untuk sukses, toast(m, 'warn') untuk peringatan (ikon tanda seru, warna berbeda, tampil lebih lama)
  function toast(m, jenis) {
    var t = document.getElementById('toast'), warn = jenis === 'warn';
    document.getElementById('toastMsg').textContent = m;
    t.querySelector('use').setAttribute('href', warn ? '#i-alert' : '#i-check');
    t.classList.toggle('warn', warn); t.classList.add('on');
    clearTimeout(window._t); window._t = setTimeout(function () { t.classList.remove('on'); }, warn ? 4200 : 2800);
  }
  @if (session('status')) toast(@json(session('status'))); @endif
  // tombol kirim form: cegah klik ganda
  document.addEventListener('submit', function (e) { var b = e.target.querySelector('button[type=submit]'); if (b && !e.defaultPrevented) setTimeout(function () { b.disabled = true; }, 0); });
</script>
@stack('scripts')
@include('partials.logout')
{{-- halaman dari cache Back/Forward dimuat ulang supaya sesi yang sudah keluar tidak menampilkan data --}}
<script>window.addEventListener('pageshow', function (e) { if (e.persisted) location.reload(); });</script>
</body>
</html>
