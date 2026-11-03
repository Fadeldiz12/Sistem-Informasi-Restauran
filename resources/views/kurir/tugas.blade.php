<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tugas Pengantaran · Kurir SIM Resto</title>
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

  body { max-width: 480px; margin: 0 auto; min-height: 100vh; }
  .hdr { background: var(--m); color: var(--cr); padding: 18px 20px 26px; position: relative; }
  .hdr::after { content: ''; position: absolute; left: 0; right: 0; bottom: -12px; height: 14px;
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 14' preserveAspectRatio='none'%3E%3Cpath d='M0 0H200V6L192 9 184 5 175 10 166 6 158 9 149 5 140 10 131 6 123 9 114 5 105 10 96 6 88 9 79 5 70 10 61 6 53 9 44 5 35 10 26 6 18 9 9 5 0 9Z' fill='%23E7E0D6'/%3E%3Cpath d='M0 0H200V3L192 6 184 2 175 7 166 3 158 6 149 2 140 7 131 3 123 6 114 2 105 7 96 3 88 6 79 2 70 7 61 3 53 6 44 2 35 7 26 3 18 6 9 2 0 6Z' fill='%2397173A'/%3E%3C/svg%3E") 0 0 / 100% 100%; }
  .wrapm { padding: 26px 18px 100px; }
  .tc { display: block; width: 100%; text-align: left; padding: 16px; border-radius: 0 20px 20px 20px; position: relative; margin-top: 12px; box-shadow: inset 0 0 0 1.4px var(--m15); background: var(--cr); }
  .tc::before { content: ''; position: absolute; left: 0; top: -11px; width: 74px; height: 12px; border-radius: 12px 12px 0 0; background: inherit; box-shadow: inset 1.4px 1.4px 0 var(--m15), inset -1.4px 0 0 var(--m15); }
  .tc.new { background: var(--m); color: var(--cr); box-shadow: none; } .tc.new::before { box-shadow: none; }
  .view { display: none; } .view.on { display: block; }
  .tabbar { position: fixed; left: 50%; transform: translateX(-50%); bottom: 0; width: min(480px, 100%); display: flex; justify-content: space-around; padding: 10px 8px 18px; background: var(--cr); border-top: 1.5px solid var(--m15); z-index: 20; }
  .tabbar a { display: flex; flex-direction: column; align-items: center; gap: 3px; font: 700 11px var(--f-accent); opacity: .55; }
  .tabbar a.on { opacity: 1; }
  .map { height: 210px; border-radius: 22px; overflow: hidden; position: relative; background-color: var(--bl);
          background-image: linear-gradient(rgba(248,245,230,.6) 1px, transparent 1px), linear-gradient(90deg, rgba(248,245,230,.6) 1px, transparent 1px); background-size: 26px 26px; }
  .stepper { display: grid; grid-template-columns: repeat(4, 1fr); margin: 6px 0 4px; }
  .stepper div { display: flex; flex-direction: column; align-items: center; gap: 6px; font: 700 10.5px/1.25 var(--f-accent); text-align: center; position: relative; }
  .stepper div::before { content: ''; position: absolute; top: 13px; left: -50%; width: 100%; height: 2px; background: var(--m15); }
  .stepper div:first-child::before { display: none; } .stepper .d::before, .stepper .n::before { background: var(--m); }
  .stepper i { width: 28px; height: 28px; border-radius: 50%; background: var(--cr); box-shadow: inset 0 0 0 1.6px var(--m); display: grid; place-items: center; position: relative; z-index: 1; font-style: normal; }
  .stepper .d i { background: var(--m); color: var(--cr); box-shadow: none; } .stepper .n i { background: var(--bl); box-shadow: 0 0 0 5px rgba(178,194,210,.5), inset 0 0 0 1.6px var(--m); }
</style>
</head>
<body>
<svg width="0" height="0" style="position:absolute" aria-hidden="true"><symbol id="i-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></symbol><symbol id="i-users" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.8c2.4.6 4 2.4 4 5.2"/></symbol><symbol id="i-tag" viewBox="0 0 24 24"><path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="7.5" r="1.4"/></symbol><symbol id="i-map" viewBox="0 0 24 24"><path d="M3 6l6-3 6 3 6-3v15l-6 3-6-3-6 3z"/><path d="M9 3v15M15 6v15"/></symbol><symbol id="i-settings" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.9 4.9 7 7M17 17l2.1 2.1M2 12h3M19 12h3M4.9 19.1 7 17M17 7l2.1-2.1"/></symbol><symbol id="i-logout" viewBox="0 0 24 24"><path d="M15 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-4"/><path d="M10 17l-5-5 5-5M5 12h11"/></symbol><symbol id="i-help" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 0 1 5 0c0 1.7-2.5 2-2.5 4M12 17h.01"/></symbol><symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></symbol><symbol id="i-bell" viewBox="0 0 24 24"><path d="M6 16v-5a6 6 0 0 1 12 0v5l2 2H4z"/><path d="M10 21h4"/></symbol><symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol><symbol id="i-x" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></symbol><symbol id="i-edit" viewBox="0 0 24 24"><path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M13.5 6.5l4 4"/></symbol><symbol id="i-trash" viewBox="0 0 24 24"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/></symbol><symbol id="i-check" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7"/></symbol><symbol id="i-alert" viewBox="0 0 24 24"><path d="M12 3.5 2.5 20h19z"/><path d="M12 10v4M12 17h.01"/></symbol><symbol id="i-info" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5h.01"/></symbol><symbol id="i-receipt" viewBox="0 0 24 24"><path d="M5 3h14v18l-2.5-1.5L14 21l-2-1.5L10 21l-2.5-1.5L5 21z"/><path d="M9 8h6M9 12h6"/></symbol><symbol id="i-box" viewBox="0 0 24 24"><path d="M3 7l9-4 9 4v10l-9 4-9-4z"/><path d="M3 7l9 4 9-4M12 11v10"/></symbol><symbol id="i-truck" viewBox="0 0 24 24"><path d="M2 6h11v10H2zM13 9h4l4 4v3h-8"/><circle cx="6" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></symbol><symbol id="i-bag" viewBox="0 0 24 24"><path d="M5 8h14l-1 13H6z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></symbol><symbol id="i-dine" viewBox="0 0 24 24"><path d="M6 3v7a2 2 0 0 0 4 0V3M8 12v9"/><path d="M17 3c-2.2 2-2.2 6.5 0 8.5V21"/></symbol><symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol><symbol id="i-pin" viewBox="0 0 24 24"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></symbol><symbol id="i-upload" viewBox="0 0 24 24"><path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v4h16v-4"/></symbol><symbol id="i-mail" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></symbol><symbol id="i-phone" viewBox="0 0 24 24"><path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2z"/></symbol><symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></symbol><symbol id="i-lock" viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></symbol><symbol id="i-card" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/></symbol><symbol id="i-home" viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z"/></symbol><symbol id="i-file" viewBox="0 0 24 24"><path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6"/></symbol><symbol id="i-wallet" viewBox="0 0 24 24"><path d="M3 7h16a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H3z"/><path d="M3 7V5a2 2 0 0 1 2-2h11v4"/><circle cx="16.5" cy="13.5" r="1"/></symbol><symbol id="i-printer" viewBox="0 0 24 24"><path d="M7 9V3h10v6"/><path d="M5 9h14a2 2 0 0 1 2 2v6h-4v4H7v-4H3v-6a2 2 0 0 1 2-2z"/></symbol><symbol id="i-flame" viewBox="0 0 24 24"><path d="M12 3c1 4 5 5.5 5 10a5 5 0 0 1-10 0c0-2.5 1.5-3.5 2-5 .8 1.5 1.5 2 2.5 2C11 7.5 11 5.5 12 3z"/></symbol><symbol id="i-download" viewBox="0 0 24 24"><path d="M12 4v12M7 11l5 5 5-5"/><path d="M4 20h16"/></symbol><symbol id="i-eye" viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></symbol><symbol id="i-minus" viewBox="0 0 24 24"><path d="M5 12h14"/></symbol><symbol id="i-cal" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></symbol><symbol id="i-cash" viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.8"/></symbol><symbol id="i-chart" viewBox="0 0 24 24"><path d="M5 20V11M11 20V5M17 20v-6M2 20h20"/></symbol><symbol id="i-dots" viewBox="0 0 24 24"><circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/></symbol><symbol id="i-image" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M21 16l-5-5-9 9"/></symbol><symbol id="i-filter" viewBox="0 0 24 24"><path d="M3 5h18l-7 8v6l-4 2v-8z"/></symbol><symbol id="i-arr" viewBox="0 0 24 24"><path d="M4 12h16M14 6l6 6-6 6"/></symbol><symbol id="i-nav" viewBox="0 0 24 24"><path d="M3 11 21 3l-8 18-2-8z"/></symbol><symbol id="i-refresh" viewBox="0 0 24 24"><path d="M20 11a8 8 0 0 0-14.9-3M4 4v4h4M4 13a8 8 0 0 0 14.9 3M20 20v-4h-4"/></symbol></svg>
<header class="hdr">
  <div style="display:flex;justify-content:space-between;align-items:center"><div><div class="eyebrow" style="opacity:.75">Kurir · BK 4821 AB</div><div style="font:700 22px var(--f-title);margin-top:2px">Andi Pratama</div></div>
    <div style="display:flex;gap:8px"><button class="icon-btn" style="box-shadow:inset 0 0 0 1.5px var(--cr)"><svg class="ic"><use href="#i-bell"/></svg><span class="dot" style="background:var(--bl);color:var(--m);outline-color:var(--m)">1</span></button><span class="av" style="width:44px;height:44px;background:var(--cr)">AP</span></div></div>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:16px">
    <div style="background:rgba(248,245,230,.14);border-radius:16px;padding:10px 12px"><div class="eyebrow" style="font-size:9.5px;opacity:.8">Tugas</div><div style="font:700 22px var(--f-title)">3</div></div><div style="background:rgba(248,245,230,.14);border-radius:16px;padding:10px 12px"><div class="eyebrow" style="font-size:9.5px;opacity:.8">Diantar</div><div style="font:700 22px var(--f-title)">0</div></div><div style="background:rgba(248,245,230,.14);border-radius:16px;padding:10px 12px"><div class="eyebrow" style="font-size:9.5px;opacity:.8">Selesai</div><div style="font:700 22px var(--f-title)">9</div></div></div>
</header>

<main class="wrapm">
  <!-- 1. DAFTAR TUGAS -->
  <section class="view on" id="v-list">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:6px"><h1 style="font:700 24px var(--f-title)">Tugas Pengantaran</h1><span class="pill b">dari kasir</span></div>
    <p style="font-size:13px;opacity:.7;margin-bottom:6px">Konfirmasi tugas sebelum berangkat.</p>
    <div style="display:grid;gap:10px"><button class="tc new" data-view="detail">
      <div style="display:flex;justify-content:space-between;align-items:center"><b style="font:700 17px var(--f-title)">ORD-0143</b><span class="pill c">Tugas baru</span></div>
      <div style="font:700 14px var(--f-accent);margin-top:8px">Rina Amalia</div>
      <div style="display:flex;gap:6px;font-size:12.5px;opacity:.8;margin-top:4px;line-height:1.4;text-align:left"><svg class="ic" style="width:15px;height:15px"><use href="#i-pin"/></svg>Jl. Sei Batang Hari No. 27, Kel. Babura, Medan Sunggal</div>
      <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap"><span class="pill c"><svg class="ic" style="width:12px;height:12px"><use href="#i-nav"/></svg>4,2 km</span><span class="pill c">Ongkir Rp 6.000</span></div></button><button class="tc" data-view="detail">
      <div style="display:flex;justify-content:space-between;align-items:center"><b style="font:700 17px var(--f-title)">ORD-0146</b><span class="pill b">Menunggu konfirmasi</span></div>
      <div style="font:700 14px var(--f-accent);margin-top:8px">Sari Wulandari</div>
      <div style="display:flex;gap:6px;font-size:12.5px;opacity:.8;margin-top:4px;line-height:1.4;text-align:left"><svg class="ic" style="width:15px;height:15px"><use href="#i-pin"/></svg>Jl. Karya Wisata No. 8, Medan Johor</div>
      <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap"><span class="pill "><svg class="ic" style="width:12px;height:12px"><use href="#i-nav"/></svg>7,5 km</span><span class="pill ">Ongkir Rp 15.000</span></div></button><button class="tc" data-view="detail">
      <div style="display:flex;justify-content:space-between;align-items:center"><b style="font:700 17px var(--f-title)">ORD-0144</b><span class="pill b">Menunggu konfirmasi</span></div>
      <div style="font:700 14px var(--f-accent);margin-top:8px">Toni Ginting</div>
      <div style="display:flex;gap:6px;font-size:12.5px;opacity:.8;margin-top:4px;line-height:1.4;text-align:left"><svg class="ic" style="width:15px;height:15px"><use href="#i-pin"/></svg>Jl. Dr. Mansyur No. 12, Medan Baru</div>
      <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap"><span class="pill "><svg class="ic" style="width:12px;height:12px"><use href="#i-nav"/></svg>2,1 km</span><span class="pill ">Ongkir Gratis</span></div></button></div>
  </section>

  <!-- 2. DETAIL & KONFIRMASI -->
  <section class="view" id="v-detail">
    <button data-view="list" style="display:flex;gap:6px;align-items:center;font:700 13px var(--f-accent);margin-bottom:14px"><svg class="ic" style="width:16px;height:16px;transform:rotate(180deg)"><use href="#i-arr"/></svg>Kembali</button>
    <div class="stepper"><div class="d"><i><svg class="ic" style="width:14px;height:14px"><use href="#i-check"/></svg></i>Ditugaskan</div><div class="n"><i>2</i>Konfirmasi</div><div><i>3</i>Dalam pengantaran</div><div><i>4</i>Selesai</div></div>
    <div class="map" style="margin-top:16px">
      <svg viewBox="0 0 360 210" preserveAspectRatio="none" style="position:absolute;inset:0;width:100%;height:100%"><path d="M-10 170 C80 150 120 190 190 120 S300 70 370 50" fill="none" stroke="#F8F5E6" stroke-width="16"/><path d="M70 -10 L120 220 M250 -10 L290 220" stroke="#F8F5E6" stroke-width="10"/>
        <path d="M60 168 C130 150 150 160 190 120 S270 70 300 62" fill="none" stroke="#97173A" stroke-width="4" stroke-dasharray="2 8" stroke-linecap="round"/>
        <circle cx="60" cy="168" r="14" fill="#97173A"/><circle cx="300" cy="62" r="14" fill="#F8F5E6" stroke="#97173A" stroke-width="3"/><circle cx="300" cy="62" r="5" fill="#97173A"/></svg>
      <span class="pill m" style="position:absolute;left:12px;bottom:12px"><svg class="ic" style="width:12px;height:12px"><use href="#i-nav"/></svg>4,2 km · ± 18 menit</span><span class="pill" style="position:absolute;right:12px;top:12px;background:var(--cr)">Restoran → pelanggan</span></div>
    <div style="font:700 24px var(--f-title);margin-top:16px">ORD-0143</div>
    <div class="card" style="margin-top:12px;padding:16px"><div class="who"><span class="av">RA</span><div style="flex:1"><b style="font:600 15px var(--f-title)">Rina Amalia</b><div style="font-size:12.5px;opacity:.7">0812 6543 2109</div></div><button class="icon-btn" style="width:40px;height:40px"><svg class="ic" style="width:16px;height:16px"><use href="#i-phone"/></svg></button></div>
      <div style="display:flex;gap:8px;font-size:13.5px;margin-top:12px;padding-top:12px;border-top:1.5px dashed var(--m60);line-height:1.45"><svg class="ic" style="width:17px;height:17px"><use href="#i-pin"/></svg>Jl. Sei Batang Hari No. 27, Kel. Babura, Medan Sunggal</div></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px">
      <div class="card b" style="padding:14px"><div class="eyebrow" style="font-size:9.5px">Ongkos kirim</div><div style="font:700 20px var(--f-title);margin-top:2px">Rp 6.000</div><div style="font-size:11.5px;opacity:.75">2 km × Rp 3.000</div></div>
      <div class="card f" style="padding:14px"><div class="eyebrow" style="font-size:9.5px">Pembayaran</div><div style="font:700 20px var(--f-title);margin-top:2px">Lunas</div><div style="font-size:11.5px;opacity:.8">Transfer terverifikasi</div></div></div>
    <div class="card soft" style="margin-top:10px;padding:14px 16px"><div class="eyebrow" style="font-size:9.5px;margin-bottom:6px">Isi pesanan</div><div style="display:flex;gap:10px;font-size:14px;padding:3px 0"><b style="font-family:var(--f-title);min-width:22px">2×</b>Pizza Beef Pepperoni</div><div style="display:flex;gap:10px;font-size:14px;padding:3px 0"><b style="font-family:var(--f-title);min-width:22px">1×</b>Penne Bolognese</div></div>
    <button class="btn lg" style="width:100%;margin-top:16px" data-view="bukti"><svg class="ic"><use href="#i-check"/></svg>Konfirmasi Tugas &amp; Berangkat</button>
  </section>

  <!-- 3. BUKTI PENERIMAAN -->
  <section class="view" id="v-bukti">
    <button data-view="detail" style="display:flex;gap:6px;align-items:center;font:700 13px var(--f-accent);margin-bottom:14px"><svg class="ic" style="width:16px;height:16px;transform:rotate(180deg)"><use href="#i-arr"/></svg>Kembali</button>
    <div class="stepper"><div class="d"><i><svg class="ic" style="width:14px;height:14px"><use href="#i-check"/></svg></i>Ditugaskan</div><div class="d"><i><svg class="ic" style="width:14px;height:14px"><use href="#i-check"/></svg></i>Konfirmasi</div><div class="n"><i><svg class="ic" style="width:13px;height:13px"><use href="#i-truck"/></svg></i>Dalam pengantaran</div><div><i>4</i>Selesai</div></div>
    <div class="stamp-out" style="margin-top:18px"><div class="stamp" style="padding:20px 22px"><div style="display:flex;justify-content:space-between;align-items:center"><div><div class="eyebrow">ORD-0143</div><div style="font:700 21px var(--f-title);margin-top:2px">Dalam Pengantaran</div></div><span class="pill m">11:14</span></div>
      <div style="font-size:13px;opacity:.75;margin-top:6px">Rina Amalia · Jl. Sei Batang Hari No. 27, Kel. Babura, Medan Sunggal</div></div></div>
    <div class="field" style="margin-top:20px"><label>Foto bukti penerimaan</label>
      <div class="drop" style="flex-direction:column;text-align:center;padding:22px"><svg class="ic" style="width:30px;height:30px"><use href="#i-image"/></svg><b style="font:600 14px var(--f-title)">Ambil foto / unggah bukti</b><span style="font-size:12px;opacity:.7">Foto pesanan diterima pelanggan · JPG/PNG maks 2 MB</span></div>
      <div style="display:flex;gap:12px;align-items:center;margin-top:10px;padding:10px;border-radius:16px;background:var(--pp)"><div class="stripes" style="width:56px;height:56px;border-radius:12px;display:grid;place-items:center"><svg class="ic" style="width:22px;height:22px"><use href="#i-image"/></svg></div>
        <div style="flex:1"><b style="font:600 13.5px var(--f-title)">bukti_0143.jpg</b><div style="font-size:12px;opacity:.7">1,6 MB · diambil 11:31</div></div><svg class="ic"><use href="#i-x"/></svg></div></div>
    <div class="field" style="margin-top:14px"><label>Diterima oleh</label><div class="in"><svg class="ic"><use href="#i-user"/></svg><input value="Rina Amalia (pemesan)"></div></div>
    <button class="btn lg" style="width:100%;margin-top:18px" onclick="toast('Pengantaran selesai · pelanggan diberi notifikasi')"><svg class="ic"><use href="#i-check"/></svg>Tandai Pesanan Selesai</button>
    <p style="font-size:12px;opacity:.6;text-align:center;margin-top:10px">Status pesanan menjadi Selesai dan pelanggan menerima notifikasi.</p>
  </section>
</main>

<nav class="tabbar"><a class="on" href="#"><svg class="ic" style="width:21px;height:21px"><use href="#i-receipt"/></svg>Tugas</a><a href="#"><svg class="ic" style="width:21px;height:21px"><use href="#i-truck"/></svg>Diantar</a><a href="#"><svg class="ic" style="width:21px;height:21px"><use href="#i-check"/></svg>Selesai</a><a href="{{ route('login') }}"><svg class="ic" style="width:21px;height:21px"><use href="#i-logout"/></svg>Keluar</a></nav>
<div class="toast" id="toast"><svg class="ic"><use href="#i-check"/></svg><span id="toastMsg"></span></div>
<script>
  const toast_el = document.getElementById('toast');
  function toast(m) { toastMsg.textContent = m; toast_el.classList.add('on'); clearTimeout(window._t); window._t = setTimeout(() => toast_el.classList.remove('on'), 2200); }
  function go(v) { document.querySelectorAll('.view').forEach(x => x.classList.toggle('on', x.id === 'v-' + v)); window.scrollTo(0, 0); }
  document.addEventListener('click', e => { const b = e.target.closest('[data-view]'); if (b) { go(b.dataset.view); if (b.dataset.view === 'bukti') toast('Tugas dikonfirmasi · status dalam pengantaran'); } });
  if (location.hash) go(location.hash.slice(1));
</script>
</body>
</html>