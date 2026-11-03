<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Keranjang · SIM Resto</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Raleway:wght@600;700;800&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
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
</head>
<body>
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
  <symbol id="i-pin" viewBox="0 0 24 24"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></symbol>
  <symbol id="i-truck" viewBox="0 0 24 24"><path d="M2 6h11v10H2zM13 9h4l4 4v3h-8"/><circle cx="6" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></symbol>
  <symbol id="i-bag" viewBox="0 0 24 24"><path d="M5 8h14l-1 13H6z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></symbol>
  <symbol id="i-dine" viewBox="0 0 24 24"><path d="M6 3v7a2 2 0 0 0 4 0V3M8 12v9"/><path d="M17 3c-2.2 2-2.2 6.5 0 8.5V21"/></symbol>
  <symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></symbol>
  <symbol id="i-cart" viewBox="0 0 24 24"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h3l2.6 12.2a1 1 0 0 0 1 .8h9.6a1 1 0 0 0 1-.8L21 7H6"/></symbol>
  <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
  <symbol id="i-minus" viewBox="0 0 24 24"><path d="M5 12h14"/></symbol>
  <symbol id="i-trash" viewBox="0 0 24 24"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/></symbol>
  <symbol id="i-edit" viewBox="0 0 24 24"><path d="M4 20h4L19 9l-4-4L4 16z"/></symbol>
  <symbol id="i-arr" viewBox="0 0 24 24"><path d="M4 12h16M14 6l6 6-6 6"/></symbol>
  <symbol id="i-arrl" viewBox="0 0 24 24"><path d="M20 12H4M10 6l-6 6 6 6"/></symbol>
  <symbol id="i-check" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7"/></symbol>
  <symbol id="i-info" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5h.01"/></symbol>
  <symbol id="i-tag" viewBox="0 0 24 24"><path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="7.5" r="1.4"/></symbol>
  <symbol id="i-receipt" viewBox="0 0 24 24"><path d="M5 3h14v18l-2.5-1.5L14 21l-2-1.5L10 21l-2.5-1.5L5 21z"/><path d="M9 8h6M9 12h6"/></symbol>
  <symbol id="i-help" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 0 1 5 0c0 1.7-2.5 2-2.5 4M12 17h.01"/></symbol>
  <symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></symbol>
  <symbol id="i-phone" viewBox="0 0 24 24"><path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2z"/></symbol>
  <symbol id="i-card" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/></symbol>
  <symbol id="i-cash" viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.8"/></symbol>
  <symbol id="i-upload" viewBox="0 0 24 24"><path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v4h16v-4"/></symbol>
  <symbol id="i-bell" viewBox="0 0 24 24"><path d="M6 16v-5a6 6 0 0 1 12 0v5l2 2H4z"/><path d="M10 21h4"/></symbol>
  <symbol id="i-image" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M21 16l-5-5-9 9"/></symbol>
  <symbol id="i-logout" viewBox="0 0 24 24"><path d="M15 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-4"/><path d="M10 17l-5-5 5-5M5 12h11"/></symbol>
  <symbol id="i-shield" viewBox="0 0 24 24"><path d="M12 3l8 3v6c0 4.5-3.4 8.2-8 9-4.6-.8-8-4.5-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/></symbol>
</svg>
<div class="topbar"><div class="wrap">
  <span><svg class="ic"><use href="#i-clock"/></svg>Buka hari ini 10.00 – 22.00 · Delivery tersedia untuk Zona A, B, C</span>
  <nav><a href="{{ route('pelanggan.tracking') }}"><svg class="ic"><use href="#i-receipt"/></svg>Lacak Pesanan</a><a href="#"><svg class="ic"><use href="#i-help"/></svg>Bantuan</a></nav>
</div></div>
<header><div class="wrap">
  <a class="logo" href="{{ route('pelanggan.beranda') }}"><b>SIM RESTO</b><small>PIZZA &amp; PASTA HOUSE</small></a>
  <nav class="mainnav"><a href="{{ route('pelanggan.beranda') }}#promo">Promo</a><a href="{{ route('pelanggan.beranda') }}#menu">Pizza</a><a href="{{ route('pelanggan.beranda') }}#menu">Pasta</a><a href="{{ route('pelanggan.beranda') }}#menu">Burger</a><a href="{{ route('pelanggan.beranda') }}#menu">Camilan</a><a href="{{ route('pelanggan.beranda') }}#menu">Minuman</a><a href="{{ route('pelanggan.beranda') }}#menu">Dessert</a></nav>
  <div class="hd-right">
    <button class="icon-btn" aria-label="Cari menu"><svg class="ic"><use href="#i-search"/></svg></button>
    <a class="user" href="#"><span class="av">RA</span><span>Rina</span></a>
    <a class="btn cart" href="{{ route('pelanggan.keranjang') }}"><svg class="ic"><use href="#i-cart"/></svg><span class="txt" id="hdTotal">Rp 236.000</span><span class="count" id="hdCount">5</span></a>
  </div>
</div></header>
<main class="wrap">
  <div class="page-head">
    <div><div class="crumb"><a href="{{ route('pelanggan.beranda') }}">Beranda</a><span>/</span><span>Keranjang</span></div><h1>Keranjang Belanja</h1></div>
    <div class="steps"><div class="s now"><span class="n">1</span><span>Keranjang</span></div><div class="line"></div>
      <div class="s next"><span class="n">2</span><span>Checkout</span></div><div class="line"></div><div class="s next"><span class="n">3</span><span>Pembayaran</span></div></div>
  </div>

  <div class="layout">
    <div>
      <section class="card">
        <div class="card-head"><h2>Menu dipesan <span class="pill b" id="menuCount" style="margin-left:6px;vertical-align:3px">4 menu</span></h2><a class="btn o sm" href="#" id="clearAll"><svg class="ic"><use href="#i-trash"/></svg>Kosongkan</a></div>
        <div class="items" id="cartItems">
      <article class="it" data-id="pep" data-price="109000">
        <div class="ph"><img src="https://images.unsplash.com/photo-1534308983496-4fabb1a015ee?w=500&q=80" alt="Pizza Beef Pepperoni"></div>
        <div><div class="cat">Pizza · Reguler</div><h3>Pizza Beef Pepperoni</h3>
          <label class="notes"><svg class="ic" style="width:15px;height:15px"><use href="#i-edit"/></svg><input value="Potong 12" placeholder="Tambah catatan untuk dapur"></label>
          <div class="unit">Rp 109.000 / porsi</div></div>
        <div class="right"><b class="line-total">Rp 109.000</b>
          <div class="row"><div class="qty"><button class="minus" aria-label="Kurangi"><svg class="ic"><use href="#i-minus"/></svg></button><b class="q">1</b><button class="plus" aria-label="Tambah"><svg class="ic"><use href="#i-plus"/></svg></button></div>
          <button class="del" aria-label="Hapus"><svg class="ic"><use href="#i-trash"/></svg></button></div></div>
      </article>
      <article class="it" data-id="pen" data-price="59000">
        <div class="ph"><img src="https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?w=500&q=80" alt="Penne Bolognese"></div>
        <div><div class="cat">Pasta</div><h3>Penne Bolognese</h3>
          <label class="notes"><svg class="ic" style="width:15px;height:15px"><use href="#i-edit"/></svg><input value="" placeholder="Tambah catatan untuk dapur"></label>
          <div class="unit">Rp 59.000 / porsi</div></div>
        <div class="right"><b class="line-total">Rp 59.000</b>
          <div class="row"><div class="qty"><button class="minus" aria-label="Kurangi"><svg class="ic"><use href="#i-minus"/></svg></button><b class="q">1</b><button class="plus" aria-label="Tambah"><svg class="ic"><use href="#i-plus"/></svg></button></div>
          <button class="del" aria-label="Hapus"><svg class="ic"><use href="#i-trash"/></svg></button></div></div>
      </article>
      <article class="it" data-id="tea" data-price="18000">
        <div class="ph"><img src="https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=500&q=80" alt="Lemon Iced Tea"></div>
        <div><div class="cat">Minuman</div><h3>Lemon Iced Tea</h3>
          <label class="notes"><svg class="ic" style="width:15px;height:15px"><use href="#i-edit"/></svg><input value="Es sedikit" placeholder="Tambah catatan untuk dapur"></label>
          <div class="unit">Rp 18.000 / porsi</div></div>
        <div class="right"><b class="line-total">Rp 36.000</b>
          <div class="row"><div class="qty"><button class="minus" aria-label="Kurangi"><svg class="ic"><use href="#i-minus"/></svg></button><b class="q">2</b><button class="plus" aria-label="Tambah"><svg class="ic"><use href="#i-plus"/></svg></button></div>
          <button class="del" aria-label="Hapus"><svg class="ic"><use href="#i-trash"/></svg></button></div></div>
      </article>
      <article class="it" data-id="brw" data-price="32000">
        <div class="ph"><img src="https://images.unsplash.com/photo-1624353365286-3f8d62daad51?w=500&q=80" alt="Choco Brownie Ice Cream"></div>
        <div><div class="cat">Dessert</div><h3>Choco Brownie Ice Cream</h3>
          <label class="notes"><svg class="ic" style="width:15px;height:15px"><use href="#i-edit"/></svg><input value="" placeholder="Tambah catatan untuk dapur"></label>
          <div class="unit">Rp 32.000 / porsi</div></div>
        <div class="right"><b class="line-total">Rp 32.000</b>
          <div class="row"><div class="qty"><button class="minus" aria-label="Kurangi"><svg class="ic"><use href="#i-minus"/></svg></button><b class="q">1</b><button class="plus" aria-label="Tambah"><svg class="ic"><use href="#i-plus"/></svg></button></div>
          <button class="del" aria-label="Hapus"><svg class="ic"><use href="#i-trash"/></svg></button></div></div>
      </article>
        </div>
        <a class="add-more" href="{{ route('pelanggan.beranda') }}#menu"><svg class="ic"><use href="#i-plus"/></svg>Tambah menu lain</a>
      </section>

      <section class="reco">
        <div class="card-head" style="margin-bottom:14px"><div><div class="eyebrow">Biar makin lengkap</div><h2 style="font:600 20px var(--f-title)">Sering dipesan bersama</h2></div></div>
        <div class="reco-grid">
          <div class="rc"><img src="https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=300&q=75" alt="Kentang Goreng"><div><b>Kentang Goreng</b><span>Rp 28.000</span></div><button class="addb" aria-label="Tambah Kentang Goreng"><svg class="ic"><use href="#i-plus"/></svg></button></div>
          <div class="rc"><img src="https://images.unsplash.com/photo-1550304943-4f24f54ddde9?w=300&q=75" alt="Caesar Salad"><div><b>Caesar Salad</b><span>Rp 42.000</span></div><button class="addb" aria-label="Tambah Caesar Salad"><svg class="ic"><use href="#i-plus"/></svg></button></div>
          <div class="rc"><img src="https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=300&q=75" alt="Classic Beef Burger"><div><b>Classic Beef Burger</b><span>Rp 55.000</span></div><button class="addb" aria-label="Tambah Classic Beef Burger"><svg class="ic"><use href="#i-plus"/></svg></button></div>
        </div>
      </section>
    </div>

    <aside class="summary">
      <div class="sum-card">
        <div class="sum-body">
          <div class="eyebrow" style="margin-bottom:12px">Ringkasan sementara</div>
          <div class="sum-row"><span>Subtotal (<span id="sumQty">5</span> item)</span><b id="sumSub">Rp 236.000</b></div>
          <div class="sum-row"><span class="mut">Pajak 10% &amp; biaya layanan 5%</span><span class="mut">dihitung di checkout</span></div>
          <div class="sum-row"><span class="mut">Ongkos kirim</span><span class="mut">sesuai zona</span></div>
          <div class="field" style="margin:14px 0 10px"><label>Kode promo</label>
            <div class="promo-in"><div class="in"><svg class="ic"><use href="#i-tag"/></svg><input placeholder="Masukkan kode"></div><button class="btn b">Pakai</button></div></div>
        </div>
        <div class="sum-total">
          <div class="t"><span class="eyebrow" style="opacity:.8">Subtotal</span><b id="sumBig">Rp 236.000</b></div>
          <a class="btn cr blk" href="{{ route('pelanggan.checkout') }}">Lanjut ke Checkout <svg class="ic"><use href="#i-arr"/></svg></a>
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
<footer>
  <svg class="torn" viewBox="0 0 1000 24" preserveAspectRatio="none" aria-hidden="true"><rect width="1000" height="12" fill="#F8F5E6"/><rect y="12" width="1000" height="12" fill="#97173A"/>
    <path d="M0,7 L24,9 L49,5 L76,8 L101,6 L128,10 L155,6 L183,8 L209,5 L238,9 L265,6 L292,8 L318,5 L347,9 L373,7 L400,10 L427,6 L455,8 L482,5 L510,9 L537,6 L563,8 L591,5 L618,9 L644,7 L671,10 L697,6 L725,8 L752,5 L779,9 L806,6 L833,8 L859,5 L887,9 L914,7 L941,10 L967,6 L1000,8 L1000,18 L972,16 L944,20 L917,17 L889,19 L861,15 L834,18 L806,16 L779,20 L751,17 L723,19 L696,15 L668,18 L641,16 L613,20 L585,17 L558,19 L530,15 L503,18 L476,16 L448,20 L421,17 L393,19 L366,15 L338,18 L311,16 L283,20 L256,17 L228,19 L200,15 L173,18 L146,16 L118,20 L91,17 L63,19 L36,15 L0,18 Z" fill="#E7E0D6"/></svg>
  <div class="wrap copy"><b>SIM RESTO</b><span>© 2026 SIM Resto · Jl. Gatot Subroto No. 100, Medan · 0811 6000 1234</span></div>
</footer>
<script>
  const rp = n => 'Rp ' + n.toLocaleString('id-ID');
  function recalc() {
    let sub = 0, qty = 0, rows = document.querySelectorAll('.it');
    rows.forEach(r => { const q = +r.querySelector('.q').textContent, p = +r.dataset.price;
      sub += p * q; qty += q; r.querySelector('.line-total').textContent = rp(p * q); });
    sumSub.textContent = sumBig.textContent = hdTotal.textContent = rp(sub);
    sumQty.textContent = hdCount.textContent = qty; menuCount.textContent = rows.length + ' menu';
  }
  cartItems.addEventListener('click', e => {
    const r = e.target.closest('.it'); if (!r) return; const q = r.querySelector('.q');
    if (e.target.closest('.plus')) q.textContent = +q.textContent + 1;
    if (e.target.closest('.minus') && +q.textContent > 1) q.textContent = +q.textContent - 1;
    if (e.target.closest('.del')) r.remove();
    recalc();
  });
  clearAll.addEventListener('click', e => { e.preventDefault(); document.querySelectorAll('.it').forEach(r => r.remove()); recalc(); });
</script>
</body>
</html>
