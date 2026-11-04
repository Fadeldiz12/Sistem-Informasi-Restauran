@extends('layouts.panel')

@use('App\Models\Pesanan')
@use('App\Models\PengaturanSistem')

@section('title', 'Input Pesanan')
@section('crumb', 'Kasir / Input Pesanan')
@section('heading', 'Input Pesanan di Tempat')
@section('sub', 'Pesanan pembeli langsung, tiket otomatis masuk antrean dapur')

@php
  $layanan = old('jenis_layanan', Pesanan::LAYANAN_DINE_IN);
  $mejaLama = old('nomor_meja');
  $oldItems = collect(old('items', []))->values();
@endphp

@push('styles')
<style>
  .menu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; }
  .mi { border-radius: 18px; overflow: hidden; box-shadow: inset 0 0 0 1.3px var(--m15); background: var(--cr); transition: .15s; text-align: left; }
  .mi:hover { box-shadow: inset 0 0 0 1.5px var(--m); }
  .mi .ph { position: relative; height: 96px; background: var(--bl); display: grid; place-items: center; overflow: hidden; } .mi .ph img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: block; }
  .mi .plus { width: 26px; height: 26px; border-radius: 50%; background: var(--m); color: var(--cr); display: grid; place-items: center; }
  .mi.out { opacity: .5; cursor: not-allowed; }
  .tables { display: grid; grid-template-columns: repeat(8, 1fr); gap: 6px; }
  .tables button { height: 36px; border-radius: 10px; box-shadow: inset 0 0 0 1.3px var(--m); font: 700 13px var(--f-title); }
  .tables button.busy { background: var(--pp); box-shadow: none; opacity: .55; cursor: not-allowed; } .tables button.on { background: var(--m); color: var(--cr); box-shadow: none; }
  .oi { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 4px 10px; padding: 11px 0; border-bottom: 1px solid var(--m15); }
  .oi .note-in { grid-column: 1 / -1; all: unset; font: 500 12.5px var(--f-body); padding: 6px 10px; border-radius: 10px; background: var(--pp); }
  .qty { display: inline-flex; align-items: center; height: 30px; border-radius: 999px; box-shadow: inset 0 0 0 1.3px var(--m); }
  .qty button { width: 28px; height: 100%; } .qty b { min-width: 18px; text-align: center; font: 700 13px var(--f-title); }
  .svc-p { display: none; } .svc-p.on { display: block; }
  .ord-empty { padding: 18px 4px; font-size: 13px; opacity: .6; text-align: center; }
  @media (max-width: 1280px) { .tables { grid-template-columns: repeat(6, 1fr); } }
  .jump { display: none; }
  @media (max-width: 760px) {
    .menu-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
    .mi .ph { height: 84px; }
    #fOrder { position: static !important; scroll-margin-top: 12px; }
    .tables { grid-template-columns: repeat(6, 1fr); }
    .jump { display: flex; position: fixed; left: 16px; right: 16px; bottom: calc(76px + env(safe-area-inset-bottom)); z-index: 55; align-items: center; gap: 10px;
            padding: 12px 18px; border-radius: 999px; background: var(--m); color: var(--cr); font: 700 14px var(--f-title); box-shadow: 0 8px 22px rgba(40, 40, 40, .25); }
    .jump[hidden] { display: none !important; }
    .jump b { margin-left: auto; }
  }
</style>
@endpush

@section('content')
  @include('partials.auth-alert')
  <div class="grid split" style="grid-template-columns:minmax(0,1fr) minmax(340px,.42fr);align-items:start">
    <div class="card"><div class="card-head">
      <div class="chips" id="katChips"><button class="on" data-k="all">Semua</button>@foreach ($kategori as $k)<button data-k="{{ $k->id }}">{{ $k->nama_kategori }}</button>@endforeach</div>
      <label class="search keep"><svg class="ic"><use href="#i-search"/></svg><input id="cari" placeholder="Cari menu…"></label></div>
      <div class="menu-grid" id="mg">
        @foreach ($menu as $m)
          <button type="button" @class(['mi', 'out' => ! $m->tersedia]) data-id="{{ $m->id }}" data-n="{{ $m->nama_menu }}" data-p="{{ (int) $m->harga }}" data-k="{{ $m->id_kategori }}">
            <div class="ph">@if ($m->fotoUrl())<img src="{{ $m->fotoUrl() }}" alt="" loading="lazy">@else<svg class="ic"><use href="#i-dine"/></svg>@endif @unless ($m->tersedia)<span class="pill m" style="position:absolute;left:8px;top:8px">Habis</span>@endunless</div>
            <div style="padding:10px 12px 12px"><b style="font:600 13.5px/1.3 var(--f-title);display:block;min-height:35px">{{ $m->nama_menu }}</b><div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px"><span style="font:700 13px var(--f-title)">Rp {{ number_format($m->harga, 0, ',', '.') }}</span><span class="plus"><svg class="ic" style="width:15px;height:15px"><use href="#i-plus"/></svg></span></div></div></button>
        @endforeach
      </div>
      <div class="empty" id="mgEmpty" hidden>Menu tidak ditemukan.</div>
    </div>

    <form class="card" method="post" action="{{ route('kasir.input.store') }}" id="fOrder" style="padding:0;overflow:hidden;position:sticky;top:16px">
      @csrf
      <input type="hidden" name="jenis_layanan" id="jenis" value="{{ $layanan }}">
      <input type="hidden" name="nomor_meja" id="meja" value="{{ $mejaLama }}">
      <div id="itemInputs"></div>
      <div style="background:var(--m);color:var(--cr);padding:18px 20px"><div style="display:flex;justify-content:space-between;align-items:center"><h2 style="font:600 19px var(--f-title)">Pesanan Baru</h2><span class="pill b">di tempat</span></div>
        <div class="seg" id="svc" style="margin-top:12px">
          <button type="button" data-s="{{ Pesanan::LAYANAN_DINE_IN }}" style="flex:1;box-shadow:inset 0 0 0 1.4px var(--cr)"><svg class="ic" style="width:15px;height:15px"><use href="#i-dine"/></svg> Dine In</button>
          <button type="button" data-s="{{ Pesanan::LAYANAN_TAKE_AWAY }}" style="flex:1;box-shadow:inset 0 0 0 1.4px var(--cr)"><svg class="ic" style="width:15px;height:15px"><use href="#i-bag"/></svg> Take Away</button></div></div>
      <div style="padding:16px 20px">
        <div class="svc-p" data-p="{{ Pesanan::LAYANAN_DINE_IN }}"><div class="field"><label>Nomor meja</label><div class="tables">
          @for ($i = 1; $i <= $jumlahMeja; $i++)
            @php $no = str_pad($i, 2, '0', STR_PAD_LEFT); @endphp
            <button type="button" data-meja="{{ $no }}" @class(['busy' => in_array($no, $mejaTerpakai, true), 'on' => $mejaLama === $no]) @if (in_array($no, $mejaTerpakai, true)) title="Meja sedang dipakai" @endif>{{ $no }}</button>
          @endfor
        </div></div></div>
        <div class="svc-p" data-p="{{ Pesanan::LAYANAN_TAKE_AWAY }}"><div class="field"><label for="nama">Nama pelanggan (opsional)</label><div class="in"><svg class="ic"><use href="#i-user"/></svg><input type="text" id="nama" name="nama_pemesan" value="{{ old('nama_pemesan') }}" maxlength="100" placeholder="Untuk dipanggil saat pesanan siap"></div></div></div>
        <div class="eyebrow" style="margin-top:16px">Menu, jumlah &amp; catatan</div><div id="ol"></div>
        <div style="display:grid;gap:4px;margin-top:14px;font-size:13.5px"><div style="display:flex;justify-content:space-between"><span>Subtotal</span><span id="sub">Rp 0</span></div>
          <div style="display:flex;justify-content:space-between"><span>Pajak {{ PengaturanSistem::persen($pengaturan->pajak) }}% + layanan {{ PengaturanSistem::persen($pengaturan->biaya_layanan) }}%</span><span id="tx">Rp 0</span></div></div>
        <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-top:10px;padding-top:10px;border-top:1.5px dashed var(--m60)"><span class="eyebrow">Total tagihan</span><b id="tot" style="font:700 26px var(--f-title)">Rp 0</b></div>
        <button class="btn lg" type="submit" id="btnSave" style="width:100%;margin-top:14px" disabled><svg class="ic"><use href="#i-receipt"/></svg>Simpan Pesanan</button>
        <p style="font-size:12px;opacity:.6;text-align:center;margin-top:10px">Waktu masuk dicatat, tiket otomatis masuk antrean dapur</p></div>
    </form>
  </div>
@endsection

@push('modals')
<a href="#fOrder" class="jump" id="jump" hidden><svg class="ic"><use href="#i-receipt"/></svg><span id="jumpN">0 item</span><b id="jumpT">Rp 0</b></a>
@if ($baru)
<div class="ovl on" id="mDone"><div class="modal sm">
  <div class="big-ic" style="background:var(--m);color:var(--cr)"><svg class="ic"><use href="#i-check"/></svg></div><h3 style="font:700 24px var(--f-title)">Pesanan tersimpan</h3>
  <div style="font:700 20px var(--f-title);margin:12px 0 6px">{{ $baru->kode() }} · {{ $baru->nomor_meja ? 'Meja '.$baru->nomor_meja : ($baru->nama_pemesan ?: $baru->layananLabel()) }}</div>
  <div style="font-size:14px;opacity:.75">Total tagihan Rp {{ number_format($baru->total, 0, ',', '.') }}</div>
  @if ($baru->tiketDapur)
    <div class="note" style="margin:16px 28px 0;text-align:left"><svg class="ic"><use href="#i-receipt"/></svg><span>Tiket masuk antrean dapur pukul {{ $baru->tiketDapur->waktu_masuk->format('H:i') }} (urutan ke-{{ $urutanAntrean }} sesuai FIFO).</span></div>
  @endif
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:22px 28px 28px"><button class="btn o lg" data-close>Pesanan baru</button><a class="btn lg" href="{{ route('kasir.pembayaran', ['pesanan' => $baru->id]) }}">Ke Pembayaran</a></div></div></div>
@endif
@endpush

@push('scripts')
<script>
  (function () {
    var pajak = {{ (float) $pengaturan->pajak }}, layanan = {{ (float) $pengaturan->biaya_layanan }};
    var order = []; // [{id, n, p, q, c}]
    var awal = @json($oldItems);
    function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function draw() {
      var s = 0, h = '', inp = '';
      order.forEach(function (o, i) {
        s += o.p * o.q;
        h += '<div class="oi" data-i="' + i + '"><b style="font:600 13.5px var(--f-title)">' + esc(o.n) + '</b><b style="font:600 13.5px var(--f-title)">' + rp(o.p * o.q) + '</b>'
          + '<span style="font-size:12px;opacity:.65">' + rp(o.p) + ' / porsi</span><div class="qty"><button type="button" data-m="' + i + '" aria-label="Kurangi">−</button><b>' + o.q + '</b><button type="button" data-a="' + i + '" aria-label="Tambah">+</button></div>'
          + '<input class="note-in" data-c="' + i + '" maxlength="255" placeholder="Catatan untuk dapur (opsional)" value="' + esc(o.c || '') + '"></div>';
        inp += '<input type="hidden" name="items[' + i + '][id_menu]" value="' + o.id + '"><input type="hidden" name="items[' + i + '][jumlah]" value="' + o.q + '"><input type="hidden" name="items[' + i + '][catatan]" value="' + esc(o.c || '') + '">';
      });
      var t = Math.round(s * pajak / 100) + Math.round(s * layanan / 100);
      ol.innerHTML = h || '<div class="ord-empty">Klik menu di sebelah kiri untuk menambahkan.</div>';
      itemInputs.innerHTML = inp;
      sub.textContent = rp(s); tx.textContent = rp(t); tot.textContent = rp(s + t);
      btnSave.disabled = !order.length;
      var n = order.reduce(function (a, o) { return a + o.q; }, 0);
      jump.hidden = !n; jumpN.textContent = 'Pesanan · ' + n + ' item'; jumpT.textContent = rp(s + t);
    }
    function tambah(b) {
      if (b.classList.contains('out')) { toast('Menu tidak tersedia, pilih menu lain'); return; }
      var id = +b.dataset.id, o = order.find(function (x) { return x.id === id; });
      if (o) o.q = Math.min(99, o.q + 1); else order.push({ id: id, n: b.dataset.n, p: +b.dataset.p, q: 1, c: '' });
      draw();
    }
    mg.addEventListener('click', function (e) { var b = e.target.closest('.mi'); if (b) tambah(b); });
    ol.addEventListener('click', function (e) {
      var a = e.target.dataset.a, m = e.target.dataset.m;
      if (a !== undefined) order[a].q = Math.min(99, order[a].q + 1);
      if (m !== undefined) { order[m].q--; if (!order[m].q) order.splice(m, 1); }
      if (a !== undefined || m !== undefined) draw();
    });
    ol.addEventListener('input', function (e) {
      var c = e.target.dataset.c; if (c === undefined) return;
      order[c].c = e.target.value;
      var h = itemInputs.querySelector('[name="items[' + c + '][catatan]"]'); if (h) h.value = e.target.value;
    });

    /* filter kategori + cari */
    var kat = 'all';
    function saring() {
      var q = cari.value.toLowerCase(), n = 0;
      mg.querySelectorAll('.mi').forEach(function (b) {
        var ok = (kat === 'all' || b.dataset.k === kat) && b.dataset.n.toLowerCase().indexOf(q) >= 0;
        b.style.display = ok ? '' : 'none'; if (ok) n++;
      });
      mgEmpty.hidden = n > 0;
    }
    katChips.addEventListener('click', function (e) { var b = e.target.closest('button'); if (!b) return; kat = b.dataset.k; saring(); });
    cari.addEventListener('input', saring);

    /* layanan & meja */
    function setLayanan(s) {
      jenis.value = s;
      svc.querySelectorAll('button').forEach(function (x) { var on = x.dataset.s === s; x.style.background = on ? 'var(--cr)' : 'transparent'; x.style.color = on ? 'var(--m)' : 'var(--cr)'; });
      document.querySelectorAll('.svc-p').forEach(function (p) { p.classList.toggle('on', p.dataset.p === s); });
    }
    svc.addEventListener('click', function (e) { var b = e.target.closest('button'); if (b) setLayanan(b.dataset.s); });
    document.querySelector('.tables').addEventListener('click', function (e) {
      var b = e.target.closest('button'); if (!b) return;
      if (b.classList.contains('busy')) { toast('Meja ' + b.dataset.meja + ' sedang dipakai'); return; }
      document.querySelectorAll('.tables button').forEach(function (x) { x.classList.toggle('on', x === b); });
      meja.value = b.dataset.meja;
    });
    fOrder.addEventListener('submit', function (e) {
      if (jenis.value === 'dine_in' && !meja.value) { e.preventDefault(); toast('Pilih nomor meja dulu'); return; }
      btnSave.disabled = true;
    });

    setLayanan(jenis.value);
    // pulihkan isian setelah error validasi
    awal.forEach(function (it) {
      var b = mg.querySelector('.mi[data-id="' + it.id_menu + '"]');
      if (b) order.push({ id: +it.id_menu, n: b.dataset.n, p: +b.dataset.p, q: +it.jumlah || 1, c: it.catatan || '' });
    });
    draw();
  })();
</script>
@endpush
