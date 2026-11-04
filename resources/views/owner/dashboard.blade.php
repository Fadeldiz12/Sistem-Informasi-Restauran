@extends('layouts.panel')

@use('App\Services\LaporanService')

@section('title', 'Dashboard Penjualan')
@section('crumb', 'Owner / Dashboard Penjualan')
@section('heading', 'Dashboard Penjualan')
@section('sub', 'Ringkasan penjualan · '.now()->translatedFormat('l, j F Y'))

@php
  $teratas = $terlaris->first();
  $layananTop = $layanan->sortByDesc('jumlah')->first();
  $ikonLayanan = ['dine_in' => 'i-dine', 'delivery' => 'i-truck', 'take_away' => 'i-bag'];
  $warna = ['dine_in' => 'f', 'delivery' => '', 'take_away' => 'b'];
@endphp

@push('styles')
<style>
  .seg a { display: inline-flex; align-items: center; height: 38px; padding: 0 14px; border-radius: 999px; box-shadow: inset 0 0 0 1.4px var(--m); font: 700 13px var(--f-accent); }
  .seg a.on { background: var(--m); color: var(--cr); box-shadow: none; }
  .stat .v.txt { font-size: clamp(19px, 1.6vw, 26px); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .terlaris .mod { padding: 12px 16px 16px; }
  .terlaris .mod .r { display: flex; gap: 12px; align-items: center; }
  .terlaris .mod .r span:nth-child(2) { flex: 1; min-width: 0; font: 700 13.5px var(--f-accent); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  #chart svg text { font-family: inherit; }
  @media (max-width: 760px) {
    .topbar { flex-wrap: wrap; }
    .topbar > div:first-child { flex: 1; min-width: 0; }
    .tb-right { display: contents; }
    .tb-right .seg { order: 3; width: 100%; flex-wrap: nowrap; }
    .tb-right .seg a { flex: 1; justify-content: center; padding: 0 8px; }
  }
</style>
@endpush

@section('topbar')
  <div class="seg">
    @foreach (LaporanService::PERIODE as $k => $l)
      <a @class(['on' => $periode === $k]) href="{{ route('owner.dashboard', ['periode' => $k]) }}">{{ $l }}</a>
    @endforeach
  </div>
@endsection

@section('content')
  <div class="grid g4">
    <div class="card stat f"><div class="top"><span class="eyebrow">Total penjualan</span><span class="ico"><svg class="ic"><use href="#i-cash"/></svg></span></div><div class="v">{{ LaporanService::ringkas($total) }}</div><div class="t">Dari pesanan berstatus lunas · {{ $label }}</div></div>
    <div class="card stat"><div class="top"><span class="eyebrow">Jumlah pesanan</span><span class="ico"><svg class="ic"><use href="#i-receipt"/></svg></span></div><div class="v">{{ number_format($jumlah, 0, ',', '.') }}</div><div class="t">Lunas</div></div>
    <div class="card stat b"><div class="top"><span class="eyebrow">Menu terlaris</span><span class="ico"><svg class="ic"><use href="#i-tag"/></svg></span></div>
      <div class="v txt" title="{{ $teratas['nama'] ?? '' }}">{{ $teratas['nama'] ?? '—' }}</div><div class="t">{{ $teratas ? $teratas['porsi'].' porsi' : 'Belum ada penjualan' }}</div></div>
    <div class="card stat p"><div class="top"><span class="eyebrow">Layanan terbanyak</span><span class="ico"><svg class="ic"><use href="#i-dine"/></svg></span></div>
      <div class="v txt">{{ $jumlah ? $layananTop['nama'] : '—' }}</div><div class="t">{{ $jumlah ? $layananTop['persen'].'% dari seluruh pesanan' : 'Belum ada penjualan' }}</div></div>
  </div>

  <div class="grid split" style="grid-template-columns:minmax(0,1.6fr) minmax(0,1fr);margin-top:18px;align-items:start">
    <div class="card"><div class="card-head"><div><h2>Grafik Penjualan</h2><p>{{ $sub }}</p></div><div class="pill m" style="font-size:11px">{{ $periode === 'harian' ? 'jam tersibuk' : ($periode === 'mingguan' ? 'hari tersibuk' : 'bulan tertinggi') }}</div></div>
      <div id="chart"></div>
      @if (! $jumlah)<p class="empty" style="padding:8px 0 0">Belum ada penjualan lunas {{ $label }}.</p>@endif
    </div>
    <div class="card"><div class="card-head"><h2>Menu Terlaris</h2><span class="pill">{{ $label }}</span></div>
      @if ($terlaris->isEmpty())
        <div class="empty">Belum ada menu terjual {{ $label }}.</div>
      @else
        <div class="mods terlaris">
          @foreach ($terlaris as $i => $m)
            <div @class(['mod', 'b' => $i % 2 === 1, 'c' => $i === 4]) style="z-index:{{ $i }}"><div class="r"><span style="font:700 22px var(--f-title)">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span><span title="{{ $m['nama'] }}">{{ $m['nama'] }}</span><b style="font:700 14px var(--f-title);white-space:nowrap">{{ $m['porsi'] }} porsi</b></div></div>
          @endforeach
        </div>
      @endif
    </div>
  </div>

  <div class="grid g3" style="margin-top:18px">
    @foreach ($layanan as $k => $l)
      <div class="card {{ $warna[$k] }}" style="padding:18px"><div style="display:flex;justify-content:space-between"><span style="display:flex;gap:8px;align-items:center;font:800 14px var(--f-accent)"><svg class="ic" style="width:18px;height:18px"><use href="#{{ $ikonLayanan[$k] }}"/></svg>{{ $l['nama'] }}</span><b style="font:700 22px var(--f-title)">{{ $l['persen'] }}%</b></div>
        <div @class(['bar', 'dark' => $warna[$k] !== 'f']) style="margin-top:12px"><i style="width:{{ $l['persen'] }}%"></i></div><div style="font-size:12.5px;opacity:.75;margin-top:8px">{{ $l['jumlah'] }} pesanan</div></div>
    @endforeach
  </div>
@endsection

@push('scripts')
<script>
  (function () {
    var G = @json($grafik), lab = G.label, val = G.nilai;
    var W = 760, H = 250, maks = Math.max.apply(null, val.concat([0]));
    // skala sumbu: rb / jt otomatis
    var unit = maks >= 1e6 ? [1e6, 'jt'] : (maks >= 1e3 ? [1e3, 'rb'] : [1, '']);
    var mx = (maks || unit[0]) * 1.15;
    var fmt = function (n) { var v = n / unit[0]; return (v >= 10 || v === 0 ? Math.round(v) : v.toFixed(1).replace('.', ',')) + (unit[1] && n ? ' ' + unit[1] : ''); };
    var n = val.length, slot = (W - 62) / n, bw = Math.min(46, slot * .62), s = '';
    [0, .25, .5, .75, 1].forEach(function (g) {
      var y = H - 28 - g * (H - 50);
      s += '<line x1="56" x2="' + W + '" y1="' + y + '" y2="' + y + '" stroke="#97173A" stroke-opacity=".12"/><text x="50" y="' + (y + 4) + '" font-size="11" fill="#97173A" opacity=".6" text-anchor="end" font-family="Open Sans">' + (maks || !g ? fmt(mx * g) : '') + '</text>';
    });
    var top = val.slice().sort(function (a, b) { return b - a; }).slice(0, 2).filter(function (v) { return v > 0; });
    val.forEach(function (v, i) {
      var x = 62 + i * slot + (slot - bw) / 2, h = v / mx * (H - 50);
      s += '<rect x="' + x + '" y="' + (H - 28 - h) + '" width="' + bw + '" height="' + Math.max(h, v > 0 ? 2 : 0) + '" rx="' + Math.min(10, bw / 3) + '" fill="' + (top.indexOf(v) >= 0 ? '#97173A' : '#B2C2D2') + '"><title>' + lab[i] + ': ' + rp(v) + '</title></rect>'
        + '<text x="' + (x + bw / 2) + '" y="' + (H - 8) + '" font-size="11.5" font-weight="700" fill="#97173A" text-anchor="middle" font-family="Raleway">' + lab[i] + '</text>';
    });
    document.getElementById('chart').innerHTML = '<svg viewBox="0 0 ' + W + ' ' + H + '" style="width:100%;display:block" role="img" aria-label="Grafik penjualan">' + s + '</svg>';
  })();
</script>
@endpush
