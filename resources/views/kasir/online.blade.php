@extends('layouts.panel')

@use('App\Models\Pembayaran')

@section('title', 'Pesanan Online')
@section('error-sendiri', '1')
@section('crumb', 'Kasir / Pesanan Online')
@section('heading', 'Konfirmasi Pesanan Online')
@section('sub', 'Pesanan online bayar tunai · VA & QRIS otomatis masuk dapur setelah lunas')

@php
  $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
  $bayarPill = fn ($p) => match ($p->bayarLabel()) { 'Menunggu Verifikasi' => 'c', 'Bukti Ditolak' => 'p', 'Belum Bayar' => 'p', default => 'b' };
  $bayar = $pesanan?->pembayaran;
  $bisaTerima = (bool) $bayar;
@endphp

@push('styles')
<style>
  .qi { display: block; width: 100%; text-align: left; padding: 14px; border-radius: 16px; margin-bottom: 8px; box-shadow: inset 0 0 0 1.2px var(--m15); }
  .qi:hover { box-shadow: inset 0 0 0 1.5px var(--m); } .qi.on { background: var(--m); color: var(--cr); box-shadow: none; }
  .btn[disabled] { opacity: .45; cursor: not-allowed; }
</style>
@endpush

@section('content')
  @if ($errors->any())<div class="alert" role="alert">{{ $errors->first() }}</div>@endif

  @if ($antrean->isEmpty())
    <div class="card"><div class="empty" style="padding:50px 16px"><svg class="ic" style="width:34px;height:34px;margin-bottom:10px"><use href="#i-check"/></svg><br>Tidak ada pesanan online yang menunggu konfirmasi.<br><small style="font-weight:500">Pesanan VA &amp; QRIS otomatis masuk dapur setelah dibayar.</small></div></div>
  @else
  <div class="grid split" style="grid-template-columns:minmax(260px,.32fr) minmax(0,1fr);align-items:start">
    <div class="card" style="padding:16px"><div class="card-head" style="margin-bottom:6px"><h2>Menunggu Konfirmasi</h2><span class="pill m">{{ $antrean->count() }}</span></div><p style="font-size:12.5px;opacity:.65;margin-bottom:12px">Pesanan online dari pelanggan · urut paling lama</p>
      @foreach ($antrean as $p)
        <a @class(['qi', 'on' => $pesanan->id === $p->id]) href="{{ route('kasir.online', ['pesanan' => $p->id]) }}"><div style="display:flex;justify-content:space-between;align-items:center"><b style="font:700 14px var(--f-title)">{{ $p->kode() }}</b><span style="font-size:12px;opacity:.75;display:flex;gap:5px;align-items:center"><svg class="ic" style="width:13px;height:13px"><use href="#i-clock"/></svg>{{ $p->waktu_pesan->format('H:i') }}</span></div>
          <div style="font:700 13.5px var(--f-accent);margin-top:6px">{{ $p->namaPemesan() }}</div><div style="display:flex;justify-content:space-between;gap:8px;margin-top:6px;align-items:center"><span style="font-size:12px;opacity:.8">{{ $p->layananDetail() }}</span><span class="pill {{ $bayarPill($p) }}" style="font-size:10.5px;padding:3px 9px">{{ $p->bayarLabel() }}</span></div></a>
      @endforeach
    </div>

    <div class="card" style="padding:0;overflow:hidden">
      <div class="stripes" style="padding:22px 24px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
        <div class="stamp-out"><div class="stamp" style="padding:18px 24px"><div class="eyebrow">Detail pesanan online</div><div style="font:700 26px var(--f-title);margin-top:4px">{{ $pesanan->kode() }}</div><div style="font-size:12.5px;opacity:.7">Masuk {{ $pesanan->waktu_pesan->format('H:i') }} · {{ $pesanan->waktu_pesan->translatedFormat('l, j M Y') }}</div></div></div>
        <div style="display:grid;gap:8px;justify-items:end"><span class="pill b">Menunggu Konfirmasi</span><span class="pill m">@if ($pesanan->isDelivery())<svg class="ic" style="width:13px;height:13px"><use href="#i-truck"/></svg>@endif{{ $pesanan->layananDetail() }}</span></div></div>
      <div class="grid split" style="grid-template-columns:minmax(0,1.2fr) minmax(0,1fr);padding:20px 24px">
        <div><div class="eyebrow" style="margin-bottom:6px">Menu dipesan</div><table class="plain" style="width:100%">
          @foreach ($pesanan->detail as $d)
            <tr><td><b>{{ $d->menu->nama_menu }}</b><div style="font-size:12px;opacity:.6">Catatan: {{ $d->catatan ?: '-' }}</div></td><td>{{ $d->jumlah }}×</td><td style="text-align:right"><b>{{ $rp($d->subtotal) }}</b></td></tr>
          @endforeach
          </table>
          <div style="display:grid;gap:6px;margin-top:12px;padding:14px;border-radius:16px;background:var(--pp);font-size:13.5px"><div style="display:flex;justify-content:space-between"><span>Subtotal</span><span>{{ $rp($pesanan->subtotal) }}</span></div><div style="display:flex;justify-content:space-between"><span>Pajak + biaya layanan</span><span>{{ $rp($pesanan->pajakDanLayanan()) }}</span></div>
            @if ($pesanan->isDelivery())<div style="display:flex;justify-content:space-between"><span>Ongkos kirim · {{ $pesanan->pengiriman?->zona?->nama_zona }}</span><span>{{ $rp($pesanan->ongkir) }}</span></div>@endif
            <div style="display:flex;justify-content:space-between;padding-top:8px;border-top:1.5px dashed var(--m60);font:700 20px var(--f-title)"><span>Total</span><span>{{ $rp($pesanan->total) }}</span></div></div></div>
        <div class="grid" style="align-content:start;gap:12px">
          <div class="card soft" style="padding:14px"><div class="eyebrow" style="font-size:10px;margin-bottom:8px">Pelanggan{{ $pesanan->isDelivery() ? ' & alamat' : '' }}</div><div class="who"><span class="av">{{ $pesanan->pelanggan->inisial() }}</span><div><b>{{ $pesanan->pelanggan->nama }}</b><div style="font-size:12.5px;opacity:.7">{{ $pesanan->pelanggan->no_hp }}</div></div></div>
            @if ($pesanan->isDelivery())<div style="display:flex;gap:8px;margin-top:10px;font-size:13px"><svg class="ic" style="width:16px;height:16px;flex:none"><use href="#i-pin"/></svg>{{ $pesanan->pengiriman?->alamat }} · {{ $pesanan->pengiriman?->zona?->nama_zona }}</div>
            @elseif ($pesanan->nomor_meja)<div style="display:flex;gap:8px;margin-top:10px;font-size:13px"><svg class="ic" style="width:16px;height:16px"><use href="#i-dine"/></svg>Meja {{ $pesanan->nomor_meja }}</div>@endif</div>
          <div class="card b" style="padding:14px"><div style="display:flex;justify-content:space-between;align-items:center"><span class="eyebrow" style="font-size:10px">Status pembayaran</span><span class="pill ">{{ $pesanan->bayarLabel() }}</span></div>
            @if ($bayar)
              <div style="display:flex;gap:12px;margin-top:10px;align-items:center">
                <div style="width:52px;height:52px;border-radius:12px;background:var(--cr);display:grid;place-items:center;box-shadow:inset 0 0 0 1.2px var(--m);flex:none"><svg class="ic" style="width:22px;height:22px"><use href="#i-cash"/></svg></div>
                <div><b style="font:600 13.5px var(--f-title)">{{ $bayar->metodeLabel() }}</b>
                  <div style="font-size:12px;opacity:.75">Dibayar saat {{ $pesanan->isDelivery() ? 'diantar kurir' : 'diambil di kasir' }} · proses di menu Pembayaran</div></div></div>
            @else
              <p style="font-size:13px;margin-top:10px;opacity:.8">Pelanggan belum memilih metode pembayaran. Pesanan bisa dikonfirmasi setelah metode dipilih.</p>
            @endif
          </div>
        </div>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:16px 24px 22px;border-top:1.5px solid var(--m15)">
        <span style="font-size:12.5px;opacity:.7;display:flex;gap:8px;align-items:center"><svg class="ic" style="width:16px;height:16px"><use href="#i-info"/></svg>Status pesanan terlihat langsung di halaman lacak pelanggan</span>
        <div style="display:flex;gap:10px"><button class="btn o" data-open="mTolak"><svg class="ic"><use href="#i-x"/></svg>Tolak</button>
          <form method="post" action="{{ route('kasir.online.terima', $pesanan) }}">@csrf<button class="btn" type="submit" @disabled(! $bisaTerima)><svg class="ic"><use href="#i-check"/></svg>Konfirmasi &amp; Kirim ke Dapur</button></form></div></div>
    </div>
  </div>
  @endif
@endsection

@push('modals')
@if ($pesanan)
<div class="ovl" id="mTolak"><form class="modal" style="width:min(480px,100%)" method="post" action="{{ route('kasir.online.tolak', $pesanan) }}">
  @csrf
  <div class="mh"><div><div class="eyebrow" style="opacity:.7">{{ $pesanan->kode() }}</div><h3>Tolak Pesanan</h3></div><button type="button" class="icon-btn" data-close><svg class="ic"><use href="#i-x"/></svg></button></div>
  <div class="mb"><p style="font-size:14px">Tolak pesanan <b>{{ $pesanan->kode() }}</b> dari <b>{{ $pesanan->namaPemesan() }}</b>?</p>
    <div class="note" style="margin-top:14px"><svg class="ic"><use href="#i-info"/></svg><span>Status pesanan menjadi <b>Dibatalkan</b> dan langsung terlihat di halaman lacak pesanan pelanggan.</span></div></div>
  <div class="mf"><button type="button" class="btn o" data-close>Batal</button><button class="btn" type="submit">Tolak Pesanan</button></div></form></div>
@endif
@endpush

@push('scripts')
<script>setTimeout(function () { if (!document.querySelector('.ovl.on')) location.reload(); }, 30000);</script>
@endpush
