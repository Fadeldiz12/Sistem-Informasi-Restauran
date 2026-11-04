@php
  $nav = [
    ['kasir.dashboard', 'i-grid', 'Dashboard', null],
    ['kasir.input', 'i-plus', 'Input Pesanan', null],
    ['kasir.online', 'i-bell', 'Pesanan Online', $sidebarCount['online'] ?: null],
    ['kasir.pembayaran', 'i-wallet', 'Pembayaran', $sidebarCount['pembayaran'] ?: null],
  ];
@endphp
<aside class="side"><div class="side-in">
  <div class="brand"><span class="mk">R</span><div><b>SIM RESTO</b><small>PANEL KASIR</small></div></div>
  <div class="nav-lbl">Menu utama</div>
  @foreach ($nav as [$route, $icon, $label, $badge])
    <a @class(['nav', 'on' => request()->routeIs($route) || request()->routeIs($route.'.*')]) href="{{ route($route) }}" title="{{ $label }}"><svg class="ic"><use href="#{{ $icon }}"/></svg><span>{{ $label }}</span>@if ($badge !== null)<span class="bd">{{ $badge }}</span>@endif</a>
  @endforeach
  <div class="spacer"></div>
  @include('partials.panel.hours')
  <button class="nav" data-open="mLogout" title="Logout"><svg class="ic"><use href="#i-logout"/></svg><span>Logout</span></button>
</div></aside>
