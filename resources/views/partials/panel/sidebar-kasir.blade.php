@php
  $nav = [
    ['kasir.dashboard', 'i-grid', 'Dashboard', null, 'Beranda'],
    ['kasir.input', 'i-plus', 'Input Pesanan', null, 'Input'],
    ['kasir.online', 'i-bell', 'Pesanan Online', $sidebarCount['online'] ?: null, 'Online'],
    ['kasir.pembayaran', 'i-wallet', 'Pembayaran', $sidebarCount['pembayaran'] ?: null, 'Bayar'],
  ];
@endphp
<aside class="side"><div class="side-in">
  <div class="brand"><img class="mk" style="border-radius:50%;object-fit:cover" src="{{ asset('images/logo-192.png') }}" alt="Logo SIM Resto"><div><b>SIM RESTO</b><small>PANEL KASIR</small></div></div>
  <div class="nav-lbl">Menu utama</div>
  @foreach ($nav as [$route, $icon, $label, $badge, $pendek])
    <a @class(['nav', 'on' => request()->routeIs($route) || request()->routeIs($route.'.*')]) href="{{ route($route) }}" title="{{ $label }}"><svg class="ic"><use href="#{{ $icon }}"/></svg><span class="nl">{{ $label }}</span><span class="ns">{{ $pendek }}</span>@if ($badge !== null)<span class="bd">{{ $badge }}</span>@endif</a>
  @endforeach
  <div class="spacer"></div>
  @include('partials.panel.hours')
  <button class="nav" data-open="mLogout" title="Logout"><svg class="ic"><use href="#i-logout"/></svg><span class="nl">Logout</span><span class="ns">Keluar</span></button>
</div></aside>
