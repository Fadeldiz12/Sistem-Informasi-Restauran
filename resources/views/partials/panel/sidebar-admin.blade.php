@php
  $nav = [
    ['admin.dashboard', 'i-grid', 'Dashboard', null],
    ['admin.pengguna', 'i-users', 'Data Pengguna', $sidebarCount['pengguna'] ?? null],
    ['admin.menu', 'i-tag', 'Kategori & Menu', $sidebarCount['menu'] ?? null],
    ['admin.zona', 'i-map', 'Zona & Tarif', $sidebarCount['zona'] ?? null],
    ['admin.pengaturan', 'i-settings', 'Pengaturan Sistem', null],
  ];
@endphp
<aside class="side"><div class="side-in">
  <div class="brand"><span class="mk">R</span><div><b>SIM RESTO</b><small>PANEL ADMIN</small></div></div>
  <div class="nav-lbl">Menu utama</div>
  @foreach ($nav as [$route, $icon, $label, $badge])
    <a @class(['nav', 'on' => request()->routeIs($route.'*')]) href="{{ route($route) }}" title="{{ $label }}"><svg class="ic"><use href="#{{ $icon }}"/></svg><span>{{ $label }}</span>@if ($badge !== null)<span class="bd">{{ $badge }}</span>@endif</a>
  @endforeach
  <div class="spacer"></div>
  @include('partials.panel.hours')
  <button class="nav" data-open="mLogout" title="Logout"><svg class="ic"><use href="#i-logout"/></svg><span>Logout</span></button>
</div></aside>
