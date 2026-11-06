@extends('layouts.panel')

@use('App\Models\Pesanan')
@use('App\Models\Pembayaran')

@section('title', 'Dashboard Kasir')
@section('crumb', 'Kasir / Dashboard')
@section('heading', 'Dashboard Kasir')
@section('sub', 'Pesanan hari ini, konfirmasi, dan pembayaran')

@php
  $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
  $totalHariIni = $perLayanan->sum();
  $tunai = $belumLunas->filter(fn ($p) => ($p->pembayaran?->metode ?? Pembayaran::METODE_TUNAI) === Pembayaran::METODE_TUNAI)->count();
  $nonTunai = $belumLunas->count() - $tunai;
  $pemasukanLabel = $pemasukan >= 1000000 ? 'Rp '.rtrim(rtrim(number_format($pemasukan / 1000000, 2, ',', '.'), '0'), ',').' jt' : $rp($pemasukan);
  $statusTeks = fn ($p) => $p->status === Pesanan::STATUS_DIPROSES && $p->tiketDapur?->status_masak === \App\Models\TiketDapur::STATUS_DIMASAK ? 'Sedang Dimasak' : $p->statusLabel();
@endphp

@push('styles')
<style>
  .kasir-atas .btn.lg { white-space: normal; text-align: left; height: auto; min-height: 52px; padding-top: 10px; padding-bottom: 10px; }
  .kasir-atas .btn.lg .pill { flex: none; }
  .notif-row { display: flex; gap: 12px; align-items: center; padding: 11px 0; border-bottom: 1px solid var(--m15); }
  .notif-row b { display: block; font: 600 13.5px/1.35 var(--f-title); }
</style>
@endpush

@section('content')
  <div class="grid g4">
    <div class="card stat f"><div class="top"><span class="eyebrow">Pesanan hari ini</span><span class="ico"><svg class="ic"><use href="#i-receipt"/></svg></span></div><div class="v">{{ $totalHariIni }}</div>
      <div class="t">Dine In {{ $perLayanan[Pesanan::LAYANAN_DINE_IN] ?? 0 }} · Take Away {{ $perLayanan[Pesanan::LAYANAN_TAKE_AWAY] ?? 0 }} · Delivery {{ $perLayanan[Pesanan::LAYANAN_DELIVERY] ?? 0 }}</div></div>
    <div class="card stat b"><div class="top"><span class="eyebrow">Menunggu konfirmasi</span><span class="ico"><svg class="ic"><use href="#i-bell"/></svg></span></div><div class="v">{{ $menunggu }}</div><div class="t">Pesanan online baru</div></div>
    <div class="card stat "><div class="top"><span class="eyebrow">Belum lunas</span><span class="ico"><svg class="ic"><use href="#i-wallet"/></svg></span></div><div class="v">{{ $belumLunas->count() }}</div><div class="t">Tunai {{ $tunai }} · Transfer/QRIS {{ $nonTunai }}</div></div>
    <div class="card stat p"><div class="top"><span class="eyebrow">Pemasukan hari ini</span><span class="ico"><svg class="ic"><use href="#i-cash"/></svg></span></div><div class="v">{{ $pemasukanLabel }}</div><div class="t">{{ $jumlahLunas }} pembayaran lunas</div></div>
  </div>

  <div class="grid split kasir-atas" style="grid-template-columns:minmax(0,1fr) minmax(0,1fr);margin-top:18px;align-items:start">
      <div class="card f"><div class="card-head" style="margin-bottom:12px"><h2>Aksi Cepat</h2></div><div style="display:grid;gap:10px">
        <a class="btn cr lg" href="{{ route('kasir.input') }}" style="justify-content:flex-start"><svg class="ic"><use href="#i-plus"/></svg><span style="flex:1;text-align:left">Input Pesanan di Tempat</span><svg class="ic"><use href="#i-arr"/></svg></a>
        <a class="btn b lg" href="{{ route('kasir.online') }}" style="justify-content:flex-start"><svg class="ic"><use href="#i-bell"/></svg><span style="flex:1;text-align:left">Konfirmasi Pesanan Online</span><span class="pill m">{{ $menunggu }}</span></a>
        <a class="btn lg" href="{{ route('kasir.pembayaran') }}" style="justify-content:flex-start;box-shadow:inset 0 0 0 1.5px var(--cr)"><svg class="ic"><use href="#i-wallet"/></svg><span style="flex:1;text-align:left">Proses Pembayaran</span><span class="pill c">{{ $belumLunas->count() }}</span></a></div></div>
      <div class="card"><div class="card-head" style="margin-bottom:4px"><h2>Notifikasi</h2><span class="pill b">{{ $notif->count() }} terbaru</span></div>
        @forelse ($notif as $n)
          <a href="{{ $n['url'] }}" class="notif-row"><span @class(['av', 'm' => $n['m']])><svg class="ic" style="width:16px;height:16px"><use href="#{{ $n['ikon'] }}"/></svg></span><div style="flex:1;min-width:0"><b>{{ $n['judul'] }}</b><div style="font-size:12px;opacity:.65">{{ $n['ket'] }}</div></div><span style="font-size:12px;opacity:.6;white-space:nowrap">{{ $n['waktu']?->diffForHumans(short: true) }}</span></a>
        @empty
          <div class="empty" style="padding:20px 8px">Belum ada notifikasi.</div>
        @endforelse
      </div>
  </div>

  <div style="margin-top:18px">
  <div class="card" id="berjalan"><div class="card-head"><h2>Pesanan Berjalan</h2>
      <div class="chips" data-filter-for="tbJalan"><button class="on" data-f="all">Semua</button>@foreach (Pesanan::LABEL_LAYANAN as $k => $l)<button data-f="{{ $k }}">{{ $l }}</button>@endforeach</div></div>
      <div class="tbl-wrap"><table class="tbl-stack"><thead><tr><th>No.</th><th>Pelanggan · layanan</th><th>Total</th><th>Status</th><th>Bayar</th><th></th></tr></thead><tbody id="tbJalan">
        @forelse ($berjalan as $p)
          <tr data-f="{{ $p->jenis_layanan }}"><td><b>{{ $p->kode() }}</b><div style="font-size:12px;opacity:.6">{{ $p->waktu_pesan->format('H:i') }}</div></td>
            <td>{{ $p->namaPemesan() }}<div style="font-size:12px;opacity:.65">{{ $p->layananDetail() }}</div></td>
            <td>{{ $rp($p->total) }}</td>
            <td><span class="pill {{ $p->statusPill() }}">{{ $statusTeks($p) }}</span></td>
            <td><span @class(['pill', 'm' => $p->isLunas(), 'b' => ! $p->isLunas()])>{{ $p->bayarLabel() }}</span></td>
            <td style="text-align:right">
              @if ($p->status === Pesanan::STATUS_SIAP && ! $p->isDelivery() && $p->isLunas())
                <form method="post" action="{{ route('kasir.selesai', $p) }}">@csrf<button class="btn sm" type="submit"><svg class="ic" style="width:15px;height:15px"><use href="#i-check"/></svg>Selesai</button></form>
              @elseif (! $p->isLunas() && ! $p->isDelivery() && $p->status !== Pesanan::STATUS_MENUNGGU && ! $p->pembayaran?->isOtomatis())
                <a class="btn o sm" href="{{ route('kasir.pembayaran', ['pesanan' => $p->id]) }}">Bayar</a>
              @elseif ($p->status === Pesanan::STATUS_MENUNGGU && $p->pembayaran?->metode === Pembayaran::METODE_TUNAI)
                <a class="btn o sm" href="{{ route('kasir.online', ['pesanan' => $p->id]) }}">Konfirmasi</a>
              @endif
            </td></tr>
        @empty
          <tr><td colspan="6"><div class="empty">Tidak ada pesanan yang sedang berjalan.</div></td></tr>
        @endforelse
      </tbody></table></div></div>
  </div>
@endsection

@push('scripts')
<script>setTimeout(function () { if (!document.querySelector('.ovl.on')) location.reload(); }, 60000);</script>
@endpush
