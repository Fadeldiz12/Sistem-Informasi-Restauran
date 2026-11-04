<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Beranda · Resto Flow</title>
@include('partials.favicon')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Raleway:wght@600;700;800&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  /* =========================================================
     PALET & FONT
     Poppins  : judul, harga, tombol utama
     Raleway  : logo, navigasi, label, link
     Open Sans: teks isi
     ========================================================= */
  :root {
    --m:  #97173A; --cr: #F8F5E6; --bl: #B2C2D2; --pp: #E7E0D6; --dk: #282828;
    --m15: rgba(151, 23, 58, .15); --m60: rgba(151, 23, 58, .65);
    --f-title: 'Poppins', sans-serif; --f-accent: 'Raleway', sans-serif; --f-body: 'Open Sans', sans-serif;
    /* lebar konten mengikuti layar laptop masing-masing */
    --gutter: clamp(20px, 4vw, 72px);
    --wrap: min(1360px, 100% - var(--gutter) * 2);
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  html { scroll-behavior: smooth; }
  body { background: var(--cr); color: var(--m); font: 400 15px/1.55 var(--f-body); -webkit-font-smoothing: antialiased; }
  a { color: inherit; text-decoration: none; }
  img { display: block; max-width: 100%; }
  button { font: inherit; color: inherit; background: none; border: 0; cursor: pointer; }
  .wrap { width: var(--wrap); margin-inline: auto; }
  .ic { width: 18px; height: 18px; flex: none; fill: none; stroke: currentColor; stroke-width: 1.9; stroke-linecap: round; stroke-linejoin: round; }

  /* tombol & label */
  .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 48px; padding: 0 26px;
         border-radius: 999px; background: var(--m); color: var(--cr); font: 600 15px var(--f-title); white-space: nowrap; transition: .2s; }
  .btn:hover { background: #7f1331; }
  .btn.cr { background: var(--cr); color: var(--m); }
  .btn.cr:hover { background: var(--pp); }
  .btn.ghost { background: transparent; color: var(--cr); box-shadow: inset 0 0 0 1.5px var(--cr); }
  .btn.ghost:hover { background: rgba(248, 245, 230, .12); }
  .btn.sm { height: 40px; padding: 0 18px; font-size: 14px; }
  .pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 999px;
          font: 700 12px var(--f-accent); letter-spacing: .04em; white-space: nowrap; }
  .pill.b { background: var(--bl); color: var(--m); }
  .pill.m { background: var(--m); color: var(--cr); }
  .pill.o { box-shadow: inset 0 0 0 1.3px var(--m); }
  .eyebrow { font: 800 12px var(--f-accent); letter-spacing: .18em; text-transform: uppercase; }
  .h2 { font: 700 clamp(26px, 2.4vw, 36px) / 1.15 var(--f-title); }
  .sec-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 26px; flex-wrap: wrap; }
  .sec-head p { font-size: 15px; opacity: .72; margin-top: 6px; }
  .link { font: 700 14px var(--f-accent); display: inline-flex; align-items: center; gap: 6px; border-bottom: 1.5px solid currentColor; padding-bottom: 2px; }
  .stripes { background: repeating-linear-gradient(90deg, var(--bl) 0 17px, var(--cr) 17px 34px); }
  /* label gantungan kunci */
  .kt { position: relative; display: inline-flex; align-items: center; padding: 8px 16px 8px 30px; border-radius: 12px;
        background: var(--bl); color: var(--m); font: 700 18px var(--f-title); white-space: nowrap; }
  .kt::before { content: ''; position: absolute; left: 11px; top: 50%; width: 10px; height: 10px; margin-top: -5px;
                border-radius: 50%; background: var(--cr); box-shadow: inset 0 0 0 1.5px var(--m); }
  .kt.m { background: var(--m); color: var(--cr); }

  /* ---------- TOP BAR & HEADER ---------- */
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
  .mainnav a { position: relative; padding: 6px 0; }
  .mainnav a.on::after, .mainnav a:hover::after { content: ''; position: absolute; left: 0; right: 0; bottom: -2px; height: 2.5px; border-radius: 2px; background: var(--m); }
  .hd-right { display: flex; align-items: center; gap: 12px; }
  .icon-btn { width: 44px; height: 44px; border-radius: 50%; display: grid; place-items: center; box-shadow: inset 0 0 0 1.5px var(--m); }
  .user { display: flex; align-items: center; gap: 10px; font: 700 14px var(--f-accent); padding: 4px 14px 4px 4px; border-radius: 999px; box-shadow: inset 0 0 0 1.5px var(--m); }
  .av { width: 36px; height: 36px; border-radius: 50%; background: var(--bl); display: grid; place-items: center; font: 700 13px var(--f-title); }
  .cart { height: 44px; padding: 0 18px 0 14px; gap: 10px; }
  .cart .count { background: var(--cr); color: var(--m); border-radius: 999px; font: 700 12px var(--f-title); padding: 1px 8px; }

  /* ---------- HERO ---------- */
  .hero { padding-top: 26px; }
  .hero-card { position: relative; border-radius: 32px; overflow: hidden; background: var(--m); color: var(--cr);
               min-height: clamp(440px, 36vw, 560px); display: grid; grid-template-columns: 1.05fr 1fr; }
  .hero-curve { position: absolute; inset: 0; width: 100%; height: 100%; }
  .hero-text { position: relative; z-index: 2; padding: clamp(36px, 4vw, 64px) 0 clamp(80px, 7vw, 110px) clamp(28px, 4vw, 64px); display: flex; flex-direction: column; justify-content: center; }
  .hero-text h1 { font: 700 clamp(36px, 4vw, 62px) / 1.05 var(--f-title); text-transform: uppercase; margin: 18px 0 16px; letter-spacing: -.005em; }
  .hero-text h1 em { font-style: normal; text-transform: none; display: inline-block; background: var(--cr); color: var(--m); padding: 0 14px; border-radius: 14px; transform: rotate(-2deg); }
  .hero-text p { font-size: clamp(15px, 1.15vw, 17px); opacity: .88; max-width: 470px; }
  .hero-cta { display: flex; gap: 12px; margin-top: 28px; flex-wrap: wrap; }
  .hero-dots { display: flex; align-items: center; gap: 8px; margin-top: 34px; }
  .hero-dots i { width: 9px; height: 9px; border-radius: 9px; background: var(--cr); opacity: .4; }
  .hero-dots i.on { width: 30px; opacity: 1; }
  .hero-art { position: relative; z-index: 2; }
  .plate { position: absolute; border-radius: 50%; overflow: hidden; }
  .plate img { width: 100%; height: 100%; object-fit: cover; }
  .hero-art .p1 { width: min(30vw, 440px); aspect-ratio: 1; right: 12%; top: 50%; transform: translateY(-54%); border: 12px solid var(--cr); }
  .hero-art .p1 img { transform: scale(1.12); }
  .hero-art .p2 { width: min(11vw, 160px); aspect-ratio: 1; right: 4%; bottom: 14%; border: 8px solid var(--m); box-shadow: 0 0 0 4px var(--cr); }
  .hero-art .kt { position: absolute; left: 6%; top: 18%; transform: rotate(-10deg); font-size: clamp(16px, 1.4vw, 22px); }
  .hero-art .stamp-note { position: absolute; left: 4%; bottom: 20%; transform: rotate(4deg); }
  .arrows { display: flex; gap: 10px; margin-left: 16px; }
  .arrows button { width: 44px; height: 44px; border-radius: 50%; background: var(--cr); color: var(--m); display: grid; place-items: center; }

  /* stempel bergerigi */
  .stamp { --r: 6px; background: var(--cr); color: var(--m); padding: 18px 22px;
           -webkit-mask: radial-gradient(var(--r) at var(--r) var(--r), #0000 97%, #000) calc(-1 * var(--r)) calc(-1 * var(--r)) / calc(3 * var(--r)) calc(3 * var(--r)) round, linear-gradient(#000 0 0) 50% / calc(100% - 3 * var(--r)) calc(100% - 3 * var(--r)) no-repeat; }
  .stamp-out { filter: drop-shadow(1.3px 0 0 var(--m)) drop-shadow(-1.3px 0 0 var(--m)) drop-shadow(0 1.3px 0 var(--m)) drop-shadow(0 -1.3px 0 var(--m)); }

  /* ---------- PANEL PILIH LAYANAN ---------- */
  .order { position: relative; z-index: 5; margin-top: clamp(-64px, -4.5vw, -48px); }
  .order-card { background: var(--cr); border-radius: 26px; box-shadow: inset 0 0 0 1.5px var(--m), 0 0 0 8px var(--cr);
                display: grid; grid-template-columns: auto 1fr auto auto; align-items: center; gap: 18px; padding: 16px 16px 16px 18px; margin-inline: clamp(12px, 2vw, 32px); }
  .seg { display: flex; background: var(--pp); border-radius: 999px; padding: 5px; gap: 4px; }
  .seg button { display: inline-flex; align-items: center; gap: 8px; height: 44px; padding: 0 18px; border-radius: 999px; font: 700 14px var(--f-accent); white-space: nowrap; }
  .seg button.on { background: var(--m); color: var(--cr); }
  .addr { display: flex; align-items: center; gap: 12px; min-width: 0; padding: 0 6px; }
  .addr .lbl { font: 800 10.5px var(--f-accent); letter-spacing: .14em; text-transform: uppercase; opacity: .7; }
  .addr input { all: unset; width: 100%; font: 600 15px var(--f-body); color: var(--m); text-overflow: ellipsis; }
  .addr .ic { width: 22px; height: 22px; }
  .zone { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; font: 600 12.5px var(--f-body); white-space: nowrap; }

  /* ---------- KATEGORI ---------- */
  section.block { padding-top: clamp(56px, 6vw, 88px); }
  .cats { display: grid; grid-template-columns: repeat(auto-fit, minmax(118px, 1fr)); gap: clamp(12px, 1.6vw, 24px); }
  .cat { display: flex; flex-direction: column; align-items: center; gap: 12px; font: 700 15px var(--f-accent); }
  .cat .ph { width: 100%; max-width: 150px; aspect-ratio: 1; border-radius: 50%; overflow: hidden; border: 6px solid var(--cr); box-shadow: 0 0 0 1.5px var(--m15); transition: .2s; }
  .cat .ph img { width: 100%; height: 100%; object-fit: cover; transition: .3s; }
  .cat:hover .ph { box-shadow: 0 0 0 4px var(--bl); }
  .cat:hover img { transform: scale(1.06); }
  .cat.on .ph { box-shadow: 0 0 0 3px var(--m); }

  /* ---------- PROMO ---------- */
  .promos { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: clamp(16px, 1.8vw, 26px); }
  .promo { border-radius: 26px; overflow: hidden; box-shadow: inset 0 0 0 1.5px var(--m); display: flex; flex-direction: column; background: var(--cr); }
  .promo .ph { position: relative; aspect-ratio: 16 / 10; margin: 10px 10px 0; border-radius: 18px; overflow: hidden; }
  .promo .ph img { width: 100%; height: 100%; object-fit: cover; }
  .promo .ph .kt { position: absolute; left: 14px; top: 14px; transform: rotate(-6deg); font-size: 15px; }
  .promo .bd { padding: 18px 22px 22px; display: flex; flex-direction: column; flex: 1; }
  .promo h3 { font: 600 20px / 1.25 var(--f-title); }
  .promo p { font-size: 14px; opacity: .75; margin: 6px 0 18px; flex: 1; }
  .promo .ft { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
  .price { font: 700 22px var(--f-title); }
  .price s { font: 500 13px var(--f-body); opacity: .55; margin-left: 6px; }
  .promo.f { background: var(--m); color: var(--cr); box-shadow: none; }
  .promo.f .btn { background: var(--cr); color: var(--m); }

  /* ---------- MENU FAVORIT ---------- */
  .chips { display: flex; gap: 8px; flex-wrap: wrap; }
  .chips button { height: 40px; padding: 0 18px; border-radius: 999px; box-shadow: inset 0 0 0 1.5px var(--m); font: 700 13.5px var(--f-accent); }
  .chips button.on { background: var(--m); color: var(--cr); box-shadow: none; }
  .menu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 250px), 1fr)); gap: clamp(16px, 1.8vw, 26px); }
  .item { border-radius: 24px; box-shadow: inset 0 0 0 1.5px var(--m15); padding: 10px; display: flex; flex-direction: column; transition: .2s; background: var(--cr); }
  .item:hover { box-shadow: inset 0 0 0 1.5px var(--m); }
  .item .ph { position: relative; aspect-ratio: 4 / 3; border-radius: 18px; overflow: hidden; }
  .item .ph img { width: 100%; height: 100%; object-fit: cover; }
  .item .ph .pill { position: absolute; left: 10px; top: 10px; }
  .item .bd { padding: 14px 8px 6px; display: flex; flex-direction: column; flex: 1; }
  .item .cat-lbl { font: 800 10.5px var(--f-accent); letter-spacing: .14em; text-transform: uppercase; opacity: .6; }
  .item h3 { font: 600 17px / 1.3 var(--f-title); margin: 4px 0 4px; }
  .item p { font-size: 13.5px; opacity: .72; flex: 1; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
  .item .ft { display: flex; align-items: center; justify-content: space-between; margin-top: 14px; }
  .item .price { font-size: 19px; }
  .add { width: 42px; height: 42px; border-radius: 50%; background: var(--m); color: var(--cr); display: grid; place-items: center; transition: .2s; }
  .add:hover { transform: scale(1.08); }
  .item.out .ph::after { content: 'Stok Habis'; position: absolute; inset: 0; display: grid; place-items: center; background: rgba(231, 224, 214, .82); font: 700 15px var(--f-title); }
  .item.out .add { background: var(--pp); color: var(--m60); cursor: not-allowed; }

  /* ---------- LACAK PESANAN ---------- */
  .track { position: relative; border-radius: 32px; overflow: hidden; background: var(--m); color: var(--cr);
           display: grid; grid-template-columns: 1fr 1.15fr; gap: clamp(24px, 3vw, 48px); align-items: center; padding: clamp(32px, 4vw, 56px); }
  .track::before { content: ''; position: absolute; right: 0; top: 0; bottom: 0; width: 46%;
                   background: repeating-linear-gradient(90deg, var(--bl) 0 17px, var(--cr) 17px 34px); opacity: .95; }
  .track > * { position: relative; }
  .track h2 { font: 700 clamp(28px, 2.6vw, 40px) / 1.12 var(--f-title); margin: 14px 0 12px; }
  .track p { opacity: .85; max-width: 440px; }
  .steps { display: grid; grid-template-columns: repeat(5, 1fr); margin-top: 18px; }
  .step { position: relative; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 8px; font: 700 12px / 1.3 var(--f-accent); }
  .step::before { content: ''; position: absolute; top: 15px; left: -50%; width: 100%; height: 2.5px; background: var(--m15); z-index: 0; }
  .step:first-child::before { display: none; }
  .step.done::before, .step.now::before { background: var(--m); }
  .step .dot { position: relative; z-index: 1; width: 32px; height: 32px; border-radius: 50%; display: grid; place-items: center; background: var(--cr); box-shadow: inset 0 0 0 1.8px var(--m); }
  .step.done .dot { background: var(--m); color: var(--cr); box-shadow: none; }
  .step.now .dot { background: var(--bl); box-shadow: 0 0 0 6px rgba(178, 194, 210, .5), inset 0 0 0 1.8px var(--m); }
  .step small { display: block; font: 500 11px var(--f-body); opacity: .65; }
  .order-mini { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
  .order-mini b { font: 700 20px var(--f-title); display: block; margin-top: 2px; }

  /* ---------- FOOTER ---------- */
  footer { margin-top: clamp(64px, 7vw, 100px); background: var(--m); color: var(--cr); }
  .torn { display: block; width: 100%; height: 26px; }
  .foot { display: grid; grid-template-columns: 1.4fr repeat(3, 1fr); gap: clamp(24px, 3vw, 56px); padding: 40px 0 36px; }
  .foot h4 { font: 800 12px var(--f-accent); letter-spacing: .16em; text-transform: uppercase; margin-bottom: 16px; opacity: .75; }
  .foot ul { list-style: none; display: grid; gap: 10px; font-size: 14.5px; }
  .foot ul a:hover { text-decoration: underline; }
  .foot .logo b { font-size: 26px; }
  .foot p { font-size: 14px; opacity: .8; margin: 16px 0 18px; max-width: 320px; }
  .hours { display: inline-flex; flex-direction: column; gap: 2px; }
  .hours small { font: 800 10px var(--f-accent); letter-spacing: .14em; }
  .hours b { font: 700 22px var(--f-title); }
  .copy { border-top: 1px solid rgba(248, 245, 230, .2); padding: 18px 0 26px; display: flex; justify-content: space-between; font: 600 13px var(--f-accent); opacity: .8; flex-wrap: wrap; gap: 10px; }

  /* ---------- tambahan versi dinamis ---------- */
  .p-toast { position: fixed; z-index: 120; left: 50%; bottom: 28px; transform: translate(-50%, 20px); opacity: 0; visibility: hidden; background: var(--m); color: var(--cr);
             border-radius: 999px; padding: 12px 20px; font: 600 14px var(--f-accent); transition: .3s; max-width: calc(100vw - 32px); text-align: center; }
  .p-toast.on { transform: translate(-50%, 0); opacity: 1; visibility: visible; }

  /* logo gambar + teks */
  .logo { flex-direction: row !important; align-items: center; gap: 10px; }
  .logo .logo-mk { width: 46px; height: 46px; border-radius: 50%; flex: none; }
  .logo .logo-tx { display: flex; flex-direction: column; line-height: 1; }
  .logo .logo-tx b { white-space: nowrap; }
  @media (max-width: 520px) { .logo .logo-mk { width: 38px; height: 38px; } .logo .logo-tx b { font-size: 17px; } .logo .logo-tx small { display: none; } }
  @media (max-width: 400px) { .hd-right > .icon-btn { display: none; } }
  .add.busy { opacity: .6; pointer-events: none; }
  [hidden] { display: none !important; }
  #menu { scroll-margin-top: 96px; }
  #promo { scroll-margin-top: 96px; }
  .item .ph .ph-none, .cat .ph .ph-none { width: 100%; height: 100%; display: grid; place-items: center; background: var(--bl); }
  .item .ft { margin-top: auto; padding-top: 14px; }
  .grid-empty { padding: 40px 16px; text-align: center; font: 600 15px var(--f-accent); opacity: .65; }
  @media (max-width: 1180px) { #menu, #promo { scroll-margin-top: 150px; } }

  /* ---------- RESPONSIF ---------- */
  @media (max-width: 1180px) {
    .mainnav { display: none; }
    .order-card { grid-template-columns: 1fr 1fr; }
    .order-card .seg { grid-column: 1 / -1; justify-self: start; }
  }
  @media (max-width: 860px) {
    .hero-card, .track { grid-template-columns: 1fr; }
    .hero-art { min-height: 300px; }
    .hero-art .p1 { width: 260px; right: 50%; transform: translate(50%, -50%); }
    .hero-curve { display: none; }
    .track::before { display: none; }
    .order-card { grid-template-columns: minmax(0, 1fr); margin-inline: 0; }
    .order-card .seg button { flex: 1; padding: 0 10px; justify-content: center; font-size: 13px; }
    .order-card .seg { width: 100%; flex-wrap: wrap; border-radius: 22px; }
    .order-card .seg button { flex: 1 1 auto; height: 40px; }
    .order-card .btn { width: 100%; }
    .zone { align-items: flex-start; }
    .foot { grid-template-columns: 1fr 1fr; }
    .user, .cart .txt, .topbar nav { display: none; }
    .hero-art .stamp-note { left: 2%; bottom: 6%; }
  }

  .ovl { position: fixed; inset: 0; z-index: 100; background: rgba(40, 40, 40, .55); display: none; place-items: center; padding: 20px; }
  .ovl.on { display: grid; }
  .modal { width: min(420px, 100%); background: var(--cr); border-radius: 26px; padding: 30px 28px 26px; text-align: center; }

  /* ---------- perbaikan hero di HP ---------- */
  @media (max-width: 860px) {
    .order { margin-top: 18px; }
    .hero-text { padding-bottom: 8px; }
    .hero-art { min-height: clamp(300px, 92vw, 380px); }
    .hero-art .p1 { width: min(250px, 64vw); top: 46%; border-width: 9px; }
    .hero-art .p2 { width: min(110px, 26vw); right: 5%; bottom: 8%; border-width: 6px; }
    .hero-art .kt { left: 5%; top: 6%; font-size: 15px; }
    .hero-art .stamp-note { left: 4%; bottom: 7%; transform: rotate(-3deg); }
    .hero-art .stamp-note .stamp { padding: 10px 14px !important; }
    .hero-art .stamp-note .stamp div:nth-child(2) { font-size: 20px !important; }
  }
  @media (max-width: 380px) {
    .hero-text { padding-left: 20px; padding-right: 20px; }
    .hero-text h1 { font-size: 30px; }
    .hero-cta .btn { width: 100%; }
  }
</style>
</head>
<body>

<!-- simbol ikon -->
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
  <symbol id="i-pin" viewBox="0 0 24 24"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></symbol>
  <symbol id="i-truck" viewBox="0 0 24 24"><path d="M2 6h11v10H2zM13 9h4l4 4v3h-8"/><circle cx="6" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></symbol>
  <symbol id="i-bag" viewBox="0 0 24 24"><path d="M5 8h14l-1 13H6z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></symbol>
  <symbol id="i-dine" viewBox="0 0 24 24"><path d="M6 3v7a2 2 0 0 0 4 0V3M8 12v9"/><path d="M17 3c-2.2 2-2.2 6.5 0 8.5V21"/></symbol>
  <symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></symbol>
  <symbol id="i-cart" viewBox="0 0 24 24"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h3l2.6 12.2a1 1 0 0 0 1 .8h9.6a1 1 0 0 0 1-.8L21 7H6"/></symbol>
  <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
  <symbol id="i-arr" viewBox="0 0 24 24"><path d="M4 12h16M14 6l6 6-6 6"/></symbol>
  <symbol id="i-arrl" viewBox="0 0 24 24"><path d="M20 12H4M10 6l-6 6 6 6"/></symbol>
  <symbol id="i-check" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7"/></symbol>
  <symbol id="i-receipt" viewBox="0 0 24 24"><path d="M5 3h14v18l-2.5-1.5L14 21l-2-1.5L10 21l-2.5-1.5L5 21z"/><path d="M9 8h6M9 12h6"/></symbol>
  <symbol id="i-logout" viewBox="0 0 24 24"><path d="M15 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-4"/><path d="M10 17l-5-5 5-5M5 12h11"/></symbol>
  <symbol id="i-help" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 0 1 5 0c0 1.7-2.5 2-2.5 4M12 17h.01"/></symbol>
</svg>

@include('partials.pelanggan.topbar')

<header>
  <div class="wrap">
    <a class="logo" href="{{ route('pelanggan.beranda') }}"><img class="logo-mk" src="{{ asset('images/logo-192.png') }}" alt=""><span class="logo-tx"><b>RESTO FLOW</b><small>PIZZA &amp; PASTA HOUSE</small></span></a>
    @include('partials.pelanggan.nav')
    <div class="hd-right">
      <button class="icon-btn" aria-label="Cari menu"><svg class="ic"><use href="#i-search"/></svg></button>
      @include('partials.profile-menu', ['trigger' => 'user'])
      @include('partials.pelanggan.cart-button')
    </div>
  </div>
  @include('partials.pelanggan.catstrip')
</header>

<main>
  <!-- HERO -->
  <section class="hero">
    <div class="wrap">
      <div class="hero-card">
        <svg class="hero-curve" viewBox="0 0 1000 560" preserveAspectRatio="none" aria-hidden="true">
          <path d="M610 0C540 120 520 260 560 380C590 470 650 530 700 560H1000V0Z" fill="url(#st)"/>
          <defs><pattern id="st" width="34" height="10" patternUnits="userSpaceOnUse"><rect width="17" height="10" fill="#B2C2D2"/><rect x="17" width="17" height="10" fill="#F8F5E6"/></pattern></defs>
        </svg>
        <div class="hero-text">
          <div><span class="pill b">PROMO MINGGU INI</span></div>
          <h1>2 Pizza Reguler<br>cuma <em>Rp 149rb</em></h1>
          <p>Berlaku untuk Delivery, Take Away, dan Dine In setiap Senin–Kamis sampai 30 November 2026.</p>
          <div class="hero-cta"><a class="btn cr" href="#menu">Pesan Sekarang <svg class="ic"><use href="#i-arr"/></svg></a><a class="btn ghost" href="#promo">Lihat Semua Promo</a></div>
          <div class="hero-dots"><i class="on"></i><i></i><i></i><div class="arrows"><button aria-label="Sebelumnya"><svg class="ic"><use href="#i-arrl"/></svg></button><button aria-label="Berikutnya"><svg class="ic"><use href="#i-arr"/></svg></button></div></div>
        </div>
        <div class="hero-art">
          <div class="plate p1"><img src="https://images.unsplash.com/photo-1628840042765-356cda07504e?w=1000&q=80" alt="Pizza beef pepperoni"></div>
          <div class="plate p2"><img src="https://images.unsplash.com/photo-1595854341625-f33ee10dbf94?w=500&q=80" alt="Pizza margherita"></div>
          <span class="kt m">HEMAT 30%</span>
          <div class="stamp-out stamp-note"><div class="stamp"><div class="eyebrow" style="font-size:10px">Mulai dari</div><div style="font:700 26px var(--f-title)">Rp 74.500</div><div style="font:500 12px var(--f-body);opacity:.7">per pizza</div></div></div>
        </div>
      </div>
    </div>
  </section>

  <!-- PILIH LAYANAN -->
  <section class="order">
    <div class="wrap">
      <div class="order-card">
        <div class="seg" role="tablist">
          <button class="on" data-mode="delivery"><svg class="ic"><use href="#i-truck"/></svg>Delivery</button>
          <button data-mode="takeaway"><svg class="ic"><use href="#i-bag"/></svg>Take Away</button>
          <button data-mode="dinein"><svg class="ic"><use href="#i-dine"/></svg>Dine In</button>
        </div>
        <label class="addr"><svg class="ic"><use href="#i-pin" id="addrIcon"/></svg>
          <span style="flex:1;min-width:0"><span class="lbl" id="addrLbl">Kirim ke</span><input id="addrIn" value="{{ auth()->user()->alamat }}" placeholder="Alamat pengiriman (isi di checkout)" readonly></span></label>
        <div class="zone" id="zoneInfo"></div>
        <a class="btn" href="#menu" data-kat="">Mulai Pesan</a>
      </div>
    </div>
  </section>

  <!-- KATEGORI -->
  <section class="block">
    <div class="wrap">
      <div class="sec-head"><div><div class="eyebrow">Kategori</div><h2 class="h2">Mau makan apa hari ini?</h2></div><a class="link" href="{{ route('pelanggan.beranda') }}#menu" data-kat="">Lihat semua menu <svg class="ic"><use href="#i-arr"/></svg></a></div>
      <div class="cats">
        @foreach ($kategoriFoto as $kf)
          <a href="{{ route('pelanggan.beranda', ['kategori' => $kf['k']->slug()]) }}#menu" data-kat="{{ $kf['k']->slug() }}" @class(['cat', 'on' => $aktif === $kf['k']->slug()])><span class="ph">@if ($kf['foto'])<img src="{{ $kf['foto'] }}" alt="">@else<span class="ph-none"><svg class="ic"><use href="#i-dine"/></svg></span>@endif</span>{{ $kf['k']->nama_kategori }}</a>
        @endforeach
      </div>
    </div>
  </section>

  <!-- PROMO -->
  <section class="block" id="promo">
    <div class="wrap">
      <div class="sec-head"><div><div class="eyebrow">Hemat lebih banyak</div><h2 class="h2">Promo Spesial</h2><p>Pilih paket favorit untuk makan berdua, bareng keluarga, atau sendiri.</p></div><a class="link" href="#">Semua promo <svg class="ic"><use href="#i-arr"/></svg></a></div>
      <div class="promos">
        <article class="promo">
          <div class="ph"><img src="https://images.unsplash.com/photo-1513104890138-7c749659a591?w=800&q=80" alt=""><span class="kt m">PAKET BERDUA</span></div>
          <div class="bd"><h3>1 Pizza Reguler + 2 Lemon Iced Tea</h3><p>Pilih pizza reguler favoritmu, lengkap dengan dua minuman segar.</p>
            <div class="ft"><span class="price">Rp 109.000<s>Rp 145.000</s></span><a class="btn sm" href="#menu" data-kat="">Pesan</a></div></div>
        </article>
        <article class="promo f">
          <div class="ph"><img src="https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=800&q=80" alt=""><span class="kt">PAKET KELUARGA</span></div>
          <div class="bd"><h3>2 Pizza Large + Kentang + 4 Minuman</h3><p>Pas untuk 4–5 orang. Berlaku untuk semua jenis layanan.</p>
            <div class="ft"><span class="price">Rp 289.000<s style="color:inherit">Rp 372.000</s></span><a class="btn sm" href="#menu" data-kat="">Pesan</a></div></div>
        </article>
        <article class="promo">
          <div class="ph"><img src="https://images.unsplash.com/photo-1563379926898-05f4575a45d8?w=800&q=80" alt=""><span class="kt m">MAKAN SIANG</span></div>
          <div class="bd"><h3>Pasta + Minuman Pilihan</h3><p>Paket makan siang Senin–Jumat pukul 11.00–15.00.</p>
            <div class="ft"><span class="price">Rp 65.000<s>Rp 77.000</s></span><a class="btn sm" href="#menu" data-kat="">Pesan</a></div></div>
        </article>
      </div>
    </div>
  </section>

  <!-- MENU FAVORIT -->
  <section class="block" id="menu">
    <div class="wrap">
      <div class="sec-head"><div><div class="eyebrow">Menu</div><h2 class="h2">Favorit Pelanggan</h2></div>
        <div class="chips" id="chips">
          <button data-kat="" @class(['on' => ! $aktif])>Semua</button>
          @foreach ($kategori as $k)
            <button data-kat="{{ $k->slug() }}" @class(['on' => $aktif === $k->slug()])>{{ $k->nama_kategori }}</button>
          @endforeach
        </div></div>
      <div class="menu-grid" id="grid">
        @foreach ($menu as $m)
          <article class="item" data-c="{{ $m->kategori->slug() }}" @if ($aktif && $aktif !== $m->kategori->slug()) style="display:none" @endif>
            <div class="ph">@if ($m->fotoUrl())<img src="{{ $m->fotoUrl() }}" alt="{{ $m->nama_menu }}" loading="lazy">@else<span class="ph-none"><svg class="ic"><use href="#i-dine"/></svg></span>@endif</div>
            <div class="bd"><span class="cat-lbl">{{ $m->kategori->nama_kategori }}</span><h3>{{ $m->nama_menu }}</h3>
            <div class="ft"><span class="price">Rp {{ number_format($m->harga, 0, ',', '.') }}</span><button class="add" data-id="{{ $m->id }}" data-p="{{ (int) $m->harga }}" aria-label="Tambah {{ $m->nama_menu }} ke keranjang"><svg class="ic"><use href="#i-plus"/></svg></button></div></div></article>
        @endforeach
      </div>
      <p class="grid-empty" id="gridEmpty" @if (! ($menu->isEmpty() || ($aktif && ! $menu->contains(fn ($m) => $m->kategori->slug() === $aktif)))) hidden @endif>Belum ada menu yang tersedia di kategori ini.</p>
    </div>
  </section>

  <!-- LACAK PESANAN -->
  <section class="block" id="lacak">
    <div class="wrap">
      <div class="track">
        <div><span class="pill b">LACAK PESANAN</span><h2>Pantau pesananmu dari dapur sampai depan pintu</h2>
          <p>Status pesanan diperbarui secara real-time: dikonfirmasi kasir, dimasak sesuai antrean, hingga diantar kurir.</p>
          <div class="hero-cta"><a class="btn cr" href="{{ route('pelanggan.tracking') }}">Lacak Pesanan <svg class="ic"><use href="#i-arr"/></svg></a></div></div>
        <div class="stamp-out"><div class="stamp" style="padding:28px 30px">
          @if ($pesananAktif)
            @php $tl = \App\Support\TimelinePesanan::untuk($pesananAktif); @endphp
            <div class="order-mini"><div><span class="eyebrow" style="font-size:10.5px">Pesanan aktif</span><b>{{ $pesananAktif->kode() }}</b><span style="font-size:13px;opacity:.7">{{ $pesananAktif->layananDetail() }} · {{ $pesananAktif->detail->sum('jumlah') }} item</span></div><span class="pill m">{{ $pesananAktif->statusLabel() }}</span></div>
            <div class="steps">
              @foreach ($tl as $l)
                <div class="step {{ $l['state'] }}"><span class="dot">@if ($l['state'] === 'done')<svg class="ic" style="width:16px;height:16px;stroke-width:2.6"><use href="#i-check"/></svg>@elseif ($l['state'] === 'now' && $l['ikon'])<svg class="ic" style="width:15px;height:15px"><use href="#{{ $l['ikon'] }}"/></svg>@endif</span>{{ $l['label'] }}<small>{{ $l['waktu'] ?? '—' }}</small></div>
              @endforeach
            </div>
          @else
            <div class="order-mini"><div><span class="eyebrow" style="font-size:10.5px">Pesanan aktif</span><b>Belum ada pesanan</b><span style="font-size:13px;opacity:.7">Pesanan yang sedang berjalan tampil di sini</span></div></div>
          @endif
</div>
        </div></div>
      </div>
    </div>
  </section>
</main>

<footer>
  <svg class="torn" viewBox="0 0 1000 26" preserveAspectRatio="none" aria-hidden="true"><rect width="1000" height="13" fill="#F8F5E6"/><rect y="13" width="1000" height="13" fill="#97173A"/>
    <path d="M0,7 L22,9 L45,5 L71,8 L96,6 L121,10 L149,6 L177,8 L203,5 L232,9 L258,6 L285,8 L311,5 L340,9 L366,7 L393,10 L420,6 L448,8 L475,5 L503,9 L530,6 L556,8 L584,5 L611,9 L637,7 L664,10 L690,6 L718,8 L745,5 L772,9 L799,6 L826,8 L852,5 L880,9 L907,7 L934,10 L960,6 L1000,8 L1000,19 L972,17 L944,21 L917,18 L889,20 L861,16 L834,19 L806,17 L779,21 L751,18 L723,20 L696,16 L668,19 L641,17 L613,21 L585,18 L558,20 L530,16 L503,19 L476,17 L448,21 L421,18 L393,20 L366,16 L338,19 L311,17 L283,21 L256,18 L228,20 L200,16 L173,19 L146,17 L118,21 L91,18 L63,20 L36,16 L0,19 Z" fill="#E7E0D6"/></svg>
  <div class="wrap">
    <div class="foot">
      <div><a class="logo" href="#"><img class="logo-mk" src="{{ asset('images/logo-192.png') }}" alt=""><span class="logo-tx"><b>RESTO FLOW</b><small>PIZZA &amp; PASTA HOUSE</small></span></a>
        <p>Pesan pizza, pasta, dan menu favorit lainnya untuk Delivery, Take Away, atau Dine In.</p>
        <div class="hours"><small>JAM OPERASIONAL</small><b>10.00 – 22.00</b></div></div>
      <div><h4>Menu</h4><ul><li><a href="#">Pizza</a></li><li><a href="#">Pasta</a></li><li><a href="#">Burger</a></li><li><a href="#">Camilan</a></li><li><a href="#">Minuman</a></li></ul></div>
      <div><h4>Layanan</h4><ul><li><a href="#">Delivery</a></li><li><a href="#">Take Away</a></li><li><a href="#">Dine In</a></li><li><a href="{{ route('pelanggan.tracking') }}">Lacak Pesanan</a></li><li><a href="#">Zona Pengiriman</a></li></ul></div>
      <div><h4>Bantuan</h4><ul><li><a href="#">Pertanyaan Umum</a></li><li><a href="#">Cara Pembayaran</a></li><li><a href="#">Syarat &amp; Ketentuan</a></li><li><a href="#">0811 6000 1234</a></li></ul></div>
    </div>
    <div class="copy"><span>© {{ date('Y') }} {{ config('resto.nama') }}. Semua hak dilindungi.</span><span>Jl. Gatot Subroto No. 100, Medan</span></div>
  </div>
</footer>

<div class="p-toast" id="pToast" role="status"></div>
<script src="{{ asset('js/pelanggan.js') }}?v={{ filemtime(public_path('js/pelanggan.js')) }}"></script>
@php
  $infoZona = $ongkirMin !== null
      ? '<span class="pill b">Ongkir mulai Rp '.number_format($ongkirMin, 0, ',', '.').'</span><span>Pilih zona saat checkout</span>'
      : '<span class="pill b">Delivery belum tersedia</span>';
@endphp
<script>
  // Ganti isian sesuai jenis layanan (sesuai alur Pilih Jenis Layanan)
  const modes = {
    delivery: { lbl: 'Kirim ke', val: @json(auth()->user()->alamat ?? ''), icon: '#i-pin',
                zone: @json($infoZona) },
    takeaway: { lbl: 'Ambil di kasir', val: 'Secepatnya setelah pesanan siap', icon: '#i-clock',
                zone: '<span class="pill b">Tanpa ongkir</span><span>Siap ± 20 menit setelah dikonfirmasi</span>' },
    dinein:   { lbl: 'Nomor meja', val: 'Isi nomor meja saat checkout', icon: '#i-dine',
                zone: '<span class="pill b">Makan di restoran</span><span>Pesanan langsung masuk antrean dapur</span>' }
  };
  document.querySelectorAll('.seg button').forEach(b => b.addEventListener('click', () => {
    document.querySelectorAll('.seg button').forEach(x => x.classList.toggle('on', x === b));
    const m = modes[b.dataset.mode];
    addrLbl.textContent = m.lbl; addrIn.value = m.val; zoneInfo.innerHTML = m.zone;
    addrIcon.setAttribute('href', m.icon);
  }));

  // Filter menu per kategori: navbar, baris kategori (HP), lingkaran kategori, dan chips.
  // Tanpa reload; alamat jadi /beranda?kategori=... supaya tombol Back dan link bisa dibagikan.
  function pilihKategori(slug) {
    document.querySelectorAll('[data-kat]').forEach(el => {
      if (el.closest('.mainnav, .catstrip, .cats, #chips')) el.classList.toggle('on', el.dataset.kat === slug && (slug !== '' || el.closest('#chips')));
    });
    let tampil = 0;
    document.querySelectorAll('#grid .item').forEach(it => { const ok = !slug || it.dataset.c === slug; it.style.display = ok ? '' : 'none'; if (ok) tampil++; });
    gridEmpty.hidden = tampil > 0;
  }
  function slugDariUrl() { return new URLSearchParams(location.search).get('kategori') || ''; }
  document.addEventListener('click', e => {
    const el = e.target.closest('[data-kat]'); if (!el) return;
    e.preventDefault();
    const slug = el.dataset.kat;
    if (slug !== slugDariUrl()) history.pushState({ kategori: slug }, '', slug ? '?kategori=' + encodeURIComponent(slug) : location.pathname);
    pilihKategori(slug);
    document.getElementById('menu').scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
  document.addEventListener('click', e => {
    const el = e.target.closest('[data-promo]'); if (!el) return;
    e.preventDefault(); document.getElementById('promo').scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
  window.addEventListener('popstate', () => pilihKategori(slugDariUrl()));
  if (slugDariUrl()) document.getElementById('menu').scrollIntoView({ block: 'start' });

  // Tambah ke keranjang (disimpan di session)
  document.querySelectorAll('.add[data-id]').forEach(b => b.addEventListener('click', () => {
    b.classList.add('busy');
    sendJson(@json(route('pelanggan.keranjang.store')), 'POST', { id_menu: +b.dataset.id })
      .then(r => { setCart(r); pToast(r.message); })
      .catch(err => pToast(err.message))
      .finally(() => b.classList.remove('busy'));
  }));
  document.getElementById('zoneInfo').innerHTML = modes.delivery.zone;
</script>
<script>
</script>
@include('partials.logout')
</body>
</html>
