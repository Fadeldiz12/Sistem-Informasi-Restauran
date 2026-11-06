@extends('layouts.pelanggan')

@section('title', 'Checkout')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<style>
  /* =========================================================
     PALET & FONT (sama dengan beranda)
     Poppins: judul, harga, tombol | Raleway: logo, navigasi, label | Open Sans: isi
     ========================================================= */
  :root {
    --m: #97173A; --cr: #F8F5E6; --bl: #B2C2D2; --pp: #E7E0D6; --dk: #282828;
    --m15: rgba(151, 23, 58, .15); --m60: rgba(151, 23, 58, .65);
    --f-title: 'Poppins', sans-serif; --f-accent: 'Raleway', sans-serif; --f-body: 'Open Sans', sans-serif;
    --gutter: clamp(20px, 4vw, 72px);
    --wrap: min(1360px, 100% - var(--gutter) * 2);   /* lebar mengikuti laptop */
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: var(--cr); color: var(--m); font: 400 15px/1.55 var(--f-body); -webkit-font-smoothing: antialiased; }
  a { color: inherit; text-decoration: none; }
  img { display: block; max-width: 100%; }
  button, input, textarea { font: inherit; color: inherit; }
  button { background: none; border: 0; cursor: pointer; }
  .wrap { width: var(--wrap); margin-inline: auto; }
  .ic { width: 18px; height: 18px; flex: none; fill: none; stroke: currentColor; stroke-width: 1.9; stroke-linecap: round; stroke-linejoin: round; }

  .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 50px; padding: 0 26px;
         border-radius: 999px; background: var(--m); color: var(--cr); font: 600 15px var(--f-title); white-space: nowrap; transition: .2s; }
  .btn:hover { background: #7f1331; }
  .btn.o { background: transparent; color: var(--m); box-shadow: inset 0 0 0 1.5px var(--m); }
  .btn.o:hover { background: var(--pp); }
  .btn.b { background: var(--bl); color: var(--m); }
  .btn.cr { background: var(--cr); color: var(--m); }
  .btn.blk { display: flex; width: 100%; }
  .btn.sm { height: 40px; padding: 0 18px; font-size: 14px; }
  .pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 999px; font: 700 12px var(--f-accent); white-space: nowrap; }
  .pill.b { background: var(--bl); color: var(--m); } .pill.m { background: var(--m); color: var(--cr); } .pill.o { box-shadow: inset 0 0 0 1.3px var(--m); }
  .eyebrow { font: 800 11.5px var(--f-accent); letter-spacing: .16em; text-transform: uppercase; }
  .stripes { background: repeating-linear-gradient(90deg, var(--bl) 0 17px, var(--cr) 17px 34px); }
  .stamp { --r: 6px; background: var(--cr); color: var(--m);
           -webkit-mask: radial-gradient(var(--r) at var(--r) var(--r), #0000 97%, #000) calc(-1 * var(--r)) calc(-1 * var(--r)) / calc(3 * var(--r)) calc(3 * var(--r)) round, linear-gradient(#000 0 0) 50% / calc(100% - 3 * var(--r)) calc(100% - 3 * var(--r)) no-repeat; }
  .stamp-out { filter: drop-shadow(1.3px 0 0 var(--m)) drop-shadow(-1.3px 0 0 var(--m)) drop-shadow(0 1.3px 0 var(--m)) drop-shadow(0 -1.3px 0 var(--m)); }

  /* top bar & header */
  .topbar { background: var(--m); color: var(--cr); font: 600 12.5px var(--f-accent); letter-spacing: .03em; }
  .topbar .wrap { display: flex; justify-content: space-between; align-items: center; height: 38px; gap: 16px; }
  .topbar span, .topbar a { display: inline-flex; align-items: center; gap: 8px; }
  .topbar .ic { width: 15px; height: 15px; }
  .topbar nav { display: flex; gap: 22px; }
  header { position: sticky; top: 0; z-index: 50; background: var(--cr); border-bottom: 1.5px solid var(--m15); }
  header .wrap { display: flex; align-items: center; gap: clamp(16px, 2.4vw, 40px); height: 76px; }
  .logo { display: flex; flex-direction: column; line-height: 1; }
  .logo b { font: 800 22px var(--f-accent); letter-spacing: .08em; }
  .logo small { font: 600 9px var(--f-accent); letter-spacing: .3em; margin-top: 5px; opacity: .75; }
  .mainnav { display: flex; gap: clamp(14px, 1.8vw, 30px); font: 700 14.5px var(--f-accent); flex: 1; }
  .mainnav a { padding: 6px 0; } .mainnav a:hover { box-shadow: 0 2.5px 0 var(--m); }
  .hd-right { display: flex; align-items: center; gap: 12px; margin-left: auto; }
  .icon-btn { width: 44px; height: 44px; border-radius: 50%; display: grid; place-items: center; box-shadow: inset 0 0 0 1.5px var(--m); }
  .user { display: flex; align-items: center; gap: 10px; font: 700 14px var(--f-accent); padding: 4px 14px 4px 4px; border-radius: 999px; box-shadow: inset 0 0 0 1.5px var(--m); }
  .av { width: 36px; height: 36px; border-radius: 50%; background: var(--bl); display: grid; place-items: center; font: 700 13px var(--f-title); }
  .cart { height: 44px; padding: 0 18px 0 14px; gap: 10px; }
  .cart .count { background: var(--cr); color: var(--m); border-radius: 999px; font: 700 12px var(--f-title); padding: 1px 8px; }

  /* judul halaman & langkah */
  .page-head { padding: clamp(28px, 3vw, 44px) 0 clamp(22px, 2.4vw, 32px); display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; flex-wrap: wrap; }
  .crumb { font: 600 13px var(--f-accent); opacity: .7; display: flex; gap: 8px; align-items: center; }
  .page-head h1 { font: 700 clamp(30px, 2.8vw, 42px) / 1.1 var(--f-title); margin-top: 8px; }
  .steps { display: flex; align-items: center; gap: 10px; }
  .steps .s { display: flex; align-items: center; gap: 10px; font: 700 13.5px var(--f-accent); white-space: nowrap; }
  .steps .n { width: 32px; height: 32px; border-radius: 50%; display: grid; place-items: center; font: 700 13px var(--f-title); box-shadow: inset 0 0 0 1.5px var(--m); }
  .steps .s.done .n { background: var(--m); color: var(--cr); box-shadow: none; }
  .steps .s.now .n { background: var(--bl); box-shadow: 0 0 0 5px rgba(178, 194, 210, .45), inset 0 0 0 1.5px var(--m); }
  .steps .s.next { opacity: .5; }
  .steps .line { width: clamp(24px, 3vw, 56px); height: 2px; background: var(--m15); }
  .steps .line.done { background: var(--m); }

  .layout { display: grid; grid-template-columns: minmax(0, 1fr) clamp(340px, 30vw, 430px); gap: clamp(20px, 2.6vw, 40px); align-items: start; }
  .card { border-radius: 24px; box-shadow: inset 0 0 0 1.5px var(--m); background: var(--cr); padding: clamp(18px, 1.8vw, 28px); }
  .card.soft { box-shadow: inset 0 0 0 1.5px var(--m15); }
  .card h2 { font: 600 20px var(--f-title); }
  .card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px; flex-wrap: wrap; }

  /* ringkasan (kolom kanan, ikut menempel saat scroll) */
  .summary { position: sticky; top: 100px; display: flex; flex-direction: column; gap: 14px; }
  .sum-card { border-radius: 26px; overflow: hidden; box-shadow: inset 0 0 0 1.5px var(--m); background: var(--cr); }
  .sum-body { padding: 22px 24px 8px; }
  .sum-row { display: flex; justify-content: space-between; gap: 12px; font: 500 14.5px var(--f-body); padding: 7px 0; }
  .sum-row .mut { opacity: .7; }
  .sum-total { background: var(--m); color: var(--cr); padding: 20px 24px 24px; margin-top: 12px; }
  .sum-total .t { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 18px; }
  .sum-total .t b { font: 700 clamp(26px, 2.2vw, 32px) var(--f-title); line-height: 1; }
  .sum-total small { display: block; text-align: center; font: 500 12.5px var(--f-body); opacity: .8; margin-top: 12px; }
  .note { display: flex; gap: 10px; align-items: flex-start; padding: 14px 16px; border-radius: 16px; background: var(--pp); font: 500 13.5px / 1.5 var(--f-body); }
  .note .ic { margin-top: 1px; }

  .qty { display: inline-flex; align-items: center; height: 40px; border-radius: 999px; box-shadow: inset 0 0 0 1.5px var(--m); }
  .qty button { width: 38px; height: 100%; display: grid; place-items: center; }
  .qty b { min-width: 26px; text-align: center; font: 700 15px var(--f-title); }

  .field label { display: block; font: 800 11px var(--f-accent); letter-spacing: .12em; text-transform: uppercase; margin-bottom: 8px; }
  .in { display: flex; align-items: center; gap: 12px; min-height: 52px; padding: 0 18px; border-radius: 16px; background: var(--pp);
        border: 1.5px solid transparent; font: 500 15px var(--f-body); }
  .in:focus-within { background: var(--cr); border-color: var(--m); box-shadow: 0 0 0 4px var(--bl); }
  .in input, .in textarea, .in select { all: unset; flex: 1; min-width: 0; color: var(--m); }
  .in input::placeholder, .in textarea::placeholder { color: rgba(151, 23, 58, .5); }
  .in.ta { align-items: flex-start; padding: 14px 18px; }
  .in.ta textarea { min-height: 54px; line-height: 1.5; }

  footer { margin-top: clamp(56px, 6vw, 88px); background: var(--m); color: var(--cr); }
  .torn { display: block; width: 100%; height: 24px; }
  .copy { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; padding: 18px 0 26px; font: 600 13px var(--f-accent); }
  .copy b { font: 800 18px var(--f-accent); letter-spacing: .08em; }

  @media (max-width: 1100px) { .mainnav { display: none; } }
  @media (max-width: 900px) {
    .layout { grid-template-columns: minmax(0, 1fr); }
    .summary { position: static; }
    .user, .cart .txt, .topbar nav, .steps .s span:not(.n) { display: none; }
  }

  .sec { margin-bottom: clamp(16px, 1.6vw, 24px); }
  .sec-title { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
  .sec-title .no { width: 34px; height: 34px; border-radius: 50%; background: var(--m); color: var(--cr); display: grid; place-items: center; font: 700 14px var(--f-title); flex: none; }
  .sec-title h2 { font: 600 20px var(--f-title); }
  .sec-title p { font: 500 13.5px var(--f-body); opacity: .7; }
  /* pilihan layanan seperti tab modul bertumpuk */
  .svc-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
  .svc-opt { position: relative; text-align: left; padding: 18px; border-radius: 0 20px 20px 20px; background: var(--cr); box-shadow: inset 0 0 0 1.5px var(--m15); margin-top: 12px; transition: .2s; }
  .svc-opt::before { content: ''; position: absolute; left: 0; top: -12px; width: 70px; height: 13px; border-radius: 12px 12px 0 0; background: inherit; box-shadow: inset 1.5px 1.5px 0 var(--m15), inset -1.5px 0 0 var(--m15); }
  .svc-opt .top { display: flex; justify-content: space-between; align-items: center; }
  .svc-opt .ico { width: 44px; height: 44px; border-radius: 14px; background: var(--pp); display: grid; place-items: center; }
  .svc-opt .ico .ic { width: 22px; height: 22px; }
  .svc-opt .rad { width: 22px; height: 22px; border-radius: 50%; box-shadow: inset 0 0 0 1.8px currentColor; display: grid; place-items: center; }
  .svc-opt b { display: block; font: 700 17px var(--f-title); margin-top: 14px; }
  .svc-opt small { font: 500 13px var(--f-body); opacity: .75; }
  .svc-opt:hover { box-shadow: inset 0 0 0 1.5px var(--m); }
  .svc-opt.on { background: var(--m); color: var(--cr); box-shadow: none; }
  .svc-opt.on::before { box-shadow: none; }
  .svc-opt.on .ico { background: var(--cr); color: var(--m); }
  .svc-opt.on .rad::after { content: ''; width: 10px; height: 10px; border-radius: 50%; background: var(--cr); }
  .svc-panel { margin-top: 18px; display: none; }
  .svc-panel.on { display: block; }
  .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  .zone { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 18px 22px; margin-top: 14px; }
  .zone .z { font: 700 24px var(--f-title); line-height: 1; margin-top: 6px; }
  .zone small { font: 500 12.5px var(--f-body); opacity: .72; }
  .kt { position: relative; display: inline-flex; align-items: center; padding: 8px 16px 8px 30px; border-radius: 12px; background: var(--bl); font: 700 18px var(--f-title); white-space: nowrap; transform: rotate(-6deg); }
  .kt::before { content: ''; position: absolute; left: 11px; top: 50%; width: 10px; height: 10px; margin-top: -5px; border-radius: 50%; background: var(--cr); box-shadow: inset 0 0 0 1.5px var(--m); }
  .chips { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
  .chips button { height: 38px; padding: 0 16px; border-radius: 999px; box-shadow: inset 0 0 0 1.5px var(--m); font: 700 13px var(--f-accent); }
  .chips button.on { background: var(--m); color: var(--cr); box-shadow: none; }
  .mini { display: flex; flex-direction: column; gap: 12px; padding-bottom: 6px; }
  .mi { display: grid; grid-template-columns: 52px minmax(0, 1fr) auto; gap: 12px; align-items: center; }
  .mi img { width: 52px; height: 52px; border-radius: 14px; object-fit: cover; }
  .mi b { display: block; font: 600 14px / 1.3 var(--f-title); }
  .mi span { font: 500 12.5px var(--f-body); opacity: .7; }
  .mi .p { font: 700 14px var(--f-title); white-space: nowrap; }
  .hr { height: 0; border-top: 1.5px dashed var(--m60); margin: 14px 0 8px; opacity: .6; }
  /* modal pesanan berhasil */
  .ovl { position: fixed; inset: 0; z-index: 100; background: rgba(40, 40, 40, .55); display: none; place-items: center; padding: 20px; }
  .ovl.on { display: grid; }
  .modal { width: min(460px, 100%); border-radius: 28px; overflow: hidden; background: var(--cr); text-align: center; }
  .modal .top { padding: 34px 28px 22px; }
  .modal .ok { width: 74px; height: 74px; border-radius: 50%; background: var(--m); color: var(--cr); display: grid; place-items: center; margin: 0 auto 16px; }
  .modal h3 { font: 700 28px var(--f-title); }
  .modal .ord { font: 700 20px var(--f-title); margin: 14px 0 10px; }
  .modal .bot { padding: 0 28px 28px; display: grid; gap: 10px; }
  @media (max-width: 760px) { .svc-grid, .grid2 { grid-template-columns: 1fr; } }
</style>
@endpush

@use('App\Models\Pesanan')
@php
  $persen = fn ($v) => \App\Models\PengaturanSistem::persen($v);
  $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
  $layanan = old('jenis_layanan', $zona->isEmpty() && $layananPilihan === Pesanan::LAYANAN_DELIVERY ? Pesanan::LAYANAN_TAKE_AWAY : $layananPilihan);
  $metode = old('metode', $midtransAktif ? \App\Models\Pembayaran::METODE_TRANSFER : \App\Models\Pembayaran::METODE_TUNAI);
  $bankPilih = old('bank', array_key_first($bank));
  $u = auth()->user();
  $alamatLama = old('alamat', $u->alamat);
  $prevRadius = 0;
@endphp

@push('styles')
<style>
  .peta { height: 300px; border-radius: 18px; margin-top: 10px; box-shadow: inset 0 0 0 1.5px var(--m15); z-index: 0; }
  .peta-alat { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 8px; }
  .cari-alamat { flex: 1; min-width: 220px; display: flex; align-items: center; gap: 10px; min-height: 44px; padding: 0 16px; border-radius: 999px; background: var(--pp); }
  .cari-alamat input { all: unset; flex: 1; min-width: 0; font: 500 14px var(--f-body); color: var(--m); }
  .hasil-cari { margin-top: 8px; border-radius: 16px; box-shadow: inset 0 0 0 1.5px var(--m15); overflow: hidden; }
  .hasil-cari button { display: block; width: 100%; text-align: left; padding: 10px 14px; font: 500 13.5px var(--f-body); border-bottom: 1px solid var(--m15); }
  .hasil-cari button:hover { background: var(--pp); }
  .peta-info { margin-top: 10px; padding: 12px 14px; border-radius: 14px; background: var(--pp); font: 600 13.5px var(--f-body); }
  .peta-info.ok { background: var(--bl); }
  .peta-info.bad { background: var(--m); color: var(--cr); }
  .zones { display: grid; gap: 8px; margin-top: 14px; }
  .zone-opt { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 16px; box-shadow: inset 0 0 0 1.5px var(--m15); cursor: pointer; background: var(--cr); }
  .zone-opt:hover { box-shadow: inset 0 0 0 1.5px var(--m); }
  .zone-opt input { accent-color: #97173A; width: 18px; height: 18px; flex: none; }
  .zone-opt .zn { flex: 1; min-width: 0; }
  .zone-opt .zn b { display: block; font: 700 15px var(--f-title); }
  .zone-opt .zn small { font-size: 12.5px; opacity: .7; }
  .zone-opt:has(input:checked) { box-shadow: inset 0 0 0 2px var(--m); background: var(--pp); }
  .ro { opacity: .8; }
  .btn[disabled] { opacity: .5; cursor: not-allowed; }
  @media (max-width: 640px) {
    #svc.svc-grid { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; gap: 8px; }
    #svc .svc-opt { padding: 12px 10px; }
    #svc .svc-opt small { display: none; }
    #svc .svc-opt b { font-size: 14px; margin-top: 8px; }
    #svc .svc-opt .ico { width: 36px; height: 36px; }
  }
  .mi .ph-none { width: 48px; height: 48px; border-radius: 12px; display: grid; place-items: center; background: var(--bl); flex: none; }
</style>
@endpush

@section('content')
<main class="wrap">
  <div class="page-head">
    <div><div class="crumb"><a href="{{ route('pelanggan.beranda') }}">Beranda</a><span>/</span><a href="{{ route('pelanggan.keranjang') }}">Keranjang</a><span>/</span><span>Checkout</span></div><h1>Checkout</h1></div>
    <div class="steps"><div class="s done"><span class="n"><svg class="ic" style="width:15px;height:15px;stroke-width:2.6"><use href="#i-check"/></svg></span><span>Keranjang</span></div><div class="line done"></div>
      <div class="s now"><span class="n">2</span><span>Checkout</span></div><div class="line"></div><div class="s next"><span class="n">3</span><span>Pembayaran</span></div></div>
  </div>

  @if (! $buka)
    <div class="flash" role="alert">Restoran sedang tutup. Pesanan bisa dibuat pada jam {{ $pengaturan->jamLabel() }}.</div>
  @endif
  @if (session('error'))<div class="flash" role="alert">{{ session('error') }}</div>@endif
  @if ($errors->any())
    <div class="flash" role="alert">@if ($errors->count() === 1){{ $errors->first() }}@else<ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>@endif</div>
  @endif

  <form method="post" action="{{ route('pelanggan.checkout.store') }}" id="fCheckout">
    @csrf
    <input type="hidden" name="jenis_layanan" id="jenisLayanan" value="{{ $layanan }}">
    <div class="layout">
      <div>
        <!-- 1. Jenis layanan -->
        <section class="card sec">
          <div class="sec-title"><span class="no">1</span><div><h2>Pilih jenis layanan</h2><p>Isian di bawah menyesuaikan layanan yang dipilih.</p></div></div>
          <div class="svc-grid" id="svc">
            <button type="button" @class(['svc-opt', 'on' => $layanan === Pesanan::LAYANAN_DINE_IN]) data-svc="{{ Pesanan::LAYANAN_DINE_IN }}"><div class="top"><span class="ico"><svg class="ic"><use href="#i-dine"/></svg></span><span class="rad"></span></div><b>Dine In</b><small>Makan di restoran</small></button>
            <button type="button" @class(['svc-opt', 'on' => $layanan === Pesanan::LAYANAN_TAKE_AWAY]) data-svc="{{ Pesanan::LAYANAN_TAKE_AWAY }}"><div class="top"><span class="ico"><svg class="ic"><use href="#i-bag"/></svg></span><span class="rad"></span></div><b>Take Away</b><small>Ambil sendiri di kasir</small></button>
            <button type="button" @class(['svc-opt', 'on' => $layanan === Pesanan::LAYANAN_DELIVERY]) data-svc="{{ Pesanan::LAYANAN_DELIVERY }}" @disabled($zona->isEmpty())><div class="top"><span class="ico"><svg class="ic"><use href="#i-truck"/></svg></span><span class="rad"></span></div><b>Delivery</b><small>{{ $zona->isEmpty() ? 'Belum tersedia' : 'Diantar kurir, ongkir sesuai zona' }}</small></button>
          </div>

          <div @class(['svc-panel', 'on' => $layanan === Pesanan::LAYANAN_DINE_IN]) data-panel="{{ Pesanan::LAYANAN_DINE_IN }}">
            <div class="field"><label for="meja">Nomor meja</label><div class="in"><svg class="ic"><use href="#i-dine"/></svg><select id="meja" name="nomor_meja">
              <option value="">Pilih nomor meja</option>
              @for ($i = 1; $i <= config('resto.jumlah_meja'); $i++)
                @php $no = str_pad($i, 2, '0', STR_PAD_LEFT); @endphp
                <option value="{{ $no }}" @selected(old('nomor_meja') === $no)>Meja {{ $no }}</option>
              @endfor
            </select></div></div>
            <div class="note" style="margin-top:14px"><svg class="ic"><use href="#i-info"/></svg>Pilih Dine In hanya bila kamu sudah duduk di restoran. Nomor meja tertera di sudut meja. Pesanan masuk antrean dapur setelah dikonfirmasi kasir.</div>
          </div>

          <div @class(['svc-panel', 'on' => $layanan === Pesanan::LAYANAN_TAKE_AWAY]) data-panel="{{ Pesanan::LAYANAN_TAKE_AWAY }}">
            <div class="note"><svg class="ic"><use href="#i-info"/></svg>Ambil pesanan di kasir dengan menyebutkan nomor pesanan. Pantau statusnya di halaman Lacak Pesanan.</div>
          </div>

          <div @class(['svc-panel', 'on' => $layanan === Pesanan::LAYANAN_DELIVERY]) data-panel="{{ Pesanan::LAYANAN_DELIVERY }}">
            {{-- Titik antar di peta (seperti aplikasi ojek online): GPS, cari alamat, atau geser pin. Zona & ongkir otomatis dari jarak. --}}
            <div class="eyebrow" style="font-size:10.5px">Titik pengantaran</div>
            <div class="peta-alat">
              <button type="button" class="btn sm" id="btnGps"><svg class="ic"><use href="#i-pin"/></svg>Pakai lokasi saya</button>
              <div class="cari-alamat"><svg class="ic"><use href="#i-search"/></svg><input id="cariAlamat" placeholder="Cari jalan / nama tempat, mis. Jl. Sei Batang Hari" autocomplete="off"></div>
            </div>
            <div class="hasil-cari" id="hasilCari" hidden></div>
            <div id="peta" class="peta" role="application" aria-label="Peta titik pengantaran"></div>
            <div class="peta-info" id="petaInfo">Ketuk peta atau geser pin ke lokasi pengantaran.</div>
            <input type="hidden" name="lat" id="lat" value="{{ old('lat') }}">
            <input type="hidden" name="lng" id="lng" value="{{ old('lng') }}">
            @error('lat')<div class="flash" role="alert" style="margin-top:10px">{{ $message }}</div>@enderror

            <div class="field" style="margin-top:14px"><label for="alamat">Alamat lengkap</label><div class="in ta"><svg class="ic" style="margin-top:2px"><use href="#i-pin"/></svg><textarea id="alamat" name="alamat" maxlength="500" placeholder="Terisi otomatis dari peta; lengkapi nomor rumah bila perlu">{{ $alamatLama }}</textarea></div>@error('alamat')<div style="font:600 12.5px var(--f-body);margin-top:6px">{{ $message }}</div>@enderror</div>
            @if ($u->alamat)
              @php $pakaiProfil = trim((string) $alamatLama) !== '' && trim((string) $alamatLama) === trim($u->alamat); @endphp
              <div class="chips" id="addrChips"><button type="button" @class(['on' => $pakaiProfil]) data-addr="profil">Pakai alamat profil</button><button type="button" @class(['on' => ! $pakaiProfil]) data-addr="lain">Alamat lain</button></div>
            @endif
            <div class="field" style="margin-top:12px"><label for="patokan">Patokan untuk kurir (opsional)</label><div class="in"><svg class="ic"><use href="#i-info"/></svg><input id="patokan" name="patokan" maxlength="255" value="{{ old('patokan') }}" placeholder="Mis. pagar hijau, sebelah warung"></div></div>
            <div class="note" style="margin-top:14px"><svg class="ic"><use href="#i-info"/></svg><span>Ongkir mengikuti jarak titik ke restoran ({{ config('resto.alamat') }}): @php $dari = 0; @endphp @foreach ($zona as $z){{ $z->nama_zona }} {{ rtrim(rtrim(number_format($dari, 1, ',', '.'), '0'), ',') }}–{{ rtrim(rtrim(number_format((float) $z->radius_km, 1, ',', '.'), '0'), ',') }} km {{ $rp($z->ongkir()) }}{{ $loop->last ? '' : ' · ' }}@php $dari = (float) $z->radius_km; @endphp @endforeach. Bisa memesan untuk alamat lain walau kamu sedang di luar kota.</span></div>
          </div>
        </section>

        <!-- 2. Data pemesan -->
        <section class="card sec">
          <div class="sec-title"><span class="no">2</span><div><h2>Data pemesan</h2><p>Diambil dari profil akunmu · <a href="{{ route('pelanggan.profil') }}" style="font-weight:700;text-decoration:underline">Ubah</a></p></div></div>
          <div class="grid2">
            <div class="field"><label>Nama</label><div class="in ro"><svg class="ic"><use href="#i-user"/></svg><input value="{{ $u->nama }}" readonly></div></div>
            <div class="field"><label>Nomor HP</label><div class="in ro"><svg class="ic"><use href="#i-phone"/></svg><input value="{{ $u->no_hp }}" readonly></div></div>
          </div>
          <div class="note" style="margin-top:14px"><svg class="ic"><use href="#i-edit"/></svg>Catatan untuk tiap menu (mis. "pedas", "es sedikit") diisi di halaman Keranjang.</div>
        </section>

        <!-- 3. Metode pembayaran -->
        <section class="card sec">
          <div class="sec-title" style="margin-bottom:12px"><span class="no">3</span><div><h2>Pilih metode pembayaran</h2><p>{{ $midtransAktif ? 'Virtual Account & QRIS dicek otomatis dan pesanan langsung diteruskan ke dapur.' : 'Bayar tunai saat pesanan diambil atau diantar.' }}</p></div></div>
          @include('pelanggan.partials.metode-bayar', ['teksTunai' => $layanan === Pesanan::LAYANAN_DELIVERY ? 'Bayar tunai ke kurir saat pesanan tiba' : 'Bayar tunai di kasir'])
        </section>
      </div>

      <aside class="summary">
        <div class="sum-card">
          <div class="sum-body">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px"><span class="eyebrow">Ringkasan pesanan</span><a href="{{ route('pelanggan.keranjang') }}" style="font:700 13px var(--f-accent);text-decoration:underline">Ubah</a></div>
            <div class="mini">
              @foreach ($items as $it)
                <div class="mi">@if ($it['menu']->fotoUrl())<img src="{{ $it['menu']->fotoUrl() }}" alt="">@else<span class="ph-none"><svg class="ic"><use href="#i-dine"/></svg></span>@endif
                  <div><b>{{ $it['menu']->nama_menu }}</b><span>{{ $it['jumlah'] }} × {{ $rp($it['menu']->harga) }}@if ($it['catatan']) · {{ $it['catatan'] }}@endif</span></div><span class="p">{{ $rp($it['subtotal']) }}</span></div>
              @endforeach
            </div>
            <div class="hr"></div>
            <div class="sum-row"><span>Subtotal ({{ $ringkasan['jumlah'] }} item)</span><span>{{ $rp($hitung['subtotal']) }}</span></div>
            <div class="sum-row"><span>Pajak ({{ $persen($pengaturan->pajak) }}%)</span><span>{{ $rp($hitung['pajak']) }}</span></div>
            <div class="sum-row"><span>Biaya layanan ({{ $persen($pengaturan->biaya_layanan) }}%)</span><span>{{ $rp($hitung['biaya_layanan']) }}</span></div>
            <div class="sum-row" id="ongRow"><span>Ongkos kirim (<span id="ongZone">-</span>)</span><span id="ongVal">Rp 0</span></div>
          </div>
          <div class="sum-total">
            <div class="t"><div><span class="eyebrow" style="opacity:.8">Total pembayaran</span><div style="margin-top:8px"><span class="pill b" id="svcBadge">{{ Pesanan::LABEL_LAYANAN[$layanan] }}</span></div></div><b id="grand">{{ $rp($hitung['total']) }}</b></div>
            <button class="btn cr blk" id="btnConfirm" type="submit" @disabled(! $buka)>Konfirmasi Pesanan <svg class="ic"><use href="#i-check"/></svg></button>
            <small>{{ $buka ? 'Dengan menekan tombol ini, pesanan disimpan dan diteruskan ke kasir' : 'Restoran sedang tutup' }}</small>
          </div>
        </div>
        <a class="btn o blk" href="{{ route('pelanggan.keranjang') }}"><svg class="ic"><use href="#i-arrl"/></svg>Kembali ke Keranjang</a>
      </aside>
    </div>
  </form>
</main>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
@php
  $zonaJs = $zona->map(fn ($z) => ['nama' => $z->nama_zona, 'radius' => (float) $z->radius_km, 'ongkir' => $z->ongkir()])->values();
@endphp
<script>
  (function () {
    var dasar = {{ (float) $hitung['total'] }}, label = @json(Pesanan::LABEL_LAYANAN), alamatProfil = @json($u->alamat);
    var RESTO = [{{ (float) ($pengaturan->lat_resto ?? \App\Models\PengaturanSistem::LAT_AWAL) }}, {{ (float) ($pengaturan->lng_resto ?? \App\Models\PengaturanSistem::LNG_AWAL) }}];
    var ZONA = @json($zonaJs), zonaAktif = null;

    /* jarak garis lurus (km), sama dengan perhitungan server */
    function jarakKm(a, b) {
      var r = Math.PI / 180, dLat = (b[0] - a[0]) * r, dLng = (b[1] - a[1]) * r;
      var h = Math.sin(dLat / 2) ** 2 + Math.cos(a[0] * r) * Math.cos(b[0] * r) * Math.sin(dLng / 2) ** 2;
      return 6371 * 2 * Math.asin(Math.min(1, Math.sqrt(h)));
    }
    function km(n) { return n.toLocaleString('id-ID', { maximumFractionDigits: 1 }); }

    function zonaDipilih() { return zonaAktif; }
    function hitung() {
      var s = jenisLayanan.value, z = zonaDipilih(), ong = s === 'delivery' && z ? z.ongkir : 0;
      ongRow.style.display = s === 'delivery' ? '' : 'none';
      ongZone.textContent = z ? z.nama : 'tentukan titik'; ongVal.textContent = z ? rp(ong) : '-';
      grand.textContent = rp(dasar + ong); svcBadge.textContent = label[s];
      var t = s === 'delivery' ? 'Bayar tunai ke kurir saat pesanan tiba' : 'Bayar tunai di kasir';
      tunaiSub.textContent = t; tunaiInfo.textContent = t + '.';
    }
    document.querySelectorAll('.svc-opt').forEach(function (b) {
      b.addEventListener('click', function () {
        if (b.disabled) return;
        jenisLayanan.value = b.dataset.svc;
        document.querySelectorAll('.svc-opt').forEach(function (x) { x.classList.toggle('on', x === b); });
        document.querySelectorAll('.svc-panel').forEach(function (p) { p.classList.toggle('on', p.dataset.panel === b.dataset.svc); });
        hitung();
      });
    });
    var chips = document.getElementById('addrChips');
    chips && chips.addEventListener('click', function (e) {
      var b = e.target.closest('button'); if (!b) return;
      chips.querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x === b); });
      alamat.value = b.dataset.addr === 'profil' ? alamatProfil : ''; alamat.focus();
      if (b.dataset.addr === 'profil' && alamatProfil) { cariAlamat.value = alamatProfil; cari(); }
    });

    /* ---------- peta titik pengantaran (OpenStreetMap) ---------- */
    var peta = null, pin = null, alamatDiubah = false;
    alamat.addEventListener('input', function () { alamatDiubah = true; });
    function siapkanPeta() {
      if (peta || !window.L) { if (!window.L) petaInfo.textContent = 'Peta gagal dimuat. Periksa koneksi internet lalu muat ulang halaman.'; return; }
      peta = L.map('peta', { zoomControl: true }).setView(RESTO, 13);
      L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(peta);
      L.circleMarker(RESTO, { radius: 8, color: '#97173A', fillColor: '#F8F5E6', fillOpacity: 1, weight: 3 }).addTo(peta).bindTooltip('Restoran');
      var maks = ZONA.length ? ZONA[ZONA.length - 1].radius : 0;
      if (maks) L.circle(RESTO, { radius: maks * 1000, color: '#97173A', weight: 1, fillOpacity: .04, dashArray: '4 6' }).addTo(peta);
      peta.on('click', function (e) { taruhPin(e.latlng.lat, e.latlng.lng, true); });
      if (lat.value && lng.value) taruhPin(+lat.value, +lng.value, false);
    }
    function taruhPin(la, ln, isiAlamat) {
      if (!pin) {
        pin = L.marker([la, ln], { draggable: true }).addTo(peta);
        pin.on('dragend', function () { var p = pin.getLatLng(); taruhPin(p.lat, p.lng, true); });
      } else pin.setLatLng([la, ln]);
      peta.setView([la, ln], Math.max(peta.getZoom(), 16));
      lat.value = la.toFixed(7); lng.value = ln.toFixed(7);
      var d = jarakKm(RESTO, [la, ln]);
      zonaAktif = ZONA.find(function (z) { return d <= z.radius; }) || null;
      petaInfo.className = 'peta-info ' + (zonaAktif ? 'ok' : 'bad');
      petaInfo.textContent = zonaAktif
        ? km(d) + ' km dari restoran · ' + zonaAktif.nama + ' · ongkir ' + rp(zonaAktif.ongkir)
        : km(d) + ' km dari restoran · di luar jangkauan Delivery (maks ' + km(ZONA.length ? ZONA[ZONA.length - 1].radius : 0) + ' km). Pilih Take Away.';
      hitung();
      if (isiAlamat && !alamatDiubah) alamatDariTitik(la, ln);
    }
    /* nama alamat dari titik (OpenStreetMap Nominatim) */
    function alamatDariTitik(la, ln) {
      fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&accept-language=id&lat=' + la + '&lon=' + ln)
        .then(function (r) { return r.json(); }).then(function (j) { if (j && j.display_name && !alamatDiubah) alamat.value = j.display_name; })
        .catch(function () {});
    }
    function cari() {
      var q = cariAlamat.value.trim(); if (q.length < 3) return;
      var vb = [RESTO[1] - .5, RESTO[0] + .5, RESTO[1] + .5, RESTO[0] - .5].join(',');
      fetch('https://nominatim.openstreetmap.org/search?format=jsonv2&limit=5&countrycodes=id&accept-language=id&viewbox=' + vb + '&q=' + encodeURIComponent(q))
        .then(function (r) { return r.json(); }).then(function (hasil) {
          hasilCari.innerHTML = ''; hasilCari.hidden = false;
          if (!hasil.length) { hasilCari.innerHTML = '<button type="button" disabled>Alamat tidak ditemukan. Coba nama jalan lain, atau geser pin langsung di peta.</button>'; return; }
          hasil.forEach(function (h) {
            var b = document.createElement('button'); b.type = 'button'; b.textContent = h.display_name;
            b.addEventListener('click', function () { hasilCari.hidden = true; alamatDiubah = false; alamat.value = h.display_name; taruhPin(+h.lat, +h.lon, false); });
            hasilCari.appendChild(b);
          });
        }).catch(function () { pToast('Pencarian alamat gagal. Periksa koneksi internet.'); });
    }
    var tunda;
    cariAlamat.addEventListener('input', function () { clearTimeout(tunda); tunda = setTimeout(cari, 600); });
    cariAlamat.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); clearTimeout(tunda); cari(); } });
    btnGps.addEventListener('click', function () {
      if (!navigator.geolocation) { pToast('Perangkat tidak mendukung lokasi otomatis. Cari alamat atau geser pin.'); return; }
      btnGps.disabled = true; btnGps.lastChild.textContent = 'Mencari lokasi…';
      navigator.geolocation.getCurrentPosition(function (pos) {
        btnGps.disabled = false; btnGps.lastChild.textContent = 'Pakai lokasi saya';
        alamatDiubah = false; taruhPin(pos.coords.latitude, pos.coords.longitude, true);
      }, function () {
        btnGps.disabled = false; btnGps.lastChild.textContent = 'Pakai lokasi saya';
        pToast('Lokasi tidak bisa diambil. Izinkan akses lokasi, atau cari alamat / geser pin.');
      }, { enableHighAccuracy: true, timeout: 10000 });
    });
    // peta disiapkan saat panel Delivery tampil (Leaflet butuh ukuran yang terlihat)
    function cekPanel() { if (jenisLayanan.value === 'delivery') { siapkanPeta(); setTimeout(function () { peta && peta.invalidateSize(); }, 50); } }
    document.querySelectorAll('.svc-opt').forEach(function (b) { b.addEventListener('click', cekPanel); });
    cekPanel();
    fCheckout.addEventListener('submit', function () { btnConfirm.disabled = true; });
    hitung();
  })();
</script>
@endpush
