@extends('layouts.panel')

@use('App\Models\Pesanan')
@use('App\Models\PengaturanSistem')

@section('title', 'Input Pesanan')
@section('error-sendiri', '1')
@section('crumb', 'Kasir / Input Pesanan')
@section('heading', $edit ? 'Ubah Pesanan '.$edit->kode() : 'Input Pesanan di Tempat')
@section('sub', 'Pesanan pembeli langsung (makan di sini, dibawa pulang, atau diantar), tiket otomatis masuk antrean dapur')

@php
  $layanan = old('jenis_layanan', $edit?->jenis_layanan ?? Pesanan::LAYANAN_DINE_IN);
  $mejaLama = old('nomor_meja', $edit?->nomor_meja);
  $kirim = $edit?->pengiriman;
  $bayarLama = old('bayar', $edit?->pembayaran?->status_bayar === \App\Models\Pembayaran::STATUS_MENUNGGU ? \App\Services\PesananService::BAYAR_KE_KURIR : \App\Services\PesananService::BAYAR_DI_KASIR);
  $oldItems = collect(old('items', $edit
      ? $edit->detail->map(fn ($d) => ['id_menu' => $d->id_menu, 'jumlah' => $d->jumlah, 'catatan' => $d->catatan])->all()
      : []))->values();
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
  .qty button { width: 30px; height: 100%; display: grid; place-items: center; }
  .qty input { width: 34px; text-align: center; font: 700 13px var(--f-title); border: 0; background: none; color: inherit; -moz-appearance: textfield; }
  .qty input::-webkit-outer-spin-button, .qty input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
  .oi .hapus { width: 30px; height: 30px; border-radius: 50%; display: grid; place-items: center; box-shadow: inset 0 0 0 1.3px var(--m15); }
  .oi .act { display: flex; gap: 6px; align-items: center; justify-content: flex-end; }
  .svc-p { display: none; } .svc-p.on { display: block; }
  .ord-empty { padding: 18px 4px; font-size: 13px; opacity: .6; text-align: center; }
  #fOrder.antar { position: static !important; }
  .bayar-opt { display: grid; gap: 8px; margin-top: 8px; }
  .bayar-opt button { display: flex; gap: 10px; align-items: flex-start; text-align: left; padding: 11px 14px; border-radius: 14px; box-shadow: inset 0 0 0 1.3px var(--m15); font: 500 12.5px/1.4 var(--f-body); }
  .bayar-opt button b { display: block; font: 600 13.5px var(--f-title); }
  .bayar-opt button .rad { width: 16px; height: 16px; flex: none; margin-top: 2px; border-radius: 50%; box-shadow: inset 0 0 0 1.5px var(--m); }
  .bayar-opt button.on { box-shadow: inset 0 0 0 2px var(--m); background: var(--pp); }
  .bayar-opt button.on .rad { box-shadow: inset 0 0 0 5px var(--m); }
  #svc button[disabled] { opacity: .5; cursor: not-allowed; }
  #svc button { white-space: nowrap; padding-left: 6px; padding-right: 6px; font-size: 12.5px; }
  @media (max-width: 1280px) { .tables { grid-template-columns: repeat(6, 1fr); } }
  .jump { display: none; }
  @media (max-width: 760px) {
    .qty { height: 40px; } .qty button { width: 40px; } .oi .hapus { width: 40px; height: 40px; }
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

    <form class="card" method="post" action="{{ $edit ? route('kasir.pesanan.update', $edit) : route('kasir.input.store') }}" id="fOrder" style="padding:0;overflow:hidden;position:sticky;top:16px">
      @csrf
      @if ($edit) @method('PUT') @endif
      <input type="hidden" name="jenis_layanan" id="jenis" value="{{ $layanan }}">
      <input type="hidden" name="nomor_meja" id="meja" value="{{ $mejaLama }}">
      <input type="hidden" name="bayar" id="bayar" value="{{ $bayarLama }}">
      <div id="itemInputs"></div>
      <div style="background:var(--m);color:var(--cr);padding:18px 20px"><div style="display:flex;justify-content:space-between;align-items:center"><h2 style="font:600 19px var(--f-title)">{{ $edit ? 'Ubah '.$edit->kode() : 'Pesanan Baru' }}</h2><span class="pill b">di tempat</span></div>
        <div class="seg" id="svc" style="margin-top:12px">
          <button type="button" data-s="{{ Pesanan::LAYANAN_DINE_IN }}" style="flex:1;box-shadow:inset 0 0 0 1.4px var(--cr)"><svg class="ic" style="width:15px;height:15px"><use href="#i-dine"/></svg> Dine In</button>
          <button type="button" data-s="{{ Pesanan::LAYANAN_TAKE_AWAY }}" style="flex:1;box-shadow:inset 0 0 0 1.4px var(--cr)"><svg class="ic" style="width:15px;height:15px"><use href="#i-bag"/></svg> Take Away</button>
          <button type="button" data-s="{{ Pesanan::LAYANAN_DELIVERY }}" style="flex:1;box-shadow:inset 0 0 0 1.4px var(--cr)" @disabled(! $adaZona) @unless ($adaZona) title="Zona pengiriman belum diatur admin" @endunless><svg class="ic" style="width:15px;height:15px"><use href="#i-truck"/></svg> Delivery</button></div></div>
      <div style="padding:16px 20px">
        <div class="svc-p" data-p="{{ Pesanan::LAYANAN_DINE_IN }}"><div class="field"><label>Nomor meja</label><div class="tables">
          @for ($i = 1; $i <= $jumlahMeja; $i++)
            @php $no = str_pad($i, 2, '0', STR_PAD_LEFT); @endphp
            <button type="button" data-meja="{{ $no }}" @class(['busy' => in_array($no, $mejaTerpakai, true), 'on' => $mejaLama === $no]) @if (in_array($no, $mejaTerpakai, true)) title="Meja sedang dipakai" @endif>{{ $no }}</button>
          @endfor
        </div></div></div>
        <div class="svc-p" data-p="{{ Pesanan::LAYANAN_TAKE_AWAY }}"><div class="field"><label for="nama">Nama pelanggan (opsional)</label><div class="in"><svg class="ic"><use href="#i-user"/></svg><input type="text" id="nama" name="nama_pemesan" value="{{ old('nama_pemesan', $edit?->nama_pemesan) }}" maxlength="100" placeholder="Untuk dipanggil saat pesanan siap"></div></div></div>
        <div class="svc-p" data-p="{{ Pesanan::LAYANAN_DELIVERY }}">
          @if ($errors->hasAny(['nama_penerima', 'no_hp_penerima', 'bayar']))<div class="peta-galat" role="alert" style="margin:0 0 12px">{{ $errors->first('nama_penerima') ?: ($errors->first('no_hp_penerima') ?: $errors->first('bayar')) }}</div>@endif
          <div class="field"><label for="namaPenerima">Nama penerima</label><div class="in"><svg class="ic"><use href="#i-user"/></svg><input type="text" id="namaPenerima" name="nama_penerima" value="{{ old('nama_penerima', $kirim?->nama_penerima ?? $edit?->nama_pemesan) }}" maxlength="100" placeholder="Orang yang menerima pesanan"></div></div>
          <div class="field" style="margin-top:12px"><label for="hpPenerima">Nomor HP penerima</label><div class="in"><svg class="ic"><use href="#i-phone"/></svg><input type="tel" id="hpPenerima" name="no_hp_penerima" value="{{ old('no_hp_penerima', $kirim?->no_hp_penerima) }}" maxlength="20" inputmode="tel" placeholder="Untuk dihubungi kurir, mis. 081234567890"></div></div>
          <div style="margin-top:14px">@include('partials.peta-antar', ['petaAlamat' => old('alamat', $kirim?->alamat), 'petaPatokan' => old('patokan', $kirim?->patokan), 'petaLat' => old('lat', $kirim?->lat), 'petaLng' => old('lng', $kirim?->lng)])</div>
          <div class="eyebrow" style="margin-top:16px">Pembayaran</div>
          <div class="bayar-opt" id="bayarOpt">
            <button type="button" data-b="{{ \App\Services\PesananService::BAYAR_DI_KASIR }}"><span class="rad"></span><span><b>Bayar sekarang di kasir</b>Tunai, QRIS, atau transfer. Kurir baru bisa mengambil pesanan setelah lunas.</span></button>
            <button type="button" data-b="{{ \App\Services\PesananService::BAYAR_KE_KURIR }}"><span class="rad"></span><span><b>Bayar tunai ke kurir (COD)</b>Penerima membayar saat pesanan tiba. Uangnya disetor kurir ke kasir.</span></button>
          </div>
        </div>
        <div class="eyebrow" style="margin-top:16px">Menu, jumlah &amp; catatan</div><div id="ol"></div>
        <div style="display:grid;gap:4px;margin-top:14px;font-size:13.5px"><div style="display:flex;justify-content:space-between"><span>Subtotal</span><span id="sub">Rp 0</span></div>
          <div style="display:flex;justify-content:space-between"><span>Pajak {{ PengaturanSistem::persen($pengaturan->pajak) }}% + layanan {{ PengaturanSistem::persen($pengaturan->biaya_layanan) }}%</span><span id="tx">Rp 0</span></div>
          <div style="display:none;justify-content:space-between" id="ongRow"><span>Ongkir <span id="ongZona">· tentukan titik</span></span><span id="ong">-</span></div></div>
        <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-top:10px;padding-top:10px;border-top:1.5px dashed var(--m60)"><span class="eyebrow">Total tagihan</span><b id="tot" style="font:700 26px var(--f-title)">Rp 0</b></div>
        <button class="btn lg" type="submit" id="btnSave" style="width:100%;margin-top:14px" disabled><svg class="ic"><use href="#i-receipt"/></svg>{{ $edit ? 'Simpan Perubahan' : 'Simpan Pesanan' }}</button>
        @if ($edit)<a class="btn o lg" href="{{ route('kasir.dashboard') }}" style="width:100%;margin-top:8px">Batal ubah</a>@endif
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
  @php $baruCod = $baru->isDelivery() && $baru->pembayaran !== null; @endphp
  @if ($baru->isDelivery())
    <div class="note" style="margin:16px 28px 0;text-align:left"><svg class="ic"><use href="#i-truck"/></svg><span>{{ $baruCod ? 'Diantar ke '.$baru->namaPemesan().'. Penerima membayar tunai ke kurir saat pesanan tiba.' : 'Diantar ke '.$baru->namaPemesan().'. Lunasi sekarang di Pembayaran supaya kurir bisa mengambil pesanan.' }}</span></div>
  @endif
  @if ($baru->tiketDapur)
    <div class="note" style="margin:16px 28px 0;text-align:left"><svg class="ic"><use href="#i-receipt"/></svg><span>Tiket masuk antrean dapur pukul {{ $baru->tiketDapur->waktu_masuk->format('H:i') }} (urutan ke-{{ $urutanAntrean }} di antrean dapur).</span></div>
  @endif
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:22px 28px 28px"><button class="btn o lg" data-close>Pesanan baru</button>@if ($baruCod)<a class="btn lg" href="{{ route('kasir.dashboard') }}#berjalan">Ke Dashboard</a>@else<a class="btn lg" href="{{ route('kasir.pembayaran', ['pesanan' => $baru]) }}">Ke Pembayaran</a>@endif</div></div></div>
@endif
@endpush

@push('scripts')
<script>
  (function () {
    var pajak = {{ (float) $pengaturan->pajak }}, layanan = {{ (float) $pengaturan->biaya_layanan }};
    var order = []; // [{id, n, p, q, c}]
    var siap = false; // true setelah isian awal / draf dipulihkan
    var awal = @json($oldItems);
    function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function draw() {
      var s = 0, h = '', inp = '';
      order.forEach(function (o, i) {
        s += o.p * o.q;
        h += '<div class="oi" data-i="' + i + '"><b style="font:600 13.5px var(--f-title)">' + esc(o.n) + '</b><b style="font:600 13.5px var(--f-title)">' + rp(o.p * o.q) + '</b>'
          + '<span style="font-size:12px;opacity:.65">' + rp(o.p) + ' / porsi</span><div class="act"><div class="qty"><button type="button" data-m="' + i + '" aria-label="Kurangi">−</button>'
          + '<input type="number" inputmode="numeric" min="1" max="99" data-q="' + i + '" value="' + o.q + '" aria-label="Jumlah porsi"><button type="button" data-a="' + i + '" aria-label="Tambah">+</button></div>'
          + '<button type="button" class="hapus" data-x="' + i + '" aria-label="Hapus item" title="Hapus item"><svg class="ic" style="width:15px;height:15px"><use href="#i-trash"/></svg></button></div>'
          + '<input class="note-in" data-c="' + i + '" maxlength="255" placeholder="Catatan untuk dapur (opsional)" value="' + esc(o.c || '') + '"></div>';
        inp += '<input type="hidden" name="items[' + i + '][id_menu]" value="' + o.id + '"><input type="hidden" name="items[' + i + '][jumlah]" value="' + o.q + '"><input type="hidden" name="items[' + i + '][catatan]" value="' + esc(o.c || '') + '">';
      });
      var t = Math.round(s * pajak / 100) + Math.round(s * layanan / 100);
      var antar = jenis.value === 'delivery', z = antar && window.PetaAntar ? PetaAntar.zona() : null, ongkir = z ? z.ongkir : 0;
      ongRow.style.display = antar ? 'flex' : 'none'; ongZona.textContent = z ? '· ' + z.nama : '· tentukan titik'; ong.textContent = z ? rp(ongkir) : '-';
      ol.innerHTML = h || '<div class="ord-empty">Pilih menu untuk menambahkannya ke pesanan.</div>';
      simpanDraf();
      itemInputs.innerHTML = inp;
      sub.textContent = rp(s); tx.textContent = rp(t); tot.textContent = rp(s + t + ongkir);
      btnSave.disabled = !order.length;
      var n = order.reduce(function (a, o) { return a + o.q; }, 0);
      jump.hidden = !n; jumpN.textContent = 'Pesanan · ' + n + ' item'; jumpT.textContent = rp(s + t + ongkir);
    }
    function tambah(b) {
      if (b.classList.contains('out')) { toast('Menu tidak tersedia, pilih menu lain'); return; }
      var id = +b.dataset.id, o = order.find(function (x) { return x.id === id; });
      if (o && o.q >= 99) { toast('Maksimal 99 porsi per menu'); return; }
      if (o) o.q = o.q + 1; else order.push({ id: id, n: b.dataset.n, p: +b.dataset.p, q: 1, c: '' });
      draw();
    }
    mg.addEventListener('click', function (e) { var b = e.target.closest('.mi'); if (b) tambah(b); });
    ol.addEventListener('click', function (e) {
      var t = e.target.closest('button'); if (!t) return;
      var a = t.dataset.a, m = t.dataset.m, x = t.dataset.x;
      if (a !== undefined && order[a].q >= 99) { toast('Maksimal 99 porsi per menu'); return; }
      if (a !== undefined) order[a].q++;
      if (m !== undefined) { order[m].q--; if (!order[m].q) order.splice(m, 1); }
      if (x !== undefined) order.splice(x, 1);
      if (a !== undefined || m !== undefined || x !== undefined) draw();
    });
    /* jumlah porsi bisa diketik langsung (1 - 99) */
    ol.addEventListener('change', function (e) {
      var i = e.target.dataset.q; if (i === undefined) return;
      var v = Math.round(+e.target.value) || 1;
      if (v > 99) toast('Maksimal 99 porsi per menu');
      order[i].q = Math.max(1, Math.min(99, v)); draw();
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
      // form Delivery lebih panjang dari layar: tidak menempel (sticky) supaya tombol Simpan terjangkau
      fOrder.classList.toggle('antar', s === 'delivery');
      if (s === 'delivery' && window.PetaAntar) PetaAntar.siapkan();
      if (siap) draw();
    }
    document.addEventListener('peta:zona', function () { if (siap) draw(); });
    function setBayar(v) { bayar.value = v; bayarOpt.querySelectorAll('button').forEach(function (b) { b.classList.toggle('on', b.dataset.b === v); }); }
    bayarOpt.addEventListener('click', function (e) { var b = e.target.closest('button'); if (b) { setBayar(b.dataset.b); simpanDraf(); } });
    setBayar(bayar.value || 'kasir');
    svc.addEventListener('click', function (e) { var b = e.target.closest('button'); if (b) setLayanan(b.dataset.s); });
    document.querySelector('.tables').addEventListener('click', function (e) {
      var b = e.target.closest('button'); if (!b) return;
      if (b.classList.contains('busy')) { toast('Meja ' + b.dataset.meja + ' sedang dipakai'); return; }
      document.querySelectorAll('.tables button').forEach(function (x) { x.classList.toggle('on', x === b); });
      meja.value = b.dataset.meja;
    });
    fOrder.addEventListener('submit', function (e) {
      if (jenis.value === 'dine_in' && !meja.value) { e.preventDefault(); toast('Pilih nomor meja dulu'); return; }
      if (jenis.value === 'delivery') {
        var kurang = !namaPenerima.value.trim() ? 'Isi nama penerima' : !hpPenerima.value.trim() ? 'Isi nomor HP penerima'
          : !lat.value ? 'Tentukan titik pengantaran di peta' : !PetaAntar.zona() ? 'Titik pengantaran di luar jangkauan Delivery' : !alamat.value.trim() ? 'Isi alamat pengantaran' : '';
        if (kurang) { e.preventDefault(); toast(kurang); return; }
      }
      btnSave.disabled = true;
    });

    /* draf pesanan disimpan di tab ini, supaya tidak hilang bila koneksi putus saat Simpan (QA A3-#20) */
    var KUNCI = 'draf-input-kasir', boleh = @json(! $edit && ! $baru);
    function simpanDraf() {
      if (!boleh) return;
      try { sessionStorage.setItem(KUNCI, JSON.stringify({ order: order, jenis: jenis.value, meja: meja.value, nama: nama.value,
        antar: { penerima: namaPenerima.value, hp: hpPenerima.value, alamat: alamat.value, patokan: patokan.value, lat: lat.value, lng: lng.value, bayar: bayar.value } })); } catch (_) {}
    }
    @if ($baru) try { sessionStorage.removeItem(KUNCI); } catch (_) {} @endif

    setLayanan(jenis.value);
    // pulihkan isian setelah error validasi, atau draf yang belum tersimpan
    var draf = null;
    if (!awal.length && boleh) { try { draf = JSON.parse(sessionStorage.getItem(KUNCI) || 'null'); } catch (_) {} }
    if (draf && draf.order && draf.order.length) {
      draf.order.forEach(function (o) { if (mg.querySelector('.mi[data-id="' + o.id + '"]')) order.push(o); });
      if (draf.antar) {
        namaPenerima.value = draf.antar.penerima || ''; hpPenerima.value = draf.antar.hp || ''; alamat.value = draf.antar.alamat || '';
        patokan.value = draf.antar.patokan || ''; lat.value = draf.antar.lat || ''; lng.value = draf.antar.lng || ''; setBayar(draf.antar.bayar || 'kasir');
      }
      setLayanan(draf.jenis || jenis.value); nama.value = draf.nama || '';
      if (draf.meja) { var bm = document.querySelector('.tables button[data-meja="' + draf.meja + '"]'); if (bm && !bm.classList.contains('busy')) bm.click(); }
      if (order.length) toast('Draf pesanan sebelumnya dipulihkan');
    }
    awal.forEach(function (it) {
      var b = mg.querySelector('.mi[data-id="' + it.id_menu + '"]');
      if (b) order.push({ id: +it.id_menu, n: b.dataset.n, p: +b.dataset.p, q: +it.jumlah || 1, c: it.catatan || '' });
    });
    [jenis, nama, namaPenerima, hpPenerima, alamat, patokan].forEach(function (el) { el.addEventListener('change', simpanDraf); });
    svc.addEventListener('click', function () { setTimeout(simpanDraf, 0); });
    document.querySelector('.tables').addEventListener('click', function () { setTimeout(simpanDraf, 0); });
    siap = true;
    draw();
  })();
</script>
@endpush
