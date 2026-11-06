{{-- CSS dasar halaman pelanggan (palet, header, kartu, form, footer); sama dengan halaman Keranjang. Dipakai halaman Profil & Bantuan. --}}
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
</style>
