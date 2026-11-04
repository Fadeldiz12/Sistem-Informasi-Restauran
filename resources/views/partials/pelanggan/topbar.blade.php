@php
  $set = \App\Models\PengaturanSistem::aktif();
  $zonaNama = \App\Models\ZonaPengiriman::orderBy('radius_km')->pluck('nama_zona');
@endphp
<div class="topbar"><div class="wrap">
  <span><svg class="ic"><use href="#i-clock"/></svg>{{ $set->sedangBuka() ? 'Buka hari ini' : 'Tutup · buka' }} {{ $set->jamLabel() }}@if ($zonaNama->isNotEmpty()) · Delivery tersedia untuk {{ $zonaNama->map(fn ($z) => preg_replace('/^zona\s*/i', '', $z))->join(', ', ' & ') }}@endif</span>
  <nav><a href="{{ route('pelanggan.tracking') }}"><svg class="ic"><use href="#i-receipt"/></svg>Lacak Pesanan</a><a href="{{ route('pelanggan.beranda') }}#menu"><svg class="ic"><use href="#i-dine"/></svg>Menu</a></nav>
</div></div>
