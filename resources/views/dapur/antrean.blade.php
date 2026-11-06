@extends('layouts.panel')

@use('App\Models\TiketDapur')

@section('title', 'Antrean Tiket')
@section('error-sendiri', '1')
@section('crumb', 'Dapur / Antrean Tiket')
@section('heading', 'Antrean Tiket Pesanan')
@section('sub', 'Siapkan pesanan sesuai urutan antrean (FIFO) dan perbarui status masakan')

@php
  $ringkas = fn ($p) => $p->detail->map(fn ($d) => $d->jumlah.'× '.$d->menu->nama_menu)->join(', ');
  $tahap = $tiket ? array_search($tiket->status_masak, [TiketDapur::STATUS_ANTRE, TiketDapur::STATUS_DIMASAK, TiketDapur::STATUS_SELESAI], true) : null;
@endphp

@push('styles')
<style>
  .tk { display: flex; gap: 12px; align-items: flex-start; width: 100%; text-align: left; padding: 14px; border-radius: 18px; box-shadow: inset 0 0 0 1.3px var(--m15); margin-bottom: 8px; }
  .tk:hover { box-shadow: inset 0 0 0 1.5px var(--m); }
  .tk.on { background: var(--m); color: var(--cr); box-shadow: none; } .tk.on .pill.st { background: var(--cr); color: var(--m); }
  .fifo { width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center; background: var(--bl); color: var(--m); font: 700 13px var(--f-title); flex: none; }
  .tk.on .fifo { background: var(--cr); }
  .tk .isi { font-size: 12.5px; opacity: .8; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .sp { display: grid; grid-template-columns: repeat(3, 1fr); }
  .sp div { display: flex; flex-direction: column; align-items: center; gap: 8px; text-align: center; font: 700 13px var(--f-accent); position: relative; }
  .sp div::before { content: ''; position: absolute; top: 20px; left: -50%; width: 100%; height: 2.5px; background: var(--m15); }
  .sp div:first-child::before { display: none; } .sp .d::before, .sp .n::before { background: var(--m); }
  .sp i { width: 42px; height: 42px; border-radius: 50%; display: grid; place-items: center; background: var(--cr); box-shadow: inset 0 0 0 1.6px var(--m); position: relative; z-index: 1; font-style: normal; }
  .sp .d i { background: var(--m); color: var(--cr); box-shadow: none; } .sp .n i { background: var(--bl); box-shadow: 0 0 0 6px rgba(178,194,210,.45), inset 0 0 0 1.6px var(--m); }
  .item-row { display: flex; gap: 14px; align-items: baseline; padding: 8px 0; border-bottom: 1px solid var(--m15); }
  .item-row .q { font: 700 26px var(--f-title); min-width: 42px; }
  .item-row .n { font: 600 17px var(--f-title); }
  .item-row small { display: block; font: 500 13px var(--f-body); opacity: .75; margin-top: 2px; }
  .btn[disabled] { opacity: .4; cursor: not-allowed; }
  .no-tk { font: 700 clamp(32px, 4vw, 46px)/1 var(--f-title); margin-top: 8px; }
  @media (max-width: 760px) { .item-row .q { font-size: 22px; min-width: 34px; } .item-row .n { font-size: 15px; } }
</style>
@endpush

@section('content')
  @if ($kritis->isNotEmpty())
    <div class="card f" style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;padding:14px 20px;border-radius:18px;margin-bottom:18px"><svg class="ic" style="width:22px;height:22px"><use href="#i-alert"/></svg><span style="flex:1;min-width:220px;font:600 14px var(--f-accent)">Notifikasi stok kritis: {{ $kritis->join(', ', ' dan ') }} berada pada atau di bawah ROP. Segera lakukan pemesanan ulang.</span><a class="btn cr sm" href="{{ route('dapur.bahan-baku') }}">Lihat bahan baku</a></div>
  @endif
  @if ($errors->any())<div class="alert" role="alert">{{ $errors->first() }}</div>@endif

  @if ($antrean->isEmpty())
    <div class="card"><div class="empty" style="padding:50px 16px"><svg class="ic" style="width:34px;height:34px;margin-bottom:10px"><use href="#i-check"/></svg><br>Antrean kosong. Tiket baru muncul otomatis setelah kasir mengirim pesanan.<br><small style="font-weight:500">{{ $selesaiHariIni }} tiket selesai hari ini</small></div></div>
  @else
  <div class="grid split" style="grid-template-columns:minmax(300px,.4fr) minmax(0,1fr);align-items:start">
    <div class="card" style="padding:16px"><div class="card-head" style="margin-bottom:6px"><h2>Antrean Tiket</h2><span class="pill m">{{ $antrean->count() }}</span></div>
      <p style="font-size:12.5px;opacity:.7;margin-bottom:12px;display:flex;gap:6px;align-items:center"><svg class="ic" style="width:14px;height:14px"><use href="#i-refresh"/></svg>FIFO · urut dari waktu masuk paling awal, tiket baru ditambahkan di akhir</p>
      @foreach ($antrean as $i => $t)
        <a @class(['tk', 'on' => $tiket->id === $t->id]) href="{{ route('dapur.antrean', ['tiket' => $t->id]) }}"><span class="fifo">#{{ $i + 1 }}</span><div style="flex:1;min-width:0">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:8px"><b style="font:700 16px var(--f-title)">{{ $t->kode() }}</b><span @class(['pill st', 'm' => $t->status_masak === TiketDapur::STATUS_DIMASAK, 'b' => $t->status_masak === TiketDapur::STATUS_ANTRE]) style="font-size:10.5px;padding:3px 9px">{{ $t->statusLabel() }}</span></div>
          <div class="isi">{{ $ringkas($t->pesanan) }}</div>
          <div style="display:flex;gap:10px;font-size:12px;opacity:.75;margin-top:6px;flex-wrap:wrap"><span style="display:flex;gap:4px;align-items:center"><svg class="ic" style="width:12px;height:12px"><use href="#i-clock"/></svg>Masuk {{ $t->waktu_masuk->format('H:i') }}</span><span>· {{ $t->pesanan->layananDetail() }}</span></div></div></a>
      @endforeach
    </div>

    <div class="grid" style="align-content:start">
      <div class="card stripes" style="padding:clamp(14px,2vw,26px);box-shadow:none">
        <div class="stamp-out"><div class="stamp" style="padding:clamp(18px,2vw,26px) clamp(18px,2vw,28px)">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap"><div><div class="eyebrow">Detail tiket pesanan</div><div class="no-tk">{{ $tiket->kode() }}</div></div>
            <div style="display:grid;gap:8px;justify-items:end"><span class="pill m">{{ $tiket->pesanan->layananDetail() }}</span><span class="pill b">FIFO #{{ $antrean->search(fn ($x) => $x->id === $tiket->id) + 1 }}</span></div></div>
          <div style="font-size:13px;opacity:.75;margin:12px 0 14px">{{ $tiket->pesanan->kode() }} · {{ $tiket->pesanan->namaPemesan() }} · masuk {{ $tiket->waktu_masuk->format('H:i') }} ({{ $tiket->waktu_masuk->diffForHumans() }})</div>
          <div style="border-top:1.5px dashed var(--m60);padding-top:8px">
            @foreach ($tiket->pesanan->detail as $d)
              <div class="item-row"><b class="q">{{ $d->jumlah }}×</b><div><b class="n">{{ $d->menu->nama_menu }}</b>@if ($d->catatan)<small>Catatan: {{ $d->catatan }}</small>@endif</div></div>
            @endforeach
          </div>
          @if ($kebutuhan)
            @php $adaKurang = collect($kebutuhan)->contains('kurang', true); $a = fn ($n) => \App\Services\BahanBakuService::angka($n); @endphp
            <div style="border-top:1.5px dashed var(--m60);margin-top:12px;padding-top:10px;font-size:13px">
              <div class="eyebrow" style="margin-bottom:6px">Bahan yang akan dipakai</div>
              @foreach ($kebutuhan as $k)
                <div style="display:flex;justify-content:space-between;gap:10px;padding:2px 0;{{ $k['kurang'] ? 'font-weight:700' : '' }}"><span>{{ $k['kurang'] ? '⚠ ' : '' }}{{ $k['nama'] }}</span><span>butuh {{ $a($k['butuh']) }} {{ $k['satuan'] }} · sisa {{ $a($k['stok']) }}</span></div>
              @endforeach
              @if ($adaKurang)<div class="alert" role="alert" style="margin:10px 0 0">Stok bahan bertanda ⚠ tidak cukup. Masakan tetap bisa dimulai, tetapi stoknya akan habis (0).</div>@endif
            </div>
          @endif
          </div></div></div>

      <div class="card"><div class="card-head"><h2>Status Masakan</h2><span class="pill">{{ $tiket->statusLabel() }}</span></div>
        <div class="sp">
          @foreach ([['i-check', 'Antre'], ['i-flame', 'Sedang Dimasak'], ['i-bell', 'Siap']] as $k => [$ikon, $label])
            <div @class(['d' => $k < $tahap, 'n' => $k === $tahap])><i><svg class="ic" style="width:18px;height:18px"><use href="#{{ $ikon }}"/></svg></i>{{ $label }}</div>
          @endforeach
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:22px">
          <form method="post" action="{{ route('dapur.tiket.mulai', $tiket) }}">@csrf<button class="btn lg o" type="submit" style="width:100%" @disabled($tiket->status_masak !== TiketDapur::STATUS_ANTRE)><svg class="ic"><use href="#i-flame"/></svg>Mulai Masak</button></form>
          <form method="post" action="{{ route('dapur.tiket.selesai', $tiket) }}">@csrf<button class="btn lg" type="submit" style="width:100%" @disabled($tiket->status_masak !== TiketDapur::STATUS_DIMASAK)><svg class="ic"><use href="#i-check"/></svg>Selesai · Siap</button></form>
        </div>
        <div class="note" style="margin-top:14px"><svg class="ic"><use href="#i-bell"/></svg><span>Saat ditandai Siap: status pesanan menjadi Siap di kasir dan halaman lacak pelanggan{{ $tiket->pesanan->isDelivery() ? '; tugas pengiriman masuk antrean kurir (FIFO).' : '.' }}</span></div></div>
    </div>
  </div>
  @endif
@endsection

@push('scripts')
<script>setTimeout(function () { if (!document.querySelector('.ovl.on')) location.reload(); }, 20000);</script>
@endpush
