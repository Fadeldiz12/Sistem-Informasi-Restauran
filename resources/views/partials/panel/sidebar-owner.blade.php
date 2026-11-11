@php
  $nav = [
    ['owner.dashboard', 'i-chart', 'Dashboard Penjualan', 'Dashboard'],
    ['owner.laporan', 'i-file', 'Laporan Keuangan', 'Laporan'],
    ['owner.pengeluaran', 'i-wallet', 'Pengeluaran', 'Pengeluaran'],
    ['owner.pengguna', 'i-users', 'Pengguna & Role', 'Pengguna'],
    ['owner.aktivitas', 'i-lock', 'Log Aktivitas', 'Log'],
  ];
@endphp
<aside class="side"><div class="side-in">
  <div class="brand"><img class="mk" style="border-radius:50%;object-fit:cover" src="{{ asset('images/logo-192.png') }}" alt="Logo Resto Flow"><div><b>RESTO FLOW</b><small>PANEL OWNER</small></div></div>
  <div class="nav-lbl">Menu utama</div>
  @foreach ($nav as [$route, $icon, $label, $pendek])
    <a @class(['nav', 'on' => request()->routeIs($route) || request()->routeIs($route.'.*')]) href="{{ route($route) }}" title="{{ $label }}"><svg class="ic"><use href="#{{ $icon }}"/></svg><span class="nl">{{ $label }}</span><span class="ns">{{ $pendek }}</span></a>
  @endforeach
  <div class="spacer"></div>
  @include('partials.panel.hours')
  <button class="nav" data-open="mLogout" title="Logout"><svg class="ic"><use href="#i-logout"/></svg><span class="nl">Logout</span><span class="ns">Keluar</span></button>
</div></aside>
