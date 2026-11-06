@php
  $nav = [
    ['kasir.dashboard', 'i-grid', 'Dashboard', $sidebarCount['siap'] ?: null, 'Beranda'],
    ['kasir.input', 'i-plus', 'Input Pesanan', null, 'Input'],
    ['kasir.online', 'i-bell', 'Pesanan Online', $sidebarCount['online'] ?: null, 'Online'],
    ['kasir.pembayaran', 'i-wallet', 'Pembayaran', $sidebarCount['pembayaran'] ?: null, 'Bayar'],
    ['kasir.setoran', 'i-cash', 'Setoran Kurir', $sidebarCount['setoran'] ?: null, 'Setoran'],
    ['kasir.eod', 'i-lock', 'Tutup Hari (EOD)', null, 'EOD'],
  ];
@endphp
<aside class="side"><div class="side-in">
  <div class="brand"><img class="mk" style="border-radius:50%;object-fit:cover" src="{{ asset('images/logo-192.png') }}" alt="Logo Resto Flow"><div><b>RESTO FLOW</b><small>PANEL KASIR</small></div></div>
  <div class="nav-lbl">Menu utama</div>
  @foreach ($nav as [$route, $icon, $label, $badge, $pendek])
    <a @class(['nav', 'on' => request()->routeIs($route) || request()->routeIs($route.'.*')]) href="{{ route($route) }}{{ $route === 'kasir.dashboard' ? '#berjalan' : '' }}" title="{{ $label }}{{ $route === 'kasir.dashboard' ? ' · pesanan siap diserahkan' : '' }}" @if ($route === 'kasir.dashboard') data-badge-siap @endif><svg class="ic"><use href="#{{ $icon }}"/></svg><span class="nl">{{ $label }}</span><span class="ns">{{ $pendek }}</span>@if ($badge !== null || $route === 'kasir.dashboard')<span class="bd" @if ($badge === null) hidden @endif>{{ $badge }}</span>@endif</a>
  @endforeach
  <div class="spacer"></div>
  @include('partials.panel.hours')
  <button class="nav" data-open="mLogout" title="Logout"><svg class="ic"><use href="#i-logout"/></svg><span class="nl">Logout</span><span class="ns">Keluar</span></button>
</div></aside>
@push('scripts')
<script>
  /* Pesanan siap dari dapur: dicek tiap 15 detik di semua halaman kasir; pesanan baru siap → toast + bunyi singkat (QA A3-#14) */
  (function () {
    var KUNCI = 'kasir-siap-dikenal', badge = document.querySelector('[data-badge-siap] .bd');
    function dikenal() { try { return JSON.parse(sessionStorage.getItem(KUNCI) || 'null'); } catch (_) { return null; } }
    function bunyi() {
      try {
        var C = window.AudioContext || window.webkitAudioContext; if (!C) return;
        var c = new C(), o = c.createOscillator(), g = c.createGain();
        o.frequency.value = 880; o.connect(g); g.connect(c.destination);
        g.gain.setValueAtTime(.18, c.currentTime); g.gain.exponentialRampToValueAtTime(.001, c.currentTime + .6);
        o.start(); o.stop(c.currentTime + .6);
      } catch (_) {}
    }
    function cek() {
      fetch(@json(route('kasir.notifikasi')), { headers: { 'Accept': 'application/json' } }).then(function (r) { return r.ok ? r.json() : null; }).then(function (j) {
        if (!j) return;
        if (badge) { badge.textContent = j.siap; badge.hidden = !j.siap; }
        var lama = dikenal(), ids = j.pesanan.map(function (p) { return p.id; });
        if (lama) {
          var baru = j.pesanan.filter(function (p) { return lama.indexOf(p.id) < 0; });
          if (baru.length) { toast(baru.map(function (p) { return p.kode; }).join(', ') + ' siap diserahkan · ' + baru[0].layanan); bunyi(); }
        }
        try { sessionStorage.setItem(KUNCI, JSON.stringify(ids)); } catch (_) {}
      }).catch(function () {});
    }
    cek(); setInterval(cek, 15000);
  })();
</script>
@endpush
