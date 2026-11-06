@extends('layouts.pelanggan')

@section('title', 'Lacak Pesanan')

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

  .od { display: block; width: 100%; text-align: left; padding: 16px; border-radius: 0 20px 20px 20px; position: relative; margin-top: 12px; box-shadow: inset 0 0 0 1.4px var(--m15); background: var(--cr); }
  .od::before { content: ''; position: absolute; left: 0; top: -11px; width: 74px; height: 12px; border-radius: 12px 12px 0 0; background: inherit; box-shadow: inset 1.4px 1.4px 0 var(--m15), inset -1.4px 0 0 var(--m15); }
  .od.on { background: var(--m); color: var(--cr); box-shadow: none; } .od.on::before { box-shadow: none; }
  .tl { position: relative; padding-left: 46px; margin-top: 8px; }
  .tl .st { position: relative; padding-bottom: 22px; }
  .tl .st::before { content: ''; position: absolute; left: -31px; top: 34px; bottom: 0; width: 2.5px; background: var(--m15); }
  .tl .st.done::before { background: var(--m); } .tl .st:last-child::before { display: none; }
  .tl .dot { position: absolute; left: -46px; top: 0; width: 32px; height: 32px; border-radius: 50%; display: grid; place-items: center; background: var(--cr); box-shadow: inset 0 0 0 1.8px var(--m); }
  .tl .done .dot { background: var(--m); color: var(--cr); box-shadow: none; }
  .tl .now .dot { background: var(--bl); box-shadow: 0 0 0 6px rgba(178, 194, 210, .5), inset 0 0 0 1.8px var(--m); }
  .tl b { font: 600 16px var(--f-title); display: block; } .tl small { font: 500 13px var(--f-body); opacity: .72; } .tl .todo { opacity: .55; }
  .split { display: grid; grid-template-columns: minmax(270px, .32fr) minmax(0, 1fr); gap: clamp(18px, 2vw, 32px); align-items: start; }
  .split2 { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr); gap: 24px; }
  .flows { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
  .flow { padding: 14px 16px; border-radius: 16px; box-shadow: inset 0 0 0 1.3px var(--m15); font-size: 12.5px; line-height: 1.6; }
  .flow.on { background: var(--bl); box-shadow: none; }
  .flow b { display: flex; gap: 8px; align-items: center; font: 700 14px var(--f-title); margin-bottom: 4px; }
  @media (max-width: 1100px) { .split2, .flows { grid-template-columns: minmax(0, 1fr); } }
  @media (max-width: 900px) { .split { grid-template-columns: minmax(0, 1fr); } }
</style>
@endpush

@use('App\Models\Pesanan')
@use('App\Models\Pembayaran')
@php
  $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
  $waktu = fn ($t) => $t->isToday() ? 'Hari ini '.$t->format('H:i') : ($t->isYesterday() ? 'Kemarin '.$t->format('H:i') : $t->translatedFormat('d M Y'));
  $aktif = $pesanan && ! in_array($pesanan->status, [Pesanan::STATUS_SELESAI, Pesanan::STATUS_DIBATALKAN], true);
  $pillStatus = fn ($p) => match ($p->status) {
      Pesanan::STATUS_SELESAI => 'o', Pesanan::STATUS_DIBATALKAN => 'o', Pesanan::STATUS_DIANTAR, Pesanan::STATUS_SIAP => 'm', default => 'b',
  };
  $statusTeks = function ($p) {
      if ($p->status === Pesanan::STATUS_DIPROSES && $p->tiketDapur?->status_masak === \App\Models\TiketDapur::STATUS_DIMASAK) return 'Sedang Dimasak';
      if ($p->status === Pesanan::STATUS_DIANTAR) return 'Dalam Perjalanan';
      return $p->statusLabel();
  };
@endphp

@push('styles')
<style>
  .od { text-decoration: none; color: inherit; display: block; }
  .od > div:first-child { flex-wrap: wrap; } .od b { white-space: nowrap; }
  .empty-track { padding: 40px 20px; text-align: center; }
  .empty-track b { display: block; font: 600 20px var(--f-title); margin-bottom: 6px; }
  .empty-track p { opacity: .7; margin-bottom: 18px; }
  .batal-note { margin: 0 24px 4px; padding: 12px 16px; border-radius: 14px; background: var(--m); color: var(--cr); font: 600 13.5px/1.5 var(--f-body); }
</style>
@endpush

@section('content')
<main class="wrap">
  <div class="page-head"><div><div class="crumb"><a href="{{ route('pelanggan.beranda') }}">Beranda</a><span>/</span><span>Lacak Pesanan</span></div><h1>Lacak Status Pesanan</h1></div></div>

  @if ($daftar->isEmpty())
    <section class="card empty-track">
      <b>Belum ada pesanan</b><p>Pesanan yang kamu buat akan muncul di sini beserta status terbarunya.</p>
      <a class="btn" href="{{ route('pelanggan.beranda') }}#menu">Pesan Sekarang</a>
    </section>
  @else
  <div class="split">
    <aside class="card"><div class="card-head" style="margin-bottom:4px"><h2>Pesanan saya</h2><span class="pill b">{{ $daftar->count() }}</span></div>
      @foreach ($daftar as $p)
        <a @class(['od', 'on' => $pesanan->id === $p->id]) href="{{ route('pelanggan.tracking', ['pesanan' => $p->id]) }}"><div style="display:flex;justify-content:space-between;align-items:center;gap:8px"><b style="font:700 16px var(--f-title)">{{ $p->kode() }}</b><span class="pill {{ $pillStatus($p) }}">{{ $statusTeks($p) }}</span></div>
          <div style="font-size:13px;opacity:.8;margin-top:6px">{{ $waktu($p->waktu_pesan) }} · {{ $p->layananDetail() }} · {{ $p->detail->sum('jumlah') }} item</div></a>
      @endforeach
    </aside>

    <div style="display:grid;gap:clamp(16px,1.6vw,24px)">
      <section class="card" style="padding:0;overflow:hidden">
        <div class="stripes" style="padding:22px 24px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
          <div class="stamp-out"><div class="stamp" style="padding:18px 24px"><div class="eyebrow">Nomor pesanan</div><div style="font:700 24px var(--f-title);margin-top:4px">{{ $pesanan->kode() }}</div><div style="font-size:12.5px;opacity:.7">{{ $pesanan->layananDetail() }} · {{ $pesanan->pembayaran ? $pesanan->pembayaran->metodeLabel().' ('.$pesanan->bayarLabel().')' : 'Belum bayar' }}</div></div></div>
          <span class="pill m" style="font-size:13px;padding:8px 16px">@if ($pesanan->status === Pesanan::STATUS_DIANTAR)<svg class="ic" style="width:15px;height:15px"><use href="#i-truck"/></svg>@endif{{ $statusTeks($pesanan) }}</span></div>

        @if ($pesanan->status === Pesanan::STATUS_DIBATALKAN)
          <div class="batal-note" style="margin-top:20px">Pesanan ini dibatalkan oleh kasir{{ $pesanan->alasan_batal ? '. Alasan: '.$pesanan->alasan_batal : '' }}. Jika sudah membayar, hubungi restoran di {{ config('resto.telepon') }}.</div>
        @elseif (! $pesanan->isLunas())
          <div style="margin:20px 24px 0;display:flex;gap:12px;align-items:center;flex-wrap:wrap;padding:12px 16px;border-radius:16px;background:var(--pp)">
            <svg class="ic"><use href="#i-card"/></svg>
            <span style="flex:1;min-width:200px;font-size:13.5px">
              @if (! $pesanan->pembayaran) Pesanan belum dibayar. Pilih Virtual Account, QRIS, atau tunai.
              @elseif ($pesanan->pembayaran->status_bayar === Pembayaran::STATUS_DITOLAK || $pesanan->pembayaran->isKedaluwarsa()) <b>Pembayaran gagal / kedaluwarsa.</b> Pilih metode pembayaran lagi.
              @elseif ($pesanan->pembayaran->metode === Pembayaran::METODE_TUNAI) Pembayaran tunai {{ $pesanan->isDelivery() ? 'ke kurir saat pesanan tiba' : 'di kasir' }}.
              @else Selesaikan pembayaran <b>{{ $pesanan->pembayaran->metodeLabel() }}</b>. Pesanan langsung masuk dapur setelah lunas. @endif
            </span>
            @if (! $pesanan->pembayaran || $pesanan->pembayaran->status_bayar !== Pembayaran::STATUS_LUNAS)
              <a class="btn sm" href="{{ route('pelanggan.pembayaran', $pesanan) }}">{{ $pesanan->pembayaran?->isOtomatis() && $pesanan->bayarLabel() === 'Menunggu Pembayaran' ? 'Lihat Cara Bayar' : ($pesanan->pembayaran ? 'Ubah Pembayaran' : 'Bayar Sekarang') }}</a>
            @endif
          </div>
        @endif

        <div class="split2" style="padding:24px">
          <div><div class="eyebrow" style="margin-bottom:10px">Timeline status · {{ $pesanan->layananLabel() }}</div>
            <div class="tl">
              @foreach ($timeline as $l)
                <div class="st {{ $l['state'] }}"><span class="dot">@if ($l['state'] === 'done')<svg class="ic" style="width:15px;height:15px;stroke-width:2.6"><use href="#i-check"/></svg>@elseif ($l['state'] === 'now' && $l['ikon'])<svg class="ic" style="width:16px;height:16px"><use href="#{{ $l['ikon'] }}"/></svg>@endif</span>
                  <div @class(['todo' => $l['state'] === 'todo'])><b>{{ $l['label'] }}</b><small>@if ($l['waktu']){{ $l['waktu'] }} · @endif{{ $l['ket'] }}</small></div></div>
              @endforeach
            </div></div>
          <div style="display:grid;gap:14px;align-content:start">
            @if ($pesanan->isDelivery())
              @php $kirim = $pesanan->pengiriman; @endphp
              <div class="card soft" style="padding:16px"><div class="eyebrow" style="font-size:10px;margin-bottom:10px">Kurir</div>
                @if ($kirim?->kurir)
                  <div style="display:flex;gap:12px;align-items:center"><span style="width:44px;height:44px;border-radius:50%;background:var(--m);color:var(--cr);display:grid;place-items:center;font:700 14px var(--f-title)">{{ $kirim->kurir->inisial() }}</span>
                    <div style="flex:1"><b style="font:600 15px var(--f-title)">{{ $kirim->kurir->nama }}</b><div style="font-size:12.5px;opacity:.7">{{ $kirim->kurir->no_hp }}</div></div><a class="btn o sm" href="tel:{{ preg_replace('/\D/', '', $kirim->kurir->no_hp) }}"><svg class="ic"><use href="#i-phone"/></svg>Hubungi</a></div>
                @else
                  <div style="font-size:13.5px;opacity:.75">Kurir ditugaskan setelah pesanan siap diantar.</div>
                @endif
                <div style="display:flex;gap:8px;font-size:13px;margin-top:12px;padding-top:12px;border-top:1.5px dashed var(--m60)"><svg class="ic"><use href="#i-pin"/></svg>{{ $kirim?->alamat }}</div>
                @if ($kirim?->bukti_kirim)
                  <a href="{{ route('pengiriman.bukti', $kirim) }}" target="_blank" rel="noopener" style="display:flex;gap:10px;align-items:center;margin-top:12px;padding:10px;border-radius:14px;background:var(--pp);font-size:12.5px"><img src="{{ route('pengiriman.bukti', $kirim) }}" alt="Foto bukti pengiriman" style="width:56px;height:56px;border-radius:10px;object-fit:cover;flex:none"><span style="flex:1"><b style="display:block;font:600 13px var(--f-title)">Bukti pengiriman</b>Diterima {{ $kirim->updated_at->translatedFormat('d M, H:i') }} · ketuk untuk memperbesar</span></a>
                @else
                  <div style="display:flex;gap:10px;align-items:center;margin-top:12px;padding:10px;border-radius:14px;background:var(--pp);font-size:12.5px"><span class="stripes" style="width:40px;height:40px;border-radius:10px;flex:none"></span>Foto bukti pengiriman tampil setelah pesanan diterima</div>
                @endif</div>
            @elseif ($pesanan->jenis_layanan === Pesanan::LAYANAN_DINE_IN && $pesanan->nomor_meja)
              <div class="card soft" style="padding:16px"><div class="eyebrow" style="font-size:10px;margin-bottom:6px">Nomor meja</div><b style="font:700 28px var(--f-title)">{{ $pesanan->nomor_meja }}</b></div>
            @endif
            <div class="card soft" style="padding:16px"><div class="eyebrow" style="font-size:10px;margin-bottom:8px">Rincian</div>
              @foreach ($pesanan->detail as $d)
                <div style="display:flex;justify-content:space-between;gap:10px;font-size:14px;padding:4px 0"><span><b style="font-family:var(--f-title)">{{ $d->jumlah }}×</b> {{ $d->menu->nama_menu }}@if ($d->catatan)<small style="display:block;opacity:.65">{{ $d->catatan }}</small>@endif</span><span>{{ $rp($d->subtotal) }}</span></div>
              @endforeach
              <div style="display:flex;justify-content:space-between;font-size:14px;padding:4px 0"><span>Pajak + biaya layanan</span><span>{{ $rp($pesanan->pajakDanLayanan()) }}</span></div>
              @if ($pesanan->isDelivery())<div style="display:flex;justify-content:space-between;font-size:14px;padding:4px 0"><span>Ongkos kirim ({{ $pesanan->pengiriman?->zona?->nama_zona }})</span><span>{{ $rp($pesanan->ongkir) }}</span></div>@endif
              <div style="display:flex;justify-content:space-between;font:700 18px var(--f-title);border-top:1.5px dashed var(--m60);padding-top:8px;margin-top:6px"><span>Total</span><span>{{ $rp($pesanan->total) }}</span></div></div>
          </div>
        </div>
      </section>
      <section class="card"><div class="card-head" style="margin-bottom:12px"><div><h2>Alur status per jenis layanan</h2><p style="font-size:13px;opacity:.7;margin-top:2px">Timeline mengikuti layanan yang dipilih saat memesan</p></div></div>
        <div class="flows">
          <div @class(['flow', 'on' => $pesanan->jenis_layanan === Pesanan::LAYANAN_DINE_IN])><b><svg class="ic" style="width:16px;height:16px"><use href="#i-dine"/></svg>Dine In</b>Dikonfirmasi → Dimasak → Siap Disajikan → Selesai · menampilkan nomor meja</div>
          <div @class(['flow', 'on' => $pesanan->jenis_layanan === Pesanan::LAYANAN_TAKE_AWAY])><b><svg class="ic" style="width:16px;height:16px"><use href="#i-bag"/></svg>Take Away</b>Dikonfirmasi → Dimasak → Siap Diambil → Selesai</div>
          <div @class(['flow', 'on' => $pesanan->isDelivery()])><b><svg class="ic" style="width:16px;height:16px"><use href="#i-truck"/></svg>Delivery</b>Dikonfirmasi → Dimasak → Siap Diantar → Dalam Perjalanan → Diterima · nama kurir &amp; bukti pengiriman</div>
        </div></section>
    </div>
  </div>
  @endif
</main>
@endsection

@if ($aktif)
@push('scripts')
<script>
  // status diperbarui otomatis tiap 30 detik selama pesanan masih berjalan
  setTimeout(function () { if (!document.hidden) location.reload(); else document.addEventListener('visibilitychange', function () { location.reload(); }, { once: true }); }, 30000);
</script>
@endpush
@endif
