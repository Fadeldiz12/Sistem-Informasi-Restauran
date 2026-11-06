@php
  $set = \App\Models\PengaturanSistem::aktif();
  $radiusMaks = \App\Models\ZonaPengiriman::max('radius_km');
@endphp
<div class="topbar"><div class="wrap">
  <span><svg class="ic"><use href="#i-clock"/></svg>{{ $set->sedangBuka() ? 'Buka hari ini' : 'Tutup · buka' }} {{ $set->jamLabel() }}@if ($radiusMaks) · Delivery hingga {{ rtrim(rtrim(number_format((float) $radiusMaks, 1, ',', '.'), '0'), ',') }} km dari restoran @endif</span>
  <nav><a href="{{ route('pelanggan.tracking') }}"><svg class="ic"><use href="#i-receipt"/></svg>Lacak Pesanan</a><a href="{{ route('pelanggan.beranda') }}#menu"><svg class="ic"><use href="#i-dine"/></svg>Menu</a></nav>
</div></div>
