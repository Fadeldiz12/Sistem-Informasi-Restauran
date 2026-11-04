@extends('layouts.pelanggan')

@section('title', 'Pembayaran')

@push('styles')
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

  .opt { position: relative; text-align: left; padding: 18px; border-radius: 0 20px 20px 20px; background: var(--cr); box-shadow: inset 0 0 0 1.5px var(--m15); margin-top: 12px; width: 100%; }
  .opt::before { content: ''; position: absolute; left: 0; top: -12px; width: 70px; height: 13px; border-radius: 12px 12px 0 0; background: inherit; box-shadow: inset 1.5px 1.5px 0 var(--m15), inset -1.5px 0 0 var(--m15); }
  .opt .top { display: flex; justify-content: space-between; align-items: center; }
  .opt .ico { width: 44px; height: 44px; border-radius: 14px; background: var(--pp); display: grid; place-items: center; }
  .opt .rad { width: 22px; height: 22px; border-radius: 50%; box-shadow: inset 0 0 0 1.8px currentColor; display: grid; place-items: center; }
  .opt b { display: block; font: 700 17px var(--f-title); margin-top: 14px; } .opt small { font: 500 13px var(--f-body); opacity: .75; }
  .opt.on { background: var(--m); color: var(--cr); box-shadow: none; } .opt.on::before { box-shadow: none; }
  .opt.on .ico { background: var(--cr); color: var(--m); } .opt.on .rad::after { content: ''; width: 10px; height: 10px; border-radius: 50%; background: var(--cr); }
  .g2 { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 12px; }
  .panel { display: none; margin-top: 18px; } .panel.on { display: block; }
  .upl { display: flex; align-items: center; gap: 14px; padding: 16px; border-radius: 18px; border: 1.8px dashed var(--m60); }
  .upl-ic { width: 56px; height: 56px; border-radius: 14px; background: var(--bl); display: grid; place-items: center; flex: none; }
  .ovl { position: fixed; inset: 0; z-index: 100; background: rgba(40, 40, 40, .55); display: none; place-items: center; padding: 20px; }
  .ovl.on { display: grid; }
  .modal { width: min(460px, 100%); border-radius: 28px; overflow: hidden; background: var(--cr); text-align: center; }
  @media (max-width: 760px) { .g2 { grid-template-columns: 1fr; } .upl { flex-wrap: wrap; } .upl > div:nth-child(2) { min-width: 0; } }
</style>
@endpush

@use('App\Models\Pembayaran')
@use('App\Models\Pesanan')
@php
  $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
  $bayar = $pesanan->pembayaran;
  $lunas = $bayar?->status_bayar === Pembayaran::STATUS_LUNAS;
  $batal = $pesanan->status === Pesanan::STATUS_DIBATALKAN;
  $gagal = $bayar && ($bayar->status_bayar === Pembayaran::STATUS_DITOLAK || $bayar->isKedaluwarsa());
  $menungguOtomatis = $bayar?->isOtomatis() && ! $lunas && ! $gagal;
  $tunai = $bayar?->metode === Pembayaran::METODE_TUNAI && ! $lunas;
  $bisaBayar = ! $lunas && ! $batal;
  $metode = old('metode', $bayar && ! $gagal ? $bayar->metode : ($midtransAktif ? Pembayaran::METODE_TRANSFER : Pembayaran::METODE_TUNAI));
  $bankPilih = old('bank', $bayar?->bank ?? array_key_first($bank));
  $statusLabel = $batal ? 'Pesanan Dibatalkan' : $pesanan->bayarLabel();
@endphp

@push('styles')
<style>
  /* dialog "Pesanan Dibuat" */
  .modal .top { padding: 34px 28px 22px; }
  .modal .ok { width: 74px; height: 74px; border-radius: 50%; background: var(--m); color: var(--cr); display: grid; place-items: center; margin: 0 auto 16px; }
  .modal h3 { font: 700 28px var(--f-title); }
  .modal .ord { font: 700 22px var(--f-title); margin: 6px 0 12px; }
  .modal .bot { padding: 0 28px 28px; display: grid; gap: 10px; }
  .modal .btn.blk { width: 100%; }

  #pay.g3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
  .opt[disabled] { opacity: .5; cursor: not-allowed; }
  .banks { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
  .banks button { height: 42px; min-width: 88px; padding: 0 16px; border-radius: 14px; box-shadow: inset 0 0 0 1.5px var(--m15); font: 700 14px var(--f-title); background: var(--cr); }
  .banks button.on { box-shadow: inset 0 0 0 2px var(--m); background: var(--pp); }
  .btn[disabled] { opacity: .5; cursor: not-allowed; }
  .state { display: flex; gap: 12px; align-items: flex-start; padding: 14px 16px; border-radius: 16px; background: var(--pp); margin-bottom: 18px; font: 500 14px/1.5 var(--f-body); }
  .state.bad { background: var(--m); color: var(--cr); }
  .state.ok { background: var(--bl); }
  .instr { display: grid; gap: 16px; }
  .va-box { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
  .va-no { font: 700 clamp(22px, 3.4vw, 30px) var(--f-title); letter-spacing: .04em; word-break: break-all; }
  .qris-box { display: flex; gap: 20px; align-items: center; flex-wrap: wrap; }
  .qris-box img { width: 220px; height: 220px; border-radius: 18px; background: #fff; padding: 10px; box-shadow: inset 0 0 0 1.5px var(--m15); flex: none; object-fit: contain; }
  .langkah { display: grid; gap: 8px; counter-reset: l; font-size: 13.5px; }
  .langkah li { list-style: none; display: flex; gap: 10px; align-items: flex-start; }
  .langkah li::before { counter-increment: l; content: counter(l); width: 22px; height: 22px; flex: none; border-radius: 50%; background: var(--m); color: var(--cr); display: grid; place-items: center; font: 700 11.5px var(--f-title); }
  .tunggu { display: flex; gap: 10px; align-items: center; font: 600 13.5px var(--f-accent); }
  .tunggu i { width: 10px; height: 10px; border-radius: 50%; background: var(--m); animation: denyut 1.2s infinite; flex: none; }
  @keyframes denyut { 0%, 100% { opacity: .25; transform: scale(.8); } 50% { opacity: 1; transform: scale(1.1); } }
  .ganti summary { cursor: pointer; font: 700 14px var(--f-accent); text-decoration: underline; margin-top: 4px; list-style: none; }
  .ganti summary::-webkit-details-marker { display: none; }
  @media (max-width: 640px) {
    #pay.g3 { grid-template-columns: 1fr; gap: 4px; }
    .qris-box img { width: 100%; height: auto; max-width: 260px; margin: 0 auto; }
  }
</style>
@endpush

@section('content')
<main class="wrap">
  <div class="page-head">
    <div><div class="crumb"><a href="{{ route('pelanggan.beranda') }}">Beranda</a><span>/</span><a href="{{ route('pelanggan.tracking', ['pesanan' => $pesanan->id]) }}">Pesanan saya</a><span>/</span><span>Pembayaran</span></div><h1>Pembayaran</h1></div>
    <div class="steps"><div class="s done"><span class="n"><svg class="ic" style="width:15px;height:15px;stroke-width:2.6"><use href="#i-check"/></svg></span><span>Keranjang</span></div><div class="line done"></div><div class="s done"><span class="n"><svg class="ic" style="width:15px;height:15px;stroke-width:2.6"><use href="#i-check"/></svg></span><span>Checkout</span></div><div class="line done"></div><div class="s now"><span class="n">3</span><span>Pembayaran</span></div></div>
  </div>

  @if ($errors->any())
    <div class="flash" role="alert">{{ $errors->first() }}</div>
  @endif

  <div class="layout">
    <div>
      <section class="card" style="padding:0;overflow:hidden;margin-bottom:20px">
        <div class="stripes" style="padding:20px 24px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
          <div class="stamp-out"><div class="stamp" style="padding:16px 22px"><div class="eyebrow">{{ $lunas ? 'Pesanan sudah dibayar' : 'Nomor pesanan' }}</div><div style="font:700 22px var(--f-title);margin-top:4px">{{ $pesanan->kode() }}</div><div style="font-size:12.5px;opacity:.72">{{ $pesanan->layananDetail() }} · {{ $pesanan->detail->sum('jumlah') }} item</div></div></div>
          <span class="pill b" style="font-size:13px;padding:8px 16px;background:var(--cr);box-shadow:inset 0 0 0 1.4px var(--m)" id="stLabel">{{ $statusLabel }}</span></div>
      </section>

      <section class="card">
        @if ($batal)
          <div class="state bad"><svg class="ic"><use href="#i-info"/></svg><span>Pesanan ini dibatalkan, jadi tidak perlu dibayar. Silakan buat pesanan baru dari beranda.</span></div>
          <a class="btn" href="{{ route('pelanggan.beranda') }}#menu">Pesan Lagi</a>
        @elseif ($lunas)
          <div class="state ok"><svg class="ic"><use href="#i-check"/></svg><span>Pembayaran <b>{{ $bayar->metodeLabel() }}</b> berhasil{{ $bayar->waktu_bayar ? ' pada '.$bayar->waktu_bayar->translatedFormat('d M Y H:i') : '' }}. Pesananmu sudah diteruskan ke dapur. Terima kasih!</span></div>
          <a class="btn" href="{{ route('pelanggan.tracking', ['pesanan' => $pesanan->id]) }}">Lacak Pesanan <svg class="ic"><use href="#i-arr"/></svg></a>
        @else
          @if ($menungguOtomatis)
            {{-- instruksi VA / QRIS --}}
            <div class="instr" id="instr">
              <div class="card-head" style="margin-bottom:0"><div><h2>Selesaikan pembayaran {{ $bayar->metodeLabel() }}</h2><p style="font-size:13.5px;opacity:.7;margin-top:2px">Bayar tepat <b>{{ $rp($bayar->jumlah) }}</b> · status diperbarui otomatis</p></div></div>
              @if ($bayar->metode === Pembayaran::METODE_QRIS)
                <div class="qris-box">
                  @if ($bayar->qris_url)<img src="{{ $bayar->qris_url }}" alt="Kode QRIS pembayaran">@endif
                  <ol class="langkah" style="flex:1;min-width:220px">
                    <li>Buka aplikasi m-banking atau e-wallet (GoPay, OVO, DANA, ShopeePay, dll.).</li>
                    <li>Pilih menu bayar / scan QRIS, lalu scan kode di samping.</li>
                    <li>Pastikan nominal {{ $rp($bayar->jumlah) }}, lalu konfirmasi.</li>
                    <li>Halaman ini berubah otomatis setelah pembayaran diterima.</li>
                  </ol>
                </div>
              @else
                <div class="stripes" style="border-radius:20px;padding:12px"><div class="stamp-out"><div class="stamp va-box" style="padding:16px 18px">
                  <div style="min-width:0"><div class="eyebrow" style="font-size:10.5px">Nomor Virtual Account · {{ $bank[$bayar->bank] ?? strtoupper($bayar->bank) }}</div><div class="va-no" id="noVa">{{ $bayar->nomor_va }}</div><small style="font-size:12.5px;opacity:.72">a.n. {{ config('resto.nama') }} · {{ $rp($bayar->jumlah) }}</small></div>
                  <button class="btn o sm" type="button" id="salin">Salin</button></div></div></div>
                <ol class="langkah">
                  <li>Buka m-banking / ATM / internet banking {{ $bank[$bayar->bank] ?? '' }}.</li>
                  <li>Pilih menu <b>Transfer → Virtual Account</b>, lalu masukkan nomor di atas.</li>
                  <li>Periksa nama dan nominal {{ $rp($bayar->jumlah) }}, lalu konfirmasi.</li>
                  <li>Halaman ini berubah otomatis setelah pembayaran diterima.</li>
                </ol>
              @endif
              <div class="state" style="margin:0;align-items:center"><span class="tunggu"><i></i>Menunggu pembayaran…</span>
                @if ($bayar->kedaluwarsa_pada)<span style="margin-left:auto;font-size:13px">Berlaku <b id="sisa" data-sampai="{{ $bayar->kedaluwarsa_pada->toIso8601String() }}">-</b></span>@endif</div>
              @if ($sandbox)
                <div class="note"><svg class="ic"><use href="#i-info"/></svg><span>Mode uji coba (sandbox): tidak ada uang sungguhan. Bayar lewat <a href="https://simulator.sandbox.midtrans.com/" target="_blank" rel="noopener" style="text-decoration:underline;font-weight:700">simulator Midtrans</a> dengan {{ $bayar->metode === Pembayaran::METODE_QRIS ? 'menyalin URL gambar QRIS' : 'nomor VA di atas' }}.</span></div>
              @endif
            </div>
            <details class="ganti" style="margin-top:18px" @if ($errors->any()) open @endif><summary>Ganti metode pembayaran</summary>
          @else
            @if ($gagal)
              <div class="state bad"><svg class="ic"><use href="#i-info"/></svg><span>Pembayaran sebelumnya <b>gagal atau kedaluwarsa</b>. Silakan pilih metode pembayaran lagi.</span></div>
            @elseif ($tunai)
              <div class="state"><svg class="ic"><use href="#i-cash"/></svg><span>Kamu memilih <b>bayar tunai</b>. {{ $pesanan->isDelivery() ? 'Siapkan uang saat pesanan diantar kurir.' : 'Bayar di kasir saat mengambil pesanan.' }} Pesanan menunggu konfirmasi kasir.</span></div>
            @endif
          @endif

          <form method="post" action="{{ route('pelanggan.pembayaran.store', $pesanan) }}" id="fBayar">
            @csrf
            <input type="hidden" name="metode" id="metode" value="{{ $metode }}">
            <input type="hidden" name="bank" id="bankIn" value="{{ $bankPilih }}">
            <div class="card-head" style="margin:14px 0 6px"><div><h2>Pilih metode pembayaran</h2><p style="font-size:13.5px;opacity:.7;margin-top:2px">Virtual Account &amp; QRIS terverifikasi otomatis</p></div></div>
            <div class="g3" id="pay">
              <button type="button" @class(['opt', 'on' => $metode === Pembayaran::METODE_TRANSFER]) data-p="transfer" @disabled(! $midtransAktif)><div class="top"><span class="ico"><svg class="ic"><use href="#i-card"/></svg></span><span class="rad"></span></div><b>Virtual Account</b><small>BCA, BNI, BRI, Permata</small></button>
              <button type="button" @class(['opt', 'on' => $metode === Pembayaran::METODE_QRIS]) data-p="qris" @disabled(! $midtransAktif)><div class="top"><span class="ico"><svg class="ic"><use href="#i-qr"/></svg></span><span class="rad"></span></div><b>QRIS</b><small>Semua m-banking &amp; e-wallet</small></button>
              <button type="button" @class(['opt', 'on' => $metode === Pembayaran::METODE_TUNAI]) data-p="tunai"><div class="top"><span class="ico"><svg class="ic"><use href="#i-cash"/></svg></span><span class="rad"></span></div><b>Tunai</b><small>{{ $pesanan->isDelivery() ? 'Bayar ke kurir / kasir' : 'Bayar di kasir' }}</small></button>
            </div>
            @unless ($midtransAktif)
              <div class="note" style="margin-top:14px"><svg class="ic"><use href="#i-info"/></svg><span>Pembayaran Virtual Account &amp; QRIS belum diaktifkan. Untuk sementara gunakan tunai.</span></div>
            @endunless
            <div @class(['panel', 'on' => $metode === Pembayaran::METODE_TRANSFER]) data-panel="transfer">
              <div class="eyebrow" style="font-size:10.5px">Pilih bank</div>
              <div class="banks" id="banks">@foreach ($bank as $kode => $nama)<button type="button" data-bank="{{ $kode }}" @class(['on' => $bankPilih === $kode])>{{ $nama }}</button>@endforeach</div>
              <div class="note" style="margin-top:14px"><svg class="ic"><use href="#i-info"/></svg><span>Nomor Virtual Account dibuat khusus untuk pesanan ini. Tidak perlu unggah bukti, pembayaran dicek otomatis.</span></div>
            </div>
            <div @class(['panel', 'on' => $metode === Pembayaran::METODE_QRIS]) data-panel="qris">
              <div class="note"><svg class="ic"><use href="#i-info"/></svg><span>Kode QRIS dibuat khusus untuk pesanan ini dan bisa dibayar dari aplikasi bank atau e-wallet apa saja. Tidak perlu unggah bukti.</span></div>
            </div>
            <div @class(['panel', 'on' => $metode === Pembayaran::METODE_TUNAI]) data-panel="tunai">
              <div class="note"><svg class="ic"><use href="#i-info"/></svg><span>{{ $pesanan->isDelivery() ? 'Bayar tunai ke kurir saat pesanan tiba.' : 'Bayar tunai di kasir.' }} Kasir akan mengonfirmasi pesananmu.</span></div>
            </div>
            <button class="btn blk" type="submit" id="kirim" style="margin-top:18px;width:100%">Lanjutkan Pembayaran <svg class="ic"><use href="#i-arr"/></svg></button>
          </form>
          @if ($menungguOtomatis)</details>@endif
        @endif
      </section>
    </div>

    <aside class="summary">
      <div class="sum-card">
        <div class="sum-body"><div class="eyebrow" style="margin-bottom:12px">Rincian tagihan</div>
          @foreach ($pesanan->detail as $d)
            <div class="sum-row"><span>{{ $d->jumlah }}× {{ $d->menu->nama_menu }}</span><span>{{ $rp($d->subtotal) }}</span></div>
          @endforeach
          <div class="hr" style="border-top:1.5px dashed var(--m60);margin:12px 0 6px;opacity:.6"></div>
          <div class="sum-row"><span>Subtotal</span><span>{{ $rp($pesanan->subtotal) }}</span></div>
          <div class="sum-row"><span>Pajak &amp; biaya layanan</span><span>{{ $rp($pesanan->pajakDanLayanan()) }}</span></div>
          @if ($pesanan->isDelivery())<div class="sum-row"><span>Ongkos kirim ({{ $pesanan->pengiriman?->zona?->nama_zona }})</span><span>{{ $rp($pesanan->ongkir) }}</span></div>@endif
        </div>
        <div class="sum-total"><div class="t"><span class="eyebrow" style="opacity:.8">Total pembayaran</span><b>{{ $rp($pesanan->total) }}</b></div>
          <a class="btn cr blk" href="{{ route('pelanggan.tracking', ['pesanan' => $pesanan->id]) }}">Lacak Pesanan <svg class="ic"><use href="#i-arr"/></svg></a>
          <small>{{ $lunas ? 'Pembayaran sudah diterima' : 'Status pesanan bisa dipantau kapan saja' }}</small></div>
      </div>
    </aside>
  </div>
</main>
@endsection

@push('modals')
@if (session('pesanan_baru'))
<div class="ovl on" id="ovlBaru"><div class="modal">
  <div class="top stripes"><div class="stamp-out" style="display:inline-block"><div class="stamp" style="padding:24px 34px">
    <div class="ok"><svg class="ic" style="width:36px;height:36px;stroke-width:2.6"><use href="#i-check"/></svg></div><h3>Pesanan Dibuat!</h3></div></div></div>
  <div style="padding:22px 28px 6px"><div class="eyebrow" style="opacity:.7">Nomor pesanan</div><div class="ord">{{ $pesanan->kode() }}</div>
    <span class="pill b" style="font-size:13px;padding:7px 14px">Menunggu Pembayaran</span>
    <p style="font:500 14px/1.5 var(--f-body);opacity:.75;margin:14px 0 18px">Pilih metode pembayaran. Pembayaran Virtual Account &amp; QRIS dicek otomatis dan pesanan langsung diteruskan ke dapur.</p></div>
  <div class="bot"><button class="btn blk" type="button" data-close-ovl>Bayar Sekarang <svg class="ic"><use href="#i-arr"/></svg></button><a class="btn o blk" href="{{ route('pelanggan.beranda') }}">Kembali ke Beranda</a></div>
</div></div>
@endif
@endpush

@push('scripts')
<script>
  (function () {
    document.querySelectorAll('.ovl').forEach(function (o) {
      o.addEventListener('click', function (e) { if (e.target === o || e.target.closest('[data-close-ovl]')) o.classList.remove('on'); });
    });

    /* pilih metode & bank */
    var pay = document.getElementById('pay');
    pay && pay.addEventListener('click', function (e) {
      var b = e.target.closest('.opt'); if (!b || b.disabled) return;
      metode.value = b.dataset.p;
      pay.querySelectorAll('.opt').forEach(function (x) { x.classList.toggle('on', x === b); });
      document.querySelectorAll('.panel').forEach(function (x) { x.classList.toggle('on', x.dataset.panel === b.dataset.p); });
    });
    var banks = document.getElementById('banks');
    banks && banks.addEventListener('click', function (e) {
      var b = e.target.closest('button'); if (!b) return;
      bankIn.value = b.dataset.bank; banks.querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x === b); });
    });
    var f = document.getElementById('fBayar');
    f && f.addEventListener('submit', function () { kirim.disabled = true; kirim.textContent = 'Memproses…'; });

    var salin = document.getElementById('salin');
    salin && salin.addEventListener('click', function () {
      var no = noVa.textContent.trim();
      (navigator.clipboard ? navigator.clipboard.writeText(no) : Promise.reject()).then(function () { pToast('Nomor VA disalin'); }, function () { pToast(no); });
    });

    /* hitung mundur masa berlaku */
    var sisa = document.getElementById('sisa');
    if (sisa) {
      var sampai = new Date(sisa.dataset.sampai).getTime();
      var tik = function () {
        var d = Math.max(0, Math.floor((sampai - Date.now()) / 1000));
        sisa.textContent = Math.floor(d / 3600) ? Math.floor(d / 3600) + ' j ' + Math.floor(d % 3600 / 60) + ' m' : Math.floor(d / 60) + ' m ' + String(d % 60).padStart(2, '0') + ' d';
        if (!d) location.reload();
      };
      tik(); setInterval(tik, 1000);
    }

    /* cek status pembayaran otomatis */
    @if ($menungguOtomatis)
      var cek = function () {
        sendJson(@json(route('pelanggan.pembayaran.status', $pesanan)), 'GET').then(function (r) {
          if (r.lunas || r.gagal) location.reload(); else setTimeout(cek, 5000);
        }).catch(function () { setTimeout(cek, 8000); });
      };
      setTimeout(cek, 3000);
    @endif
  })();
</script>
@endpush
