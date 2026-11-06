@extends('layouts.panel')

@use('App\Models\Pembayaran')
@use('App\Models\Pesanan')

@section('title', 'Pembayaran')
@section('error-sendiri', '1')
@section('crumb', 'Kasir / Pembayaran')
@section('heading', 'Proses Pembayaran')
@section('sub', 'Pembayaran tunai di kasir dan cetak struk · VA & QRIS online lunas otomatis')

@php
  $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
  $bayar = $pesanan?->pembayaran;
  $metode = old('metode', $bayar?->metode ?? Pembayaran::METODE_TUNAI);
  $total = (float) ($pesanan?->total ?? 0);
  // pilihan cepat: uang pas, lalu dibulatkan ke atas ke 10rb, 50rb, 100rb
  $cepat = collect([$total, ceil($total / 10000) * 10000, ceil($total / 50000) * 50000, ceil($total / 100000) * 100000])->unique()->values();
  $ikonMetode = [Pembayaran::METODE_TUNAI => 'i-cash', Pembayaran::METODE_TRANSFER => 'i-card', Pembayaran::METODE_QRIS => 'i-grid'];
@endphp

@push('styles')
<style>
  .qi { display: block; width: 100%; text-align: left; padding: 14px; border-radius: 16px; margin-bottom: 8px; box-shadow: inset 0 0 0 1.2px var(--m15); }
  .qi:hover { box-shadow: inset 0 0 0 1.5px var(--m); } .qi.on { background: var(--m); color: var(--cr); box-shadow: none; }
  .key { height: 48px; border-radius: 14px; box-shadow: inset 0 0 0 1.4px var(--m15); font: 700 20px var(--f-title); }
  .key:hover { background: var(--pp); }
  .mt { display: none; } .mt.on { display: block; }
  .btn[disabled] { opacity: .45; cursor: not-allowed; }
  .struk .sl { border-top: 1.5px dashed var(--m60); margin: 12px 0; }
  .struk .sr { display: flex; justify-content: space-between; gap: 10px; padding: 2px 0; }
  .struk .mut { opacity: .7; }
  #chg.kurang { color: var(--m); font-size: 20px !important; }
  .tabs { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 16px; }
  .tabs a { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 16px; border-radius: 999px; box-shadow: inset 0 0 0 1.4px var(--m); font: 700 13px var(--f-accent); }
  .tabs a.on { background: var(--m); color: var(--cr); box-shadow: none; }
  .lunas-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1.2px solid var(--m15); }
  .lunas-row:last-child { border-bottom: 0; }
</style>
@endpush

@section('content')
  @if ($errors->any())<div class="alert" role="alert">{{ $errors->first() }}</div>@endif

  <nav class="tabs" aria-label="Daftar pembayaran">
    <a @class(['on' => $tab === 'belum']) href="{{ route('kasir.pembayaran') }}">Belum Lunas <span class="pill c" style="font-size:11px;padding:2px 8px">{{ $daftar->count() }}</span></a>
    <a @class(['on' => $tab === 'lunas']) href="{{ route('kasir.pembayaran', ['tab' => 'lunas']) }}">Sudah Lunas Hari Ini <span class="pill c" style="font-size:11px;padding:2px 8px">{{ $lunasHariIni->count() }}</span></a>
  </nav>

  @if ($tab === 'lunas')
    <div class="card">
      <div class="card-head"><div><h2>Sudah Lunas Hari Ini</h2><p>Cetak ulang struk bila printer macet atau pelanggan meminta struk lagi.</p></div></div>
      @forelse ($lunasHariIni as $p)
        <div class="lunas-row">
          <div style="min-width:0"><b style="font:700 14px var(--f-title)">{{ $p->kode() }}</b> · {{ $p->namaPemesan() }}
            <div style="font-size:12.5px;opacity:.7">{{ $p->layananLabel() }} · {{ $p->pembayaran->metodeLabel() }} · {{ $p->pembayaran->waktu_bayar->format('H:i') }}</div></div>
          <div style="display:flex;align-items:center;gap:12px;flex:none"><b style="font:700 14px var(--f-title)">{{ $rp($p->total) }}</b>
            <a class="btn o sm" href="{{ route('kasir.struk', $p) }}" target="_blank" rel="noopener"><svg class="ic" style="width:15px;height:15px"><use href="#i-printer"/></svg>Cetak ulang struk</a></div>
        </div>
      @empty
        <div class="empty" style="padding:40px 16px">Belum ada pembayaran lunas hari ini.</div>
      @endforelse
    </div>
  @elseif ($daftar->isEmpty())
    <div class="card"><div class="empty" style="padding:50px 16px"><svg class="ic" style="width:34px;height:34px;margin-bottom:10px"><use href="#i-check"/></svg><br>Semua pesanan sudah lunas.</div></div>
  @else
  <form method="post" action="{{ route('kasir.pembayaran.lunas', $pesanan) }}" id="fLunas">
    @csrf
    <input type="hidden" name="metode" id="metode" value="{{ $metode }}">
    <input type="hidden" name="uang_diterima" id="uangHidden" value="">
    <div class="grid split" style="grid-template-columns:minmax(250px,.3fr) minmax(0,1fr) minmax(250px,.32fr);align-items:start">
      <div class="card" style="padding:16px"><div class="card-head" style="margin-bottom:10px"><h2>Belum Lunas</h2><span class="pill m">{{ $daftar->count() }}</span></div>
        @foreach ($daftar as $p)
          @php $mt = $p->pembayaran?->metode ?? Pembayaran::METODE_TUNAI; @endphp
          <a @class(['qi', 'on' => $pesanan->id === $p->id]) href="{{ route('kasir.pembayaran', ['pesanan' => $p->id]) }}#detail"><div style="display:flex;justify-content:space-between;align-items:center"><b style="font:700 14px var(--f-title)">{{ $p->kode() }}</b><span @class(['pill', 'b' => $mt !== Pembayaran::METODE_TUNAI, 'c' => $pesanan->id === $p->id]) style="font-size:10.5px;padding:3px 9px"><svg class="ic" style="width:12px;height:12px"><use href="#{{ $ikonMetode[$mt] }}"/></svg>{{ Pembayaran::LABEL_METODE[$mt] }}</span></div>
            <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:12.5px;gap:8px"><span style="opacity:.85;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $p->namaPemesan() }} · {{ $p->layananLabel() }}</span><b style="font:700 13.5px var(--f-title);white-space:nowrap">{{ $rp($p->total) }}</b></div></a>
        @endforeach
      </div>

      <div class="card" id="detail">
        <div class="card-head"><div><h2>{{ $pesanan->kode() }} · {{ $pesanan->namaPemesan() }}</h2><p>{{ $pesanan->layananDetail() }}{{ $pesanan->kasir ? ' · kasir '.$pesanan->kasir->nama : '' }}</p></div>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap"><span class="pill {{ $pesanan->statusPill() }}">{{ $pesanan->statusLabel() }}</span>
            <button type="button" class="btn o sm js-batal" data-url="{{ route('kasir.pesanan.batal', $pesanan) }}" data-kode="{{ $pesanan->kode() }}" data-dimasak="{{ $pesanan->tiketDapur && $pesanan->tiketDapur->status_masak !== \App\Models\TiketDapur::STATUS_ANTRE ? 1 : 0 }}"><svg class="ic" style="width:15px;height:15px"><use href="#i-x"/></svg>Batalkan</button></div></div>
        <div class="seg" id="mtd" style="margin-bottom:16px">
          @foreach (Pembayaran::LABEL_METODE as $k => $l)
            <button type="button" @class(['on' => $metode === $k]) data-m="{{ $k }}"><svg class="ic" style="width:15px;height:15px"><use href="#{{ $ikonMetode[$k] }}"/></svg> {{ $l }}</button>
          @endforeach
        </div>
        <div style="display:grid;gap:6px;padding:14px;border-radius:16px;background:var(--pp);font-size:13.5px">
          @foreach ($pesanan->detail as $d)<div style="display:flex;justify-content:space-between;gap:10px"><span>{{ $d->jumlah }}× {{ $d->menu->nama_menu }}</span><span>{{ $rp($d->subtotal) }}</span></div>@endforeach
          <div style="display:flex;justify-content:space-between"><span>Pajak + biaya layanan</span><span>{{ $rp($pesanan->pajakDanLayanan()) }}</span></div>
          @if ($pesanan->isDelivery())<div style="display:flex;justify-content:space-between"><span>Ongkos kirim</span><span>{{ $rp($pesanan->ongkir) }}</span></div>@endif
          <div style="display:flex;justify-content:space-between;padding-top:8px;border-top:1.5px dashed var(--m60)"><span class="eyebrow">Total tagihan</span><b style="font:700 24px var(--f-title)">{{ $rp($total) }}</b></div></div>

        <div @class(['mt', 'on' => $metode === Pembayaran::METODE_TUNAI]) data-mt="tunai" style="margin-top:16px">
          <div class="field"><label for="cash">Uang diterima</label><div class="in" style="min-height:58px;font-size:22px"><span class="suf" style="opacity:1;font:700 18px var(--f-title)">Rp</span><input id="cash" inputmode="numeric" autocomplete="off" value="{{ old('uang_diterima') ? number_format((float) old('uang_diterima'), 0, ',', '.') : '' }}" placeholder="0" style="font:700 24px var(--f-title)"></div></div>
          <div class="chips" style="margin-top:10px" id="quick">@foreach ($cepat as $i => $v)<button type="button" data-v="{{ (int) $v }}">{{ $i === 0 ? 'Uang pas' : $rp($v) }}</button>@endforeach</div>
          <div class="stamp-out" style="margin-top:16px"><div class="stamp" style="padding:16px 20px;background:var(--bl);display:flex;justify-content:space-between;align-items:center"><span class="eyebrow">Kembalian</span><b id="chg" style="font:700 28px var(--f-title)">Rp 0</b></div></div></div>

        <div @class(['mt', 'on' => $metode !== Pembayaran::METODE_TUNAI]) data-mt="nontunai" style="margin-top:16px">
          <div class="note"><svg class="ic"><use href="#i-info"/></svg><span>Untuk pembeli di tempat yang membayar transfer / QRIS restoran: pastikan dana sudah masuk (cek mutasi atau aplikasi QRIS) sebelum menandai lunas.</span></div>
        </div>
      </div>

      <div class="grid" style="align-content:start">
        <div class="card" style="padding:14px" id="padWrap"><div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px" id="pad">@foreach (['1','2','3','4','5','6','7','8','9','000','0','⌫'] as $k)<button type="button" class="key" data-k="{{ $k }}">{{ $k }}</button>@endforeach</div></div>
        <label class="card soft" style="display:flex;gap:10px;align-items:center;padding:14px 16px;font:600 13px var(--f-body);cursor:pointer"><span class="toggle"><input type="checkbox" name="cetak" value="1" checked><i></i></span>Cetak struk setelah lunas</label>
        <button class="btn lg aksi-utama" type="submit" id="btnLunas"><svg class="ic"><use href="#i-check"/></svg>Tandai Lunas</button>
        <p style="font-size:12px;opacity:.6;text-align:center">Pembayaran lunas otomatis dicatat sebagai pemasukan untuk laporan owner.</p>
      </div>
    </div>
  </form>
  @endif
@endsection

@push('modals')
@if ($struk)
<div class="ovl on" id="mStruk"><div class="modal" style="width:min(720px,100%)">
  <div class="mh"><div><div class="eyebrow" style="opacity:.7">Pembayaran berhasil · Lunas</div><h3>Cetak Struk</h3></div><button class="icon-btn" data-close><svg class="ic"><use href="#i-x"/></svg></button></div>
  <div class="mb" style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:20px;align-items:start">
    <div class="stripes" style="border-radius:20px;padding:20px"><div style="background:var(--pp);padding:20px 22px;font-size:13px;border-radius:4px">@include('kasir.partials.struk-isi', ['pesanan' => $struk])</div></div>
    <div style="display:grid;gap:12px"><div style="display:flex;gap:14px;align-items:center;padding:16px;border-radius:18px;background:var(--m);color:var(--cr)"><span style="width:48px;height:48px;border-radius:50%;background:var(--cr);color:var(--m);display:grid;place-items:center"><svg class="ic" style="width:24px;height:24px"><use href="#i-check"/></svg></span>
      <div><div class="eyebrow" style="opacity:.8">{{ $struk->kode() }}</div><b style="font:700 24px var(--f-title)">Rp {{ number_format($struk->total, 0, ',', '.') }}</b></div></div>
      @if ($struk->pembayaran->kembalian() !== null)<div class="note"><svg class="ic"><use href="#i-cash"/></svg><span>Kembalian <b>Rp {{ number_format($struk->pembayaran->kembalian(), 0, ',', '.') }}</b></span></div>@endif
      <div class="note"><svg class="ic"><use href="#i-info"/></svg><span>Status pembayaran Lunas dan pemasukan tercatat pukul {{ $struk->pembayaran->waktu_bayar->format('H:i') }}.</span></div>
      <a class="btn lg" href="{{ route('kasir.struk', $struk) }}" target="_blank" rel="noopener"><svg class="ic"><use href="#i-printer"/></svg>Cetak Struk</a><button class="btn o lg" data-close>Tutup</button>
      <p style="font-size:12px;opacity:.65;text-align:center">Struk bisa dicetak ulang dari tab <b>Sudah Lunas Hari Ini</b>.</p></div></div>
</div></div>
@endif
@endpush

@push('scripts')
@if ($pesanan && $tab === 'belum')
<script>
  (function () {
    var total = {{ $total }};
    var cash = document.getElementById('cash');
    var num = function (s) { return +String(s || '').replace(/\D/g, '') || 0; };
    function setMetode(m) {
      metode.value = m;
      document.querySelectorAll('#mtd button').forEach(function (x) { x.classList.toggle('on', x.dataset.m === m); });
      document.querySelectorAll('.mt').forEach(function (x) { x.classList.toggle('on', x.dataset.mt === (m === 'tunai' ? 'tunai' : 'nontunai')); });
      padWrap.hidden = m !== 'tunai';
      tombol();
    }
    function chgCalc() {
      var v = num(cash.value); uangHidden.value = v || '';
      if (v >= total) { chg.textContent = rp(v - total); chg.classList.remove('kurang'); }
      else { chg.textContent = v ? 'Kurang ' + rp(total - v) : 'Rp 0'; chg.classList.toggle('kurang', !!v); }
      tombol();
    }
    function tombol() {
      btnLunas.disabled = metode.value === 'tunai' && num(cash.value) < total;
    }
    mtd.addEventListener('click', function (e) { var b = e.target.closest('button'); if (b) setMetode(b.dataset.m); });
    quick.addEventListener('click', function (e) { var b = e.target.closest('button'); if (!b) return; cash.value = (+b.dataset.v).toLocaleString('id-ID'); chgCalc(); });
    pad.addEventListener('click', function (e) {
      var k = e.target.dataset.k; if (!k) return; var v = String(num(cash.value) || '');
      v = k === '⌫' ? v.slice(0, -1) : (v + k).replace(/^0+/, ''); cash.value = v ? (+v).toLocaleString('id-ID') : ''; chgCalc();
    });
    cash.addEventListener('input', function () { var v = num(cash.value); cash.value = v ? v.toLocaleString('id-ID') : ''; chgCalc(); });
    fLunas.addEventListener('submit', function (e) {
      var kembali = num(cash.value) - total;
      // salah ketik nominal (mis. kelebihan satu nol) -> minta konfirmasi
      if (metode.value === 'tunai' && (kembali > total || kembali > 100000)
        && !confirm('Kembalian ' + rp(kembali) + ' untuk tagihan ' + rp(total) + '. Nominal uang diterima sudah benar?')) {
        e.preventDefault(); return;
      }
      btnLunas.disabled = true;
    });
    setMetode(metode.value); chgCalc();
  })();
</script>
@endif
@endpush

@include('kasir.partials.modal-batal')
