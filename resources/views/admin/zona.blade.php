<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Zona & Tarif Pengiriman · SIM Resto</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Raleway:wght@600;700;800&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  /* =========================================================
     PALET (wajib) & FONT
     Poppins : judul, angka, tombol | Raleway : navigasi, label | Open Sans : isi
     ========================================================= */
  :root {
    --m: #97173A; --cr: #F8F5E6; --bl: #B2C2D2; --pp: #E7E0D6; --dk: #282828;
    --m08: rgba(151, 23, 58, .08); --m15: rgba(151, 23, 58, .15); --m60: rgba(151, 23, 58, .65);
    --f-title: 'Poppins', sans-serif; --f-accent: 'Raleway', sans-serif; --f-body: 'Open Sans', sans-serif;
    --side: clamp(244px, 18vw, 276px);
    --pad: clamp(18px, 2.4vw, 40px);
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: var(--cr); color: var(--m); font: 400 14.5px/1.55 var(--f-body); -webkit-font-smoothing: antialiased; }
  a { color: inherit; text-decoration: none; }
  img { display: block; max-width: 100%; }
  button, input, select, textarea { font: inherit; color: inherit; }
  button { background: none; border: 0; cursor: pointer; }
  .ic { width: 18px; height: 18px; flex: none; fill: none; stroke: currentColor; stroke-width: 1.9; stroke-linecap: round; stroke-linejoin: round; }

  /* ---------- LAYOUT ---------- */
  .app { display: grid; grid-template-columns: var(--side) minmax(0, 1fr); min-height: 100vh; }
  .side { position: relative; min-height: 100vh; background: var(--m); color: var(--cr); z-index: 30; }
  .side-in { position: sticky; top: 0; height: 100vh; display: flex; flex-direction: column; padding: 24px 18px 20px 20px; overflow-y: auto; }
  .side::after { content: ''; position: absolute; top: 0; right: -14px; width: 16px; height: 100%; pointer-events: none;
                 background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 200' preserveAspectRatio='none'%3E%3Cpath d='M0 0H6L9 8 5 15 10 24 6 32 11 41 6 50 9 58 5 66 10 75 6 84 11 92 6 101 9 110 5 118 10 127 6 136 11 144 6 153 9 162 5 170 10 179 6 188 9 200H0Z' fill='%23E7E0D6'/%3E%3Cpath d='M0 0H3L6 9 2 17 7 25 3 34 8 42 3 51 6 59 2 68 7 76 3 85 8 93 3 102 6 111 2 119 7 128 3 137 8 145 3 154 6 163 2 171 7 180 3 189 6 200H0Z' fill='%2397173A'/%3E%3C/svg%3E") 0 0 / 100% 100% no-repeat; }
  .brand { display: flex; align-items: center; gap: 12px; padding: 0 4px 20px; }
  .brand .mk { width: 44px; height: 44px; border-radius: 14px; background: var(--cr); color: var(--m); display: grid; place-items: center; font: 800 20px var(--f-accent); flex: none; }
  .brand b { display: block; font: 800 18px var(--f-accent); letter-spacing: .08em; line-height: 1.1; }
  .brand small { font: 700 9.5px var(--f-accent); letter-spacing: .22em; opacity: .7; }
  .nav-lbl { font: 800 10px var(--f-accent); letter-spacing: .18em; text-transform: uppercase; opacity: .55; margin: 14px 0 8px 14px; }
  .nav { display: flex; align-items: center; gap: 11px; padding: 11px 14px; border-radius: 999px; font: 700 14px var(--f-accent); transition: .15s; white-space: nowrap; width: 100%; text-align: left; }
  .nav:hover { background: rgba(248, 245, 230, .1); }
  .nav.on { background: var(--cr); color: var(--m); }
  .nav .bd { margin-left: auto; background: var(--bl); color: var(--m); font: 700 11px var(--f-title); padding: 1px 8px; border-radius: 999px; }
  .side .spacer { flex: 1; }
  .hours { --r: 5px; background: var(--cr); color: var(--m); padding: 16px 18px; margin: 12px 0 10px;
           -webkit-mask: radial-gradient(var(--r) at var(--r) var(--r), #0000 97%, #000) calc(-1 * var(--r)) calc(-1 * var(--r)) / calc(3 * var(--r)) calc(3 * var(--r)) round, linear-gradient(#000 0 0) 50% / calc(100% - 3 * var(--r)) calc(100% - 3 * var(--r)) no-repeat; }
  .hours small { font: 800 9.5px var(--f-accent); letter-spacing: .14em; }
  .hours b { display: block; font: 700 21px var(--f-title); margin-top: 2px; }
  .hours span { font: 500 11.5px var(--f-body); opacity: .7; }

  .main { min-width: 0; padding: 22px var(--pad) 48px calc(var(--pad) + 8px); }
  .topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 26px; flex-wrap: wrap; }
  .crumb { font: 600 12.5px var(--f-accent); opacity: .65; }
  .topbar h1 { font: 700 clamp(26px, 2.3vw, 34px) / 1.15 var(--f-title); margin-top: 2px; }
  .topbar .sub { font-size: 14px; opacity: .7; margin-top: 2px; }
  .tb-right { display: flex; align-items: center; gap: 10px; }
  .search { display: flex; align-items: center; gap: 10px; height: 44px; padding: 0 16px; border-radius: 999px; background: var(--pp); width: clamp(200px, 20vw, 300px); }
  .search input { all: unset; flex: 1; min-width: 0; font: 500 14px var(--f-body); }
  .search input::placeholder { color: rgba(151, 23, 58, .5); }
  .icon-btn { width: 44px; height: 44px; border-radius: 50%; display: grid; place-items: center; box-shadow: inset 0 0 0 1.5px var(--m); position: relative; flex: none; }
  .icon-btn .dot { position: absolute; top: -3px; right: -3px; min-width: 18px; height: 18px; border-radius: 9px; background: var(--m); color: var(--cr); font: 700 10px var(--f-title); display: grid; place-items: center; outline: 2px solid var(--cr); }
  .me { display: flex; align-items: center; gap: 10px; padding: 4px 16px 4px 4px; border-radius: 999px; box-shadow: inset 0 0 0 1.5px var(--m); font: 700 13.5px var(--f-accent); white-space: nowrap; }
  .av { width: 36px; height: 36px; border-radius: 50%; background: var(--bl); display: grid; place-items: center; font: 700 12.5px var(--f-title); color: var(--m); flex: none; }
  .av.m { background: var(--m); color: var(--cr); }
  .av.p { background: var(--pp); }

  /* ---------- KOMPONEN ---------- */
  .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 44px; padding: 0 20px; border-radius: 999px;
         background: var(--m); color: var(--cr); font: 600 14px var(--f-title); white-space: nowrap; transition: .15s; }
  .btn:hover { background: #7f1331; }
  .btn.o { background: transparent; color: var(--m); box-shadow: inset 0 0 0 1.5px var(--m); }
  .btn.o:hover { background: var(--pp); }
  .btn.b { background: var(--bl); color: var(--m); }
  .btn.cr { background: var(--cr); color: var(--m); }
  .btn.sm { height: 36px; padding: 0 14px; font-size: 13px; }
  .btn.lg { height: 50px; padding: 0 26px; font-size: 15px; }
  .pill { display: inline-flex; align-items: center; gap: 6px; padding: 4px 11px; border-radius: 999px; font: 700 11.5px var(--f-accent); white-space: nowrap; background: var(--cr); box-shadow: inset 0 0 0 1.2px var(--m); }
  .pill.b { background: var(--bl); box-shadow: none; } .pill.m { background: var(--m); color: var(--cr); box-shadow: none; }
  .pill.p { background: var(--pp); box-shadow: none; } .pill.c { background: var(--cr); color: var(--m); box-shadow: none; }
  .eyebrow { font: 800 10.5px var(--f-accent); letter-spacing: .14em; text-transform: uppercase; }
  .grid { display: grid; gap: clamp(14px, 1.4vw, 20px); }
  .g4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
  .g3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  .g2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .card { border-radius: 22px; background: var(--cr); box-shadow: inset 0 0 0 1.5px var(--m); padding: clamp(16px, 1.5vw, 22px); min-width: 0; }
  .card.soft { box-shadow: inset 0 0 0 1.5px var(--m15); }
  .card.f { background: var(--m); color: var(--cr); box-shadow: none; }
  .card.b { background: var(--bl); box-shadow: none; }
  .card.p { background: var(--pp); box-shadow: none; }
  .card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
  .card-head h2 { font: 600 18px var(--f-title); }
  .card-head p { font-size: 13px; opacity: .7; }
  .stat .top { display: flex; justify-content: space-between; align-items: center; }
  .stat .v { font: 700 clamp(26px, 2.3vw, 34px) / 1 var(--f-title); margin: 14px 0 8px; }
  .stat .t { font: 500 12.5px var(--f-body); opacity: .75; }
  .stat .ico { width: 38px; height: 38px; border-radius: 12px; display: grid; place-items: center; background: rgba(248, 245, 230, .18); }
  .card:not(.f) .stat .ico, .stat:not(.f) .ico { background: var(--m08); }
  .stamp { --r: 5px; background: var(--cr); color: var(--m);
           -webkit-mask: radial-gradient(var(--r) at var(--r) var(--r), #0000 97%, #000) calc(-1 * var(--r)) calc(-1 * var(--r)) / calc(3 * var(--r)) calc(3 * var(--r)) round, linear-gradient(#000 0 0) 50% / calc(100% - 3 * var(--r)) calc(100% - 3 * var(--r)) no-repeat; }
  .stamp-out { filter: drop-shadow(1.3px 0 0 var(--m)) drop-shadow(-1.3px 0 0 var(--m)) drop-shadow(0 1.3px 0 var(--m)) drop-shadow(0 -1.3px 0 var(--m)); }
  .kt { position: relative; display: inline-flex; align-items: center; padding: 5px 12px 5px 24px; border-radius: 10px; background: var(--bl); font: 700 13.5px var(--f-title); white-space: nowrap; }
  .kt::before { content: ''; position: absolute; left: 8px; top: 50%; width: 8px; height: 8px; margin-top: -4px; border-radius: 50%; background: var(--cr); box-shadow: inset 0 0 0 1.5px var(--m); }
  .kt.m { background: var(--m); color: var(--cr); }
  .stripes { background: repeating-linear-gradient(90deg, var(--bl) 0 17px, var(--cr) 17px 34px); }

  /* tab bertumpuk (gaya referensi) */
  .mods { display: flex; flex-direction: column; padding-top: 12px; }
  .mod { position: relative; background: var(--m); color: var(--cr); border-radius: 0 16px 16px 16px; padding: 12px 16px 18px; margin-top: -8px; }
  .mod:first-child { margin-top: 0; }
  .mod::before { content: ''; position: absolute; left: 0; top: -12px; width: 80px; height: 13px; background: inherit; border-radius: 12px 12px 0 0; }
  .mod.b { background: var(--bl); color: var(--m); } .mod.c { background: var(--cr); color: var(--m); box-shadow: inset 0 0 0 1.5px var(--m); }
  .mod.c::before { box-shadow: inset 1.5px 1.5px 0 var(--m), inset -1.5px 0 0 var(--m); }
  .mod .row { display: grid; grid-template-columns: 44px minmax(0, 1fr) 54px minmax(80px, 34%); align-items: center; gap: 12px; }
  .mod .no { font: 700 22px var(--f-title); }
  .mod .lbl { font: 700 13.5px var(--f-accent); }
  .mod .val { font: 700 20px var(--f-title); text-align: right; }
  .bar { height: 7px; border-radius: 4px; background: rgba(248, 245, 230, .3); overflow: hidden; }
  .bar i { display: block; height: 100%; border-radius: 4px; background: var(--cr); }
  .mod.b .bar, .mod.c .bar, .card .bar.dark { background: var(--m15); }
  .mod.b .bar i, .mod.c .bar i, .card .bar.dark i { background: var(--m); }

  /* tabel */
  .tbl-wrap { overflow-x: auto; margin: 0 -4px; padding: 0 4px; }
  table { width: 100%; border-collapse: collapse; }
  th { font: 800 10.5px var(--f-accent); letter-spacing: .1em; text-transform: uppercase; text-align: left; padding: 10px 10px; border-bottom: 1.5px solid var(--m); white-space: nowrap; }
  td { padding: 12px 10px; border-bottom: 1px solid var(--m15); font-size: 14px; vertical-align: middle; white-space: nowrap; }
  td.wrap { white-space: normal; min-width: 220px; }
  tr:last-child td { border-bottom: 0; }
  tbody tr { transition: .15s; } tbody tr:hover { background: var(--m08); }
  td b { font: 600 14px var(--f-title); }
  .who { display: flex; align-items: center; gap: 12px; }
  .thumb { width: 52px; height: 52px; border-radius: 14px; object-fit: cover; flex: none; }
  .acts { display: flex; gap: 6px; }
  .ab { width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center; box-shadow: inset 0 0 0 1.3px var(--m); transition: .15s; }
  .ab:hover { background: var(--pp); }
  .ab.f { background: var(--m); color: var(--cr); box-shadow: none; }
  .ab.f:hover { background: #7f1331; }
  .ab .ic { width: 16px; height: 16px; }
  .tfoot { display: flex; justify-content: space-between; align-items: center; margin-top: 14px; font: 600 12.5px var(--f-accent); flex-wrap: wrap; gap: 10px; }
  .pager { display: flex; gap: 6px; }
  .pager button { width: 34px; height: 34px; border-radius: 50%; box-shadow: inset 0 0 0 1.3px var(--m); font: 700 12.5px var(--f-title); }
  .pager button.on { background: var(--m); color: var(--cr); box-shadow: none; }

  .chips { display: flex; gap: 8px; flex-wrap: wrap; }
  .chips button { height: 38px; padding: 0 16px; border-radius: 999px; box-shadow: inset 0 0 0 1.5px var(--m); font: 700 13px var(--f-accent); white-space: nowrap; }
  .chips button.on { background: var(--m); color: var(--cr); box-shadow: none; }
  .chips button span { opacity: .7; margin-left: 4px; }

  /* form */
  .field label { display: block; font: 800 10.5px var(--f-accent); letter-spacing: .12em; text-transform: uppercase; margin-bottom: 7px; }
  .in { display: flex; align-items: center; gap: 10px; min-height: 48px; padding: 0 16px; border-radius: 14px; background: var(--pp); border: 1.5px solid transparent; }
  .in:focus-within { background: var(--cr); border-color: var(--m); box-shadow: 0 0 0 4px var(--bl); }
  .in input, .in select, .in textarea { all: unset; flex: 1; min-width: 0; font: 500 14.5px var(--f-body); color: var(--m); }
  .in input::placeholder, .in textarea::placeholder { color: rgba(151, 23, 58, .5); }
  .in.ta { align-items: flex-start; padding: 12px 16px; } .in.ta textarea { min-height: 60px; line-height: 1.5; }
  .in .suf { font: 700 13px var(--f-accent); opacity: .7; }
  .seg { display: flex; flex-wrap: wrap; gap: 6px; }
  .seg button { height: 38px; padding: 0 14px; border-radius: 999px; box-shadow: inset 0 0 0 1.4px var(--m); font: 700 13px var(--f-accent); }
  .seg button.on { background: var(--m); color: var(--cr); box-shadow: none; }
  .toggle { position: relative; width: 44px; height: 25px; flex: none; display: inline-block; }
  .toggle input { position: absolute; opacity: 0; inset: 0; cursor: pointer; z-index: 1; margin: 0; }
  .toggle i { position: absolute; inset: 0; border-radius: 999px; background: var(--pp); box-shadow: inset 0 0 0 1.4px var(--m15); transition: .2s; }
  .toggle i::after { content: ''; position: absolute; top: 3px; left: 3px; width: 19px; height: 19px; border-radius: 50%; background: var(--cr); box-shadow: inset 0 0 0 1.5px var(--m); transition: .2s; }
  .toggle input:checked + i { background: var(--m); box-shadow: none; }
  .toggle input:checked + i::after { left: 22px; box-shadow: none; }
  .drop { border: 1.8px dashed var(--m60); border-radius: 18px; padding: 18px; display: flex; align-items: center; gap: 14px; }
  .drop .pv { width: 84px; height: 84px; border-radius: 16px; object-fit: cover; flex: none; }
  .note { display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border-radius: 14px; background: var(--pp); font: 500 13px / 1.5 var(--f-body); }
  .note .ic { margin-top: 1px; }

  /* modal & toast */
  .ovl { position: fixed; inset: 0; z-index: 100; background: rgba(40, 40, 40, .55); display: none; place-items: center; padding: 20px; overflow-y: auto; }
  .ovl.on { display: grid; }
  .modal { width: min(640px, 100%); background: var(--cr); border-radius: 26px; overflow: hidden; }
  .modal.sm { width: min(440px, 100%); text-align: center; }
  .modal .mh { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; padding: 24px 28px 0; }
  .modal .mh h3 { font: 700 24px var(--f-title); margin-top: 4px; }
  .modal .mb { padding: 20px 28px; }
  .modal .mf { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 28px 24px; border-top: 1.5px solid var(--m15); align-items: center; }
  .modal .big-ic { width: 70px; height: 70px; border-radius: 50%; display: grid; place-items: center; margin: 28px auto 14px; }
  .modal .big-ic .ic { width: 30px; height: 30px; }
  .fgrid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 16px; }
  .field { min-width: 0; }
  .fgrid .full { grid-column: 1 / -1; }
  .toast { position: fixed; z-index: 120; left: 50%; bottom: 28px; transform: translate(-50%, 20px); opacity: 0; visibility: hidden; background: var(--m); color: var(--cr); border-radius: 999px; padding: 12px 20px; display: flex; gap: 10px; align-items: center; font: 600 14px var(--f-accent); transition: .3s; }
  .toast.on { transform: translate(-50%, 0); opacity: 1; visibility: visible; }

  /* ---------- RESPONSIF ---------- */
  @media (max-width: 1240px) { .g4 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
  @media (max-width: 1400px) { .hide-md { display: none; } }
  @media (max-width: 1300px) { .ropbar { display: none; } }
  @media (max-width: 1180px) { .split-md { grid-template-columns: minmax(0, 1fr) !important; } .split-md .catlist { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); } }
  td .desc { white-space: normal; font-size: 12.5px; opacity: .65; line-height: 1.4; }
  @media (max-width: 1080px) {
    :root { --side: 84px; }
    .side-in { padding: 22px 12px; align-items: center; }
    .brand { padding: 0 0 18px; } .brand div, .nav span, .nav-lbl, .hours, .nav .bd { display: none; }
    .nav { width: 52px; height: 52px; justify-content: center; padding: 0; }
    .search { display: none; }
  }
  @media (max-width: 860px) { .g3, .g2, .split { grid-template-columns: minmax(0, 1fr) !important; } .me span { display: none; } }
  @media (max-width: 640px) { .fgrid { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<svg width="0" height="0" style="position:absolute" aria-hidden="true"><symbol id="i-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></symbol><symbol id="i-users" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.8c2.4.6 4 2.4 4 5.2"/></symbol><symbol id="i-tag" viewBox="0 0 24 24"><path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="7.5" r="1.4"/></symbol><symbol id="i-map" viewBox="0 0 24 24"><path d="M3 6l6-3 6 3 6-3v15l-6 3-6-3-6 3z"/><path d="M9 3v15M15 6v15"/></symbol><symbol id="i-settings" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.9 4.9 7 7M17 17l2.1 2.1M2 12h3M19 12h3M4.9 19.1 7 17M17 7l2.1-2.1"/></symbol><symbol id="i-logout" viewBox="0 0 24 24"><path d="M15 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-4"/><path d="M10 17l-5-5 5-5M5 12h11"/></symbol><symbol id="i-help" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 0 1 5 0c0 1.7-2.5 2-2.5 4M12 17h.01"/></symbol><symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></symbol><symbol id="i-bell" viewBox="0 0 24 24"><path d="M6 16v-5a6 6 0 0 1 12 0v5l2 2H4z"/><path d="M10 21h4"/></symbol><symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol><symbol id="i-x" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></symbol><symbol id="i-edit" viewBox="0 0 24 24"><path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M13.5 6.5l4 4"/></symbol><symbol id="i-trash" viewBox="0 0 24 24"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/></symbol><symbol id="i-check" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7"/></symbol><symbol id="i-alert" viewBox="0 0 24 24"><path d="M12 3.5 2.5 20h19z"/><path d="M12 10v4M12 17h.01"/></symbol><symbol id="i-info" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5h.01"/></symbol><symbol id="i-receipt" viewBox="0 0 24 24"><path d="M5 3h14v18l-2.5-1.5L14 21l-2-1.5L10 21l-2.5-1.5L5 21z"/><path d="M9 8h6M9 12h6"/></symbol><symbol id="i-box" viewBox="0 0 24 24"><path d="M3 7l9-4 9 4v10l-9 4-9-4z"/><path d="M3 7l9 4 9-4M12 11v10"/></symbol><symbol id="i-truck" viewBox="0 0 24 24"><path d="M2 6h11v10H2zM13 9h4l4 4v3h-8"/><circle cx="6" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></symbol><symbol id="i-bag" viewBox="0 0 24 24"><path d="M5 8h14l-1 13H6z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></symbol><symbol id="i-dine" viewBox="0 0 24 24"><path d="M6 3v7a2 2 0 0 0 4 0V3M8 12v9"/><path d="M17 3c-2.2 2-2.2 6.5 0 8.5V21"/></symbol><symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol><symbol id="i-pin" viewBox="0 0 24 24"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></symbol><symbol id="i-upload" viewBox="0 0 24 24"><path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v4h16v-4"/></symbol><symbol id="i-mail" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></symbol><symbol id="i-phone" viewBox="0 0 24 24"><path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2z"/></symbol><symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></symbol><symbol id="i-lock" viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></symbol><symbol id="i-card" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/></symbol><symbol id="i-home" viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z"/></symbol><symbol id="i-file" viewBox="0 0 24 24"><path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6"/></symbol><symbol id="i-wallet" viewBox="0 0 24 24"><path d="M3 7h16a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H3z"/><path d="M3 7V5a2 2 0 0 1 2-2h11v4"/><circle cx="16.5" cy="13.5" r="1"/></symbol><symbol id="i-printer" viewBox="0 0 24 24"><path d="M7 9V3h10v6"/><path d="M5 9h14a2 2 0 0 1 2 2v6h-4v4H7v-4H3v-6a2 2 0 0 1 2-2z"/></symbol><symbol id="i-flame" viewBox="0 0 24 24"><path d="M12 3c1 4 5 5.5 5 10a5 5 0 0 1-10 0c0-2.5 1.5-3.5 2-5 .8 1.5 1.5 2 2.5 2C11 7.5 11 5.5 12 3z"/></symbol><symbol id="i-download" viewBox="0 0 24 24"><path d="M12 4v12M7 11l5 5 5-5"/><path d="M4 20h16"/></symbol><symbol id="i-eye" viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></symbol><symbol id="i-minus" viewBox="0 0 24 24"><path d="M5 12h14"/></symbol><symbol id="i-cal" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></symbol><symbol id="i-cash" viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.8"/></symbol><symbol id="i-chart" viewBox="0 0 24 24"><path d="M5 20V11M11 20V5M17 20v-6M2 20h20"/></symbol><symbol id="i-dots" viewBox="0 0 24 24"><circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/></symbol><symbol id="i-image" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M21 16l-5-5-9 9"/></symbol><symbol id="i-filter" viewBox="0 0 24 24"><path d="M3 5h18l-7 8v6l-4 2v-8z"/></symbol><symbol id="i-arr" viewBox="0 0 24 24"><path d="M4 12h16M14 6l6 6-6 6"/></symbol><symbol id="i-nav" viewBox="0 0 24 24"><path d="M3 11 21 3l-8 18-2-8z"/></symbol><symbol id="i-refresh" viewBox="0 0 24 24"><path d="M20 11a8 8 0 0 0-14.9-3M4 4v4h4M4 13a8 8 0 0 0 14.9 3M20 20v-4h-4"/></symbol></svg>
<div class="app">
  <aside class="side"><div class="side-in">
    <div class="brand"><span class="mk">R</span><div><b>SIM RESTO</b><small>PANEL ADMIN</small></div></div>
    <div class="nav-lbl">Menu utama</div>
    <a class="nav" href="{{ route('admin.dashboard') }}" title="Dashboard"><svg class="ic"><use href="#i-grid"/></svg><span>Dashboard</span></a><a class="nav" href="{{ route('admin.pengguna') }}" title="Data Pengguna"><svg class="ic"><use href="#i-users"/></svg><span>Data Pengguna</span><span class="bd">48</span></a><a class="nav" href="{{ route('admin.menu') }}" title="Kategori & Menu"><svg class="ic"><use href="#i-tag"/></svg><span>Kategori & Menu</span><span class="bd">40</span></a><a class="nav on" href="{{ route('admin.zona') }}" title="Zona & Tarif"><svg class="ic"><use href="#i-map"/></svg><span>Zona & Tarif</span><span class="bd">3</span></a><a class="nav" href="{{ route('admin.pengaturan') }}" title="Pengaturan Sistem"><svg class="ic"><use href="#i-settings"/></svg><span>Pengaturan Sistem</span></a>
    <div class="spacer"></div>
    <div class="hours"><small>JAM OPERASIONAL</small><b>10.00 – 22.00</b><span>Senin, 2 November 2026</span></div>
    <a class="nav" href="#" title="Bantuan"><svg class="ic"><use href="#i-help"/></svg><span>Bantuan</span></a>
    <button class="nav" data-open="mLogout" title="Logout"><svg class="ic"><use href="#i-logout"/></svg><span>Logout</span></button>
  </div></aside>

  <main class="main">
    <div class="topbar">
      <div><div class="crumb">Admin / Zona & Tarif</div><h1>Zona & Tarif Pengiriman</h1><div class="sub">Kelola zona pengiriman dan tarif ongkos kirim</div></div>
      <div class="tb-right">
        <label class="search"><svg class="ic"><use href="#i-search"/></svg><input placeholder="Cari…"></label>
        <button class="icon-btn" aria-label="Notifikasi"><svg class="ic"><use href="#i-bell"/></svg><span class="dot">3</span></button>
        <a class="me" href="#"><span class="av m">{{ auth()->user()->inisial() }}</span><span>{{ auth()->user()->nama }}</span></a>
      </div>
    </div>

    <div class="grid g3"><div class="card stat "><div class="top"><span class="eyebrow">Zona A · 0 – 3 km</span><span class="ico"><svg class="ic"><use href="#i-map"/></svg></span></div><div class="v">Rp 8.000</div><div class="t">214 pengiriman bulan ini</div></div><div class="card stat f"><div class="top"><span class="eyebrow">Zona B · 3 – 6 km</span><span class="ico"><svg class="ic"><use href="#i-map"/></svg></span></div><div class="v">Rp 15.000</div><div class="t">167 pengiriman bulan ini</div></div><div class="card stat b"><div class="top"><span class="eyebrow">Zona C · 6 – 10 km</span><span class="ico"><svg class="ic"><use href="#i-map"/></svg></span></div><div class="v">Rp 22.000</div><div class="t">58 pengiriman bulan ini</div></div></div>
    <div class="grid" style="margin-top:18px">
      <div class="card"><div class="card-head"><div><h2>Daftar Zona Pengiriman</h2><p>Dasar perhitungan ongkos kirim pesanan Delivery</p></div><button class="btn" data-open="mZona"><svg class="ic"><use href="#i-plus"/></svg>Tambah Zona</button></div>
        <div class="tbl-wrap"><table><thead><tr><th>Zona</th><th>Jarak</th><th>Cakupan wilayah</th><th>Tarif ongkir</th><th>Penggunaan</th><th>Aksi</th></tr></thead><tbody><tr><td><div class="who"><span class="av " style="font-size:15px">A</span><b>Zona A</b></div></td><td>0 – 3 km</td><td class="wrap" style="opacity:.75">Medan Baru, Medan Petisah, Medan Polonia</td><td><span class="kt">Rp 8.000</span></td><td>214 kiriman</td><td><div class="acts"><button class="ab" data-open="mZona" title="Ubah"><svg class="ic"><use href="#i-edit"/></svg></button><button class="ab f js-del" data-name="Zona A" title="Hapus"><svg class="ic"><use href="#i-trash"/></svg></button></div></td></tr><tr><td><div class="who"><span class="av m" style="font-size:15px">B</span><b>Zona B</b></div></td><td>3 – 6 km</td><td class="wrap" style="opacity:.75">Medan Sunggal, Medan Helvetia, Medan Maimun</td><td><span class="kt m">Rp 15.000</span></td><td>167 kiriman</td><td><div class="acts"><button class="ab" data-open="mZona" title="Ubah"><svg class="ic"><use href="#i-edit"/></svg></button><button class="ab f js-del" data-name="Zona B" title="Hapus"><svg class="ic"><use href="#i-trash"/></svg></button></div></td></tr><tr><td><div class="who"><span class="av " style="font-size:15px">C</span><b>Zona C</b></div></td><td>6 – 10 km</td><td class="wrap" style="opacity:.75">Medan Johor, Medan Tuntungan, Medan Selayang</td><td><span class="kt">Rp 22.000</span></td><td>58 kiriman</td><td><div class="acts"><button class="ab" data-open="mZona" title="Ubah"><svg class="ic"><use href="#i-edit"/></svg></button><button class="ab f js-del" data-name="Zona C" title="Hapus"><svg class="ic"><use href="#i-trash"/></svg></button></div></td></tr></tbody></table></div>
        <div class="note" style="margin-top:16px"><svg class="ic"><use href="#i-info"/></svg><span>Alamat di luar 10 km tidak dapat memilih layanan Delivery. Pelanggan diarahkan memilih Take Away.</span></div></div>
      <div class="grid split" style="grid-template-columns:minmax(0,1fr) minmax(0,1fr);align-items:stretch">
        <div class="card"><div class="card-head" style="margin-bottom:8px"><h2>Peta Zona</h2><span class="pill">dari restoran</span></div><svg viewBox="0 0 300 300" style="width:100%;max-width:280px;display:block;margin:0 auto">
      <circle cx="150" cy="150" r="140" fill="#B2C2D2"/><circle cx="150" cy="150" r="140" fill="none" stroke="#97173A" stroke-width="1.5" stroke-dasharray="4 6"/>
      <circle cx="150" cy="150" r="96" fill="#F8F5E6" stroke="#97173A" stroke-width="1.5"/>
      <circle cx="150" cy="150" r="52" fill="#97173A"/>
      <g font-family="Poppins" font-weight="700" font-size="14" fill="#97173A"><text x="150" y="30" text-anchor="middle">C · 6–10 km</text><text x="150" y="78" text-anchor="middle">B · 3–6 km</text></g>
      <text x="150" y="128" text-anchor="middle" font-family="Poppins" font-weight="700" font-size="13" fill="#F8F5E6">A · 0–3 km</text>
      <circle cx="150" cy="156" r="13" fill="#F8F5E6"/><path d="M144 152v8M148 152v8M144 156h4M155 151c-2 2-2 5 0 7v3" stroke="#97173A" stroke-width="1.8" fill="none" stroke-linecap="round"/>
      <circle cx="214" cy="196" r="7" fill="#97173A" stroke="#F8F5E6" stroke-width="3"/></svg></div>
        <div class="card"><div class="card-head" style="margin-bottom:12px"><div><h2>Simulasi Ongkir</h2><p>Cek zona dan ongkir berdasarkan jarak alamat pelanggan</p></div></div>
          <div class="field"><label>Jarak alamat pelanggan</label><div class="in"><svg class="ic"><use href="#i-nav"/></svg><input id="km" type="number" value="4.2" step="0.1" min="0"><span class="suf">km</span></div></div>
          <input id="kmRange" type="range" min="0" max="12" step="0.1" value="4.2" style="width:100%;margin:14px 0;accent-color:#97173A">
          <div class="stamp-out"><div class="stamp" style="padding:18px 20px;display:flex;justify-content:space-between;align-items:center;gap:12px">
            <div><div class="eyebrow">Hasil</div><div id="zRes" style="font:700 22px var(--f-title);margin-top:4px">Zona B</div><div id="zSub" style="font-size:12.5px;opacity:.7">Medan Sunggal, Medan Helvetia…</div></div>
            <span class="kt m" id="zFee" style="font-size:16px">Rp 15.000</span></div></div></div>
      </div>
    </div>
  </main>
</div>

<div class="ovl" id="mZona"><div class="modal">
  <div class="mh"><div><div class="eyebrow" style="opacity:.7">Zona pengiriman</div><h3>Tambah / Ubah Zona</h3></div><button class="icon-btn" data-close><svg class="ic"><use href="#i-x"/></svg></button></div>
  <div class="mb"><div class="fgrid">
    <div class="field full"><label>Nama zona</label><div class="in"><svg class="ic"><use href="#i-map"/></svg><input type="text" value="Zona B" placeholder=""></div></div>
    <div class="field "><label>Jarak minimum</label><div class="in"><svg class="ic"><use href="#i-nav"/></svg><input type="number" value="3" placeholder=""><span class=suf>km</span></div></div><div class="field "><label>Jarak maksimum</label><div class="in"><svg class="ic"><use href="#i-nav"/></svg><input type="number" value="6" placeholder=""><span class=suf>km</span></div></div>
    <div class="field full"><label>Cakupan wilayah</label><div class="in ta"><textarea>Medan Sunggal, Medan Helvetia, Medan Maimun</textarea></div></div>
    <div class="field full"><label>Tarif ongkos kirim</label><div class="in"><span class="suf" style="opacity:1">Rp</span><input value="15.000"></div></div>
  </div></div>
  <div class="mf"><button class="btn o" data-close>Batal</button><button class="btn" data-save="Zona pengiriman disimpan"><svg class="ic"><use href="#i-check"/></svg>Simpan</button></div>
</div></div>
<div class="ovl" id="mHapus"><div class="modal sm">
  <div class="big-ic" style="background:var(--m);color:var(--cr)"><svg class="ic"><use href="#i-trash"/></svg></div>
  <h3 style="font:700 24px var(--f-title)">Hapus zona ini?</h3>
  <div class="pill p" style="margin-top:12px;font-size:13px;padding:7px 14px" id="delName"></div>
  <p style="opacity:.75;margin:12px 28px 22px">Data akan dihapus dari database dan tidak dapat dikembalikan.</p>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:0 28px 28px"><button class="btn o lg" data-close>Tidak</button><button class="btn lg" id="delOk">Ya, Hapus</button></div>
</div></div>
<div class="ovl" id="mLogout"><div class="modal sm">
  <div class="big-ic" style="background:var(--bl)"><svg class="ic"><use href="#i-logout"/></svg></div>
  <h3 style="font:700 24px var(--f-title)">Logout dari sistem?</h3>
  <p style="opacity:.75;margin:10px 28px 22px">Sesi login akan dihapus dan Anda diarahkan kembali ke halaman login.</p>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:0 28px 28px"><button class="btn o lg" data-close>Batal</button><a class="btn lg" href="#" data-logout>Ya, Logout</a></div>
</div></div>
<div class="toast" id="toast"><svg class="ic"><use href="#i-check"/></svg><span id="toastMsg">Tersimpan</span></div>

<script>
  /* buka/tutup dialog */
  document.addEventListener('click', e => {
    const o = e.target.closest('[data-open]');
    if (o) { e.preventDefault(); document.getElementById(o.dataset.open).classList.add('on'); }
    if (e.target.closest('[data-close]') || e.target.classList.contains('ovl')) {
      const ov = e.target.closest('.ovl'); if (ov) ov.classList.remove('on');
    }
  });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.ovl.on').forEach(o => o.classList.remove('on')); });
  function toast(msg) { toastMsg.textContent = msg; toast_el.classList.add('on'); clearTimeout(window._t); window._t = setTimeout(() => toast_el.classList.remove('on'), 2200); }
  const toast_el = document.getElementById('toast');
  const rp = n => 'Rp ' + Number(n).toLocaleString('id-ID');
  /* grup tombol pilihan (chips / seg) */
  document.querySelectorAll('.chips, .seg').forEach(g => g.addEventListener('click', e => {
    const b = e.target.closest('button'); if (!b || !g.contains(b)) return;
    g.querySelectorAll('button').forEach(x => x.classList.toggle('on', x === b));
  }));
  /* filter tabel: [data-filter-for] pada grup chips, [data-search-for] pada input */
  function applyFilter(tbodyId) {
    const tb = document.getElementById(tbodyId); if (!tb) return;
    const fs = [...document.querySelectorAll(`[data-filter-for="${tbodyId}"]`)].map(g => { const o = g.querySelector('.on'); return o ? o.dataset.f : 'all'; });
    const q = (document.querySelector(`[data-search-for="${tbodyId}"]`) || { value: '' }).value.toLowerCase();
    let shown = 0;
    tb.querySelectorAll('tr').forEach(tr => {
      const tags = (tr.dataset.f || '').split(' ');
      const ok = fs.every(f => f === 'all' || tags.includes(f)) && tr.textContent.toLowerCase().includes(q);
      tr.style.display = ok ? '' : 'none'; if (ok) shown++;
    });
    const c = document.querySelector(`[data-count-for="${tbodyId}"]`); if (c) c.textContent = shown;
  }
  document.querySelectorAll('[data-filter-for]').forEach(g => g.addEventListener('click', e => {
    const b = e.target.closest('button'); if (!b) return;
    g.querySelectorAll('button').forEach(x => x.classList.toggle('on', x === b));
    applyFilter(g.dataset.filterFor);
  }));
  document.querySelectorAll('[data-search-for]').forEach(i => i.addEventListener('input', () => applyFilter(i.dataset.searchFor)));
  /* hapus baris dengan konfirmasi */
  let pendingRow = null;
  document.addEventListener('click', e => {
    const d = e.target.closest('.js-del'); if (!d) return;
    pendingRow = d.closest('tr, .js-row');
    const n = document.getElementById('delName'); if (n) n.textContent = d.dataset.name || '';
    document.getElementById('mHapus').classList.add('on');
  });
  const delOk = document.getElementById('delOk');
  if (delOk) delOk.addEventListener('click', () => { if (pendingRow) pendingRow.remove(); document.getElementById('mHapus').classList.remove('on'); toast('Data berhasil dihapus'); });
  /* tombol simpan di dialog */
  document.querySelectorAll('[data-save]').forEach(b => b.addEventListener('click', () => { const ov = b.closest('.ovl'); if (ov) ov.classList.remove('on'); toast(b.dataset.save); }));

  const Z = [['A', 0, 3, 'Medan Baru, Medan Petisah, Medan Polonia', 8000], ['B', 3, 6, 'Medan Sunggal, Medan Helvetia, Medan Maimun', 15000], ['C', 6, 10, 'Medan Johor, Medan Tuntungan, Medan Selayang', 22000]];
  function sim(v) {
    const z = Z.find(r => v >= r[1] && v < r[2] || (v === r[2] && r[0] === 'C'));
    if (!z) { zRes.textContent = 'Di luar jangkauan'; zSub.textContent = 'Lebih dari 10 km · Delivery tidak tersedia'; zFee.textContent = '—'; return; }
    zRes.textContent = 'Zona ' + z[0]; zSub.textContent = z[3]; zFee.textContent = rp(z[4]);
  }
  km.addEventListener('input', () => { kmRange.value = km.value; sim(+km.value); });
  kmRange.addEventListener('input', () => { km.value = kmRange.value; sim(+kmRange.value); });
</script>
@include('partials.logout')
</body>
</html>
