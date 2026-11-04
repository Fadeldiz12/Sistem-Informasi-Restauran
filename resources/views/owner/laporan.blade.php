@extends('layouts.panel')

@section('title', 'Laporan Keuangan')
@section('crumb', 'Owner / Laporan Keuangan')
@section('heading', 'Laporan Keuangan')
@section('sub', 'Pendapatan, pengeluaran, dan laba berdasarkan periode')

@php
  $rp = fn ($n) => ($n < 0 ? '-' : '').'Rp '.number_format(abs((float) $n), 0, ',', '.');
  $q = ['dari' => $dari->toDateString(), 'sampai' => $sampai->toDateString()];
@endphp

@push('styles')
<style>
  .filter { padding: 18px 20px; background-image: linear-gradient(var(--m08) 1px, transparent 1px), linear-gradient(90deg, var(--m08) 1px, transparent 1px); background-size: 24px 24px; }
  .filter form { display: flex; gap: 14px; align-items: flex-end; flex-wrap: wrap; }
  .filter .tgl { width: 200px; }
  .filter .in input[type=date] { min-height: 46px; }
  .tbl-lap td, .tbl-lap th { text-align: right; } .tbl-lap td:first-child, .tbl-lap th:first-child { text-align: left; }
  .tbl-lap tr.nol td { opacity: .45; }
  .tbl-lap tr.total td { background: var(--m); color: var(--cr); font-weight: 800; border: none; }
  .tbl-lap tr.total td:first-child { border-radius: 14px 0 0 14px; } .tbl-lap tr.total td:last-child { border-radius: 0 14px 14px 0; }
  .rugi { color: #c0392b; }
  .tbl-lap tr.total .rugi { color: var(--cr); }
  .unduh { display: flex; gap: 8px; flex-wrap: wrap; }
  @media (max-width: 760px) {
    .filter .tgl { width: calc(50% - 7px); } .filter .sp { display: none; } .filter .btn.lg { width: 100%; }
    .filter .tgl .in { padding: 0 10px; } .filter .tgl .in > svg { display: none; }
    .unduh { width: 100%; } .unduh .btn { flex: 1; }
    .tbl-lap td, .tbl-lap th { padding-left: 6px; padding-right: 6px; font-size: 12.5px; }
  }
</style>
@endpush

@section('content')
  <div class="card filter">
    <form method="get" action="{{ route('owner.laporan') }}">
      <div class="tgl"><div class="field"><label for="dari">Dari tanggal</label><div class="in"><svg class="ic"><use href="#i-cal"/></svg><input type="date" name="dari" id="dari" value="{{ old('dari', $dari->toDateString()) }}" max="{{ today()->toDateString() }}" required></div></div></div>
      <div class="tgl"><div class="field"><label for="sampai">Sampai tanggal</label><div class="in"><svg class="ic"><use href="#i-cal"/></svg><input type="date" name="sampai" id="sampai" value="{{ old('sampai', $sampai->toDateString()) }}" required></div></div></div>
      <div class="field"><label>Periode cepat</label><div class="chips">
        <a @class(['on' => $cepat === 'hari']) href="{{ route('owner.laporan', ['cepat' => 'hari']) }}">Hari ini</a>
        <a @class(['on' => $cepat === '7hari']) href="{{ route('owner.laporan', ['cepat' => '7hari']) }}">7 hari</a>
        <a @class(['on' => $cepat === 'bulan']) href="{{ route('owner.laporan', ['cepat' => 'bulan']) }}">Bulan ini</a></div></div>
      <div class="sp" style="flex:1"></div>
      <button class="btn lg" type="submit"><svg class="ic"><use href="#i-search"/></svg>Tampilkan</button>
    </form>
    @if ($errors->any())<div class="alert" role="alert" style="margin:14px 0 0">{{ $errors->first() }}</div>@endif
  </div>

  <div class="grid g3" style="margin-top:18px">
    <div class="card stat f"><div class="top"><span class="eyebrow">Total pendapatan</span><span class="ico"><svg class="ic"><use href="#i-cash"/></svg></span></div><div class="v">{{ \App\Services\LaporanService::ringkas($pendapatan) }}</div><div class="t">Dari pembayaran berstatus Lunas</div></div>
    <div class="card stat b"><div class="top"><span class="eyebrow">Total pengeluaran</span><span class="ico"><svg class="ic"><use href="#i-wallet"/></svg></span></div><div class="v">{{ \App\Services\LaporanService::ringkas($pengeluaran) }}</div><div class="t">Pengeluaran operasional</div></div>
    <div class="stamp-out"><div class="stamp" style="padding:22px;height:100%"><div style="display:flex;justify-content:space-between"><span class="eyebrow">{{ $laba < 0 ? 'Rugi' : 'Laba' }}</span><svg class="ic"><use href="#i-chart"/></svg></div><div style="font:700 clamp(24px,2.2vw,32px) var(--f-title);margin:12px 0 6px">{{ \App\Services\LaporanService::ringkas($laba) }}</div><div style="font-size:12.5px;opacity:.72">Pendapatan − pengeluaran</div></div></div>
  </div>

  <div class="card" style="margin-top:18px"><div class="card-head"><div><h2>Laporan Keuangan · {{ $judulPeriode }}</h2><p>Rincian per tanggal · {{ $baris->count() }} hari</p></div>
      @if ($adaData)
        <div class="unduh"><a class="btn o" href="{{ route('owner.laporan.pdf', $q) }}"><svg class="ic"><use href="#i-file"/></svg>Unduh PDF</a><a class="btn" href="{{ route('owner.laporan.excel', $q) }}"><svg class="ic"><use href="#i-download"/></svg>Unduh Excel</a></div>
      @endif</div>
    @if (! $adaData)
      <div class="empty" style="padding:44px 16px"><svg class="ic" style="width:32px;height:32px;margin-bottom:8px"><use href="#i-file"/></svg><br>Tidak ada data pendapatan maupun pengeluaran pada periode {{ $judulPeriode }}.<br><small style="font-weight:500">Silakan pilih periode lain.</small></div>
    @else
      <div class="tbl-wrap"><table class="tbl-lap"><thead><tr><th>Tanggal</th><th>Pendapatan</th><th>Pengeluaran</th><th>Laba</th></tr></thead><tbody>
        @foreach ($baris as $b)
          <tr @class(['nol' => ! $b['pendapatan'] && ! $b['pengeluaran']])><td><b>{{ $b['tanggal']->translatedFormat('d M Y') }}</b></td><td>{{ $rp($b['pendapatan']) }}</td><td>{{ $rp($b['pengeluaran']) }}</td><td><b @class(['rugi' => $b['laba'] < 0])>{{ $rp($b['laba']) }}</b></td></tr>
        @endforeach
        <tr class="total"><td>TOTAL</td><td>{{ $rp($pendapatan) }}</td><td>{{ $rp($pengeluaran) }}</td><td @class(['rugi' => $laba < 0])>{{ $rp($laba) }}</td></tr>
      </tbody></table></div>
    @endif
    <div class="note" style="margin-top:14px"><svg class="ic"><use href="#i-info"/></svg><span>Pendapatan dihitung dari pembayaran berstatus Lunas pada tanggal pembayaran (pesanan yang dibatalkan tidak dihitung). Pengeluaran diambil dari menu Pengeluaran Operasional.</span></div>
  </div>
@endsection

@push('scripts')
<script>
  (function () {
    var d = document.getElementById('dari'), s = document.getElementById('sampai');
    function sinkron() { s.min = d.value; if (s.value && s.value < d.value) s.value = d.value; }
    d.addEventListener('change', sinkron); sinkron();
  })();
</script>
@endpush
