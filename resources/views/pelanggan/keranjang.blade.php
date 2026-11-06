@extends('layouts.pelanggan')

@section('title', 'Keranjang')

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

  .items { display: flex; flex-direction: column; }
  .it { display: grid; grid-template-columns: clamp(96px, 9vw, 128px) minmax(0, 1fr) auto; gap: clamp(14px, 1.6vw, 24px); align-items: center; padding: 18px 0; border-bottom: 1.5px solid var(--m15); }
  .it:first-child { padding-top: 4px; }
  .it .ph { aspect-ratio: 1; border-radius: 20px; overflow: hidden; }
  .it .ph img { width: 100%; height: 100%; object-fit: cover; }
  .it .cat { font: 800 10.5px var(--f-accent); letter-spacing: .14em; text-transform: uppercase; opacity: .6; }
  .it h3 { font: 600 18px / 1.3 var(--f-title); margin: 2px 0 8px; }
  .it .notes { display: inline-flex; align-items: center; gap: 8px; font: 500 13.5px var(--f-body); padding: 7px 12px; border-radius: 12px; background: var(--pp); max-width: 100%; }
  .it .notes input { all: unset; min-width: 0; width: clamp(140px, 18vw, 260px); color: var(--m); }
  .it .notes input::placeholder { color: rgba(151, 23, 58, .5); }
  .it .unit { font: 500 13px var(--f-body); opacity: .65; margin-top: 8px; }
  .it .right { display: flex; flex-direction: column; align-items: flex-end; gap: 14px; }
  .it .line-total { font: 700 18px var(--f-title); }
  .it .row { display: flex; align-items: center; gap: 10px; }
  .del { width: 40px; height: 40px; border-radius: 50%; display: grid; place-items: center; box-shadow: inset 0 0 0 1.5px var(--m15); }
  .del:hover { background: var(--m); color: var(--cr); box-shadow: none; }
  .add-more { display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 18px; height: 54px; border-radius: 18px; border: 1.8px dashed var(--m60); font: 700 14.5px var(--f-accent); }
  .add-more:hover { background: var(--pp); }
  .reco { margin-top: clamp(24px, 2.4vw, 36px); }
  .reco-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; }
  .rc { display: flex; align-items: center; gap: 12px; padding: 10px; border-radius: 20px; box-shadow: inset 0 0 0 1.5px var(--m15); }
  .rc img { width: 64px; height: 64px; border-radius: 14px; object-fit: cover; flex: none; }
  .rc b { display: block; font: 600 14px / 1.3 var(--f-title); }
  .rc span { font: 600 13px var(--f-body); opacity: .75; }
  .rc .addb { margin-left: auto; width: 36px; height: 36px; border-radius: 50%; background: var(--m); color: var(--cr); display: grid; place-items: center; flex: none; }
  .promo-in { display: flex; gap: 8px; }
  .promo-in .in { flex: 1; min-height: 48px; border-radius: 999px; }
  .promo-in .btn { height: 48px; }
  .svc { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 4px; }
  .svc span { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 12px 6px; border-radius: 16px; background: var(--pp); font: 700 12.5px var(--f-accent); text-align: center; }
  @media (max-width: 640px) {
    .it { grid-template-columns: 84px minmax(0, 1fr); }
    .it .right { grid-column: 1 / -1; flex-direction: row; justify-content: space-between; align-items: center; }
    .it .notes { display: flex; } .it .notes input { width: 100%; }
  }
</style>
@endpush

@php
  $set = \App\Models\PengaturanSistem::aktif();
  $persen = fn ($v) => \App\Models\PengaturanSistem::persen($v);
@endphp

@push('styles')
<style>
  .cart-empty { padding: 40px 20px; text-align: center; }
  .cart-empty b { display: block; font: 600 20px var(--f-title); margin-bottom: 6px; }
  .cart-empty p { opacity: .7; margin-bottom: 18px; }
  .it .ph .ph-none, .rc .ph-none { width: 100%; height: 100%; display: grid; place-items: center; background: var(--bl); }
  .rc .ph-none { width: 56px; height: 56px; border-radius: 14px; flex: none; }
  .btn[aria-disabled="true"] { opacity: .5; pointer-events: none; }
  .it.busy { opacity: .6; pointer-events: none; }
</style>
@endpush

@section('content')
<main class="wrap">
  <div class="page-head">
    <div><div class="crumb"><a href="{{ route('pelanggan.beranda') }}">Beranda</a><span>/</span><span>Keranjang</span></div><h1>Keranjang Belanja</h1></div>
    <div class="steps"><div class="s now"><span class="n">1</span><span>Keranjang</span></div><div class="line"></div>
      <div class="s next"><span class="n">2</span><span>Checkout</span></div><div class="line"></div><div class="s next"><span class="n">3</span><span>Pembayaran</span></div></div>
  </div>

  @if (session('error'))<div class="flash" role="alert">{{ session('error') }}</div>
  @elseif ($dibuang)<div class="flash" role="alert">{{ collect($dibuang)->join(', ', ' dan ') }} sedang habis dan sudah dihapus dari keranjang.</div>@endif
  @unless ($set->sedangBuka())<div class="flash" role="status">Restoran sedang tutup. Menu tetap bisa disimpan di keranjang; pesanan bisa dibuat pukul {{ $set->jamLabel() }}.</div>@endunless
  @if ($pesananTerakhir)<div class="flash ok" role="status">Pesanan {{ $pesananTerakhir->kode() }} sudah dibuat. <a href="{{ route('pelanggan.tracking', ['pesanan' => $pesananTerakhir->id]) }}" style="text-decoration:underline">Lihat statusnya</a>.</div>@endif

  <div class="layout">
    <div>
      <section class="card">
        <div class="card-head"><h2>Menu dipesan <span class="pill b" id="menuCount" style="margin-left:6px;vertical-align:3px">{{ $ringkasan['menu'] }} menu</span></h2>
          <button class="btn o sm" type="button" id="clearAll" @if ($items->isEmpty()) hidden @endif><svg class="ic"><use href="#i-trash"/></svg>Kosongkan</button></div>
        <div class="items" id="cartItems">
          @foreach ($items as $it)
            @php $m = $it['menu']; @endphp
            <article class="it" data-id="{{ $m->id }}" data-price="{{ (int) $m->harga }}" data-url="{{ route('pelanggan.keranjang.update', $m->id) }}" data-del="{{ route('pelanggan.keranjang.destroy', $m->id) }}">
              <div class="ph">@if ($m->fotoUrl())<img src="{{ $m->fotoUrl() }}" alt="{{ $m->nama_menu }}">@else<span class="ph-none"><svg class="ic"><use href="#i-dine"/></svg></span>@endif</div>
              <div><div class="cat">{{ $m->kategori->nama_kategori }}</div><h3>{{ $m->nama_menu }}</h3>
                <label class="notes"><svg class="ic" style="width:15px;height:15px"><use href="#i-edit"/></svg><input class="js-note" value="{{ $it['catatan'] }}" maxlength="255" placeholder="Tambah catatan untuk dapur"></label>
                <div class="unit">Rp {{ number_format($m->harga, 0, ',', '.') }} / porsi</div></div>
              <div class="right"><b class="line-total">Rp {{ number_format($it['subtotal'], 0, ',', '.') }}</b>
                <div class="row"><div class="qty"><button class="minus" type="button" aria-label="{{ $it['jumlah'] === 1 ? 'Hapus' : 'Kurangi' }}"><svg class="ic"><use href="#{{ $it['jumlah'] === 1 ? 'i-trash' : 'i-minus' }}"/></svg></button><b class="q">{{ $it['jumlah'] }}</b><button class="plus" type="button" aria-label="Tambah" @if ($it['jumlah'] >= \App\Support\Keranjang::MAKS_JUMLAH) style="opacity:.4" @endif><svg class="ic"><use href="#i-plus"/></svg></button></div>
                <button class="del" type="button" aria-label="Hapus"><svg class="ic"><use href="#i-trash"/></svg></button></div></div>
            </article>
          @endforeach
        </div>
        <div class="cart-empty" id="cartEmpty" @if ($items->isNotEmpty()) hidden @endif>
          <b>Keranjang masih kosong</b><p>Pilih menu favoritmu di beranda, lalu kembali ke sini.</p>
          <a class="btn" href="{{ route('pelanggan.beranda') }}#menu">Lihat Menu</a>
        </div>
        <a class="add-more" href="{{ route('pelanggan.beranda') }}#menu"><svg class="ic"><use href="#i-plus"/></svg>Tambah menu lain</a>
      </section>

      @if ($rekomendasi->isNotEmpty())
        <section class="reco">
          <div class="card-head" style="margin-bottom:14px"><div><div class="eyebrow">Biar makin lengkap</div><h2 style="font:600 20px var(--f-title)">Coba juga</h2></div></div>
          <div class="reco-grid">
            @foreach ($rekomendasi as $r)
              <div class="rc">@if ($r->fotoUrl())<img src="{{ $r->fotoUrl() }}" alt="{{ $r->nama_menu }}">@else<span class="ph-none"><svg class="ic"><use href="#i-dine"/></svg></span>@endif
                <div><b>{{ $r->nama_menu }}</b><span>Rp {{ number_format($r->harga, 0, ',', '.') }}</span></div>
                <button class="addb" type="button" data-add="{{ $r->id }}" aria-label="Tambah {{ $r->nama_menu }}"><svg class="ic"><use href="#i-plus"/></svg></button></div>
            @endforeach
          </div>
        </section>
      @endif
    </div>

    <aside class="summary">
      <div class="sum-card">
        <div class="sum-body">
          <div class="eyebrow" style="margin-bottom:12px">Ringkasan sementara</div>
          <div class="sum-row"><span>Subtotal (<span id="sumQty">{{ $ringkasan['jumlah'] }}</span> item)</span><b id="sumSub">Rp {{ number_format($ringkasan['subtotal'], 0, ',', '.') }}</b></div>
          <div class="sum-row"><span class="mut">Pajak {{ $persen($set->pajak) }}% &amp; biaya layanan {{ $persen($set->biaya_layanan) }}%</span><span class="mut">dihitung di checkout</span></div>
          <div class="sum-row"><span class="mut">Ongkos kirim</span><span class="mut">sesuai zona</span></div>
        </div>
        <div class="sum-total">
          <div class="t"><span class="eyebrow" style="opacity:.8">Subtotal</span><b id="sumBig">Rp {{ number_format($ringkasan['subtotal'], 0, ',', '.') }}</b></div>
          <a class="btn cr blk" id="toCheckout" href="{{ route('pelanggan.checkout') }}" @if ($items->isEmpty()) aria-disabled="true" @endif>Lanjut ke Checkout <svg class="ic"><use href="#i-arr"/></svg></a>
          <small>Pilih Dine In, Take Away, atau Delivery di langkah berikutnya</small>
        </div>
      </div>
      <div class="card soft" style="padding:16px 18px">
        <div class="eyebrow" style="font-size:10.5px;margin-bottom:10px">Layanan tersedia</div>
        <div class="svc"><span><svg class="ic"><use href="#i-dine"/></svg>Dine In</span><span><svg class="ic"><use href="#i-bag"/></svg>Take Away</span><span><svg class="ic"><use href="#i-truck"/></svg>Delivery</span></div>
      </div>
    </aside>
  </div>
</main>
@endsection

@push('scripts')
<script>
  (function () {
    var list = document.getElementById('cartItems');
    function ringkas(r) {
      setCart(r);
      sumQty.textContent = r.jumlah; sumSub.textContent = sumBig.textContent = rp(r.subtotal);
      menuCount.textContent = r.menu + ' menu';
      var kosong = r.menu === 0;
      cartEmpty.hidden = !kosong; clearAll.hidden = kosong;
      if (kosong) toCheckout.setAttribute('aria-disabled', 'true'); else toCheckout.removeAttribute('aria-disabled');
    }
    var MAKS = {{ \App\Support\Keranjang::MAKS_JUMLAH }};
    function baris(row) {
      var q = +row.querySelector('.q').textContent;
      row.querySelector('.line-total').textContent = rp(q * +row.dataset.price);
      // di jumlah 1, tombol − berubah menjadi tombol hapus; di batas maksimal tombol + diredupkan
      var minus = row.querySelector('.minus');
      minus.querySelector('use').setAttribute('href', q === 1 ? '#i-trash' : '#i-minus');
      minus.setAttribute('aria-label', q === 1 ? 'Hapus' : 'Kurangi');
      row.querySelector('.plus').style.opacity = q >= MAKS ? '.4' : '';
    }
    /* hapus item, bisa dibatalkan beberapa detik lewat toast */
    function hapus(row) {
      var data = { id: +row.dataset.id, jumlah: +row.querySelector('.q').textContent, catatan: row.querySelector('.js-note').value },
          sebelum = row.nextElementSibling, nama = row.querySelector('h3').textContent;
      row.classList.add('busy');
      sendJson(row.dataset.del, 'DELETE').then(function (r) {
        row.remove(); row.classList.remove('busy'); ringkas(r);
        pToastAksi(nama + ' dihapus.', 'Batalkan', function () {
          sendJson(@json(route('pelanggan.keranjang.store')), 'POST', { id_menu: data.id, jumlah: data.jumlah, catatan: data.catatan }).then(function (r2) {
            list.insertBefore(row, sebelum && sebelum.parentNode === list ? sebelum : null); ringkas(r2); pToast(nama + ' dikembalikan ke keranjang');
          }).catch(function (err) { pToast(err.message); });
        });
      }).catch(function (err) { row.classList.remove('busy'); pToast(err.message); });
    }
    list.addEventListener('click', function (e) {
      var row = e.target.closest('.it'); if (!row) return;
      var q = row.querySelector('.q'), n = +q.textContent;
      if (e.target.closest('.minus') && n === 1) { hapus(row); return; }
      if (e.target.closest('.plus') && n >= MAKS) { pToast('Maksimal ' + MAKS + ' porsi per menu.'); return; }
      if (e.target.closest('.plus') || e.target.closest('.minus')) {
        var baru = e.target.closest('.plus') ? n + 1 : n - 1;
        q.textContent = baru; baris(row);
        sendJson(row.dataset.url, 'PATCH', { jumlah: baru }).then(ringkas).catch(function (err) { q.textContent = n; baris(row); pToast(err.message); });
      }
      if (e.target.closest('.del')) hapus(row);
    });
    /* catatan disimpan otomatis setelah berhenti mengetik */
    list.addEventListener('input', function (e) {
      if (!e.target.classList.contains('js-note')) return;
      var row = e.target.closest('.it'), inp = e.target;
      clearTimeout(inp._t);
      inp._t = setTimeout(function () { sendJson(row.dataset.url, 'PATCH', { catatan: inp.value }).catch(function (err) { pToast(err.message); }); }, 600);
    });
    clearAll.addEventListener('click', function () {
      if (!confirm('Kosongkan semua menu di keranjang?')) return;
      sendJson(@json(route('pelanggan.keranjang.clear')), 'DELETE').then(function (r) {
        list.querySelectorAll('.it').forEach(function (x) { x.remove(); }); ringkas(r); pToast(r.message);
      }).catch(function (err) { pToast(err.message); });
    });
    document.querySelectorAll('[data-add]').forEach(function (b) {
      b.addEventListener('click', function () {
        b.disabled = true;
        sendJson(@json(route('pelanggan.keranjang.store')), 'POST', { id_menu: +b.dataset.add }).then(function () { location.reload(); })
          .catch(function (err) { b.disabled = false; pToast(err.message); });
      });
    });
  })();
</script>
@endpush
