<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title') · {{ config('resto.nama') }}</title>
@include('partials.favicon')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Raleway:wght@600;700;800&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  /* =========================================================
     Halaman masuk / daftar / OTP. Ukuran mengikuti desain 1600 × 1280:
     --k = 1 px desain, diskalakan agar seluruh desain muat di layar.
     ========================================================= */
  :root {
    --m: #97173A; --cr: #F8F5E6; --bl: #B2C2D2; --pp: #E7E0D6; --dk: #282828;
    --f-title: 'Poppins', sans-serif; --f-accent: 'Raleway', sans-serif; --f-body: 'Open Sans', sans-serif;
    --k: min(calc(100vw / 1600), calc(100vh / 1280));
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  html, body { height: 100%; }
  body { background: var(--m); color: var(--m); font: 400 15px/1.5 var(--f-body); -webkit-font-smoothing: antialiased; overflow: hidden; }
  button, input, textarea { font: inherit; color: inherit; }
  a { color: inherit; }

  .stage { position: relative; height: 100vh; min-height: 560px; overflow: hidden; }

  /* ---------- sisi kiri: strip foto, merek, piring ---------- */
  /* panel kiri: merah polos dengan satu foto utama + dua foto kecil, semua rata tengah di area merah */
  .strip { display: none; }
  .brand { position: absolute; z-index: 3; top: calc(var(--k) * 64); left: calc(var(--k) * 90); width: calc(var(--k) * 640);
           text-align: center; color: var(--cr); }
  .brand b { display: block; font: 800 calc(var(--k) * 44) / 1.1 var(--f-accent); letter-spacing: .2em; padding-left: .2em; white-space: nowrap; }
  .brand small { display: block; margin-top: calc(var(--k) * 12); font: 600 calc(var(--k) * 15) var(--f-accent); letter-spacing: .3em; padding-left: .3em; }
  .brand i { display: block; width: calc(var(--k) * 60); height: max(2px, calc(var(--k) * 3)); background: var(--cr); opacity: .85; margin: calc(var(--k) * 18) auto 0; }
  .tagline { position: absolute; z-index: 3; bottom: calc(var(--k) * 56); left: calc(var(--k) * 90); width: calc(var(--k) * 640); text-align: center;
             color: var(--cr); font: 600 calc(var(--k) * 14.5) var(--f-accent); letter-spacing: .22em; padding-left: .22em; opacity: .85; white-space: nowrap; }
  .layanan-chip { position: absolute; z-index: 3; bottom: calc(var(--k) * 104); left: calc(var(--k) * 90); width: calc(var(--k) * 640);
                  display: flex; justify-content: center; gap: calc(var(--k) * 12); }
  .layanan-chip span { padding: calc(var(--k) * 8) calc(var(--k) * 18); border-radius: 999px; box-shadow: inset 0 0 0 1.5px rgba(248, 245, 230, .55);
                       color: var(--cr); font: 700 calc(var(--k) * 15) var(--f-accent); letter-spacing: .04em; white-space: nowrap; }

  .plate { position: absolute; z-index: 4; border-radius: 50%; background: #6d0e2a; overflow: hidden;
           left: calc(var(--k) * var(--x)); top: calc(50% + var(--k) * (var(--y) - 640)); width: calc(var(--k) * var(--d)); height: calc(var(--k) * var(--d));
           transform: translate(-50%, -50%); box-shadow: 0 calc(var(--k) * 18) calc(var(--k) * 40) rgba(40, 0, 12, .35); }
  .plate img { display: block; width: 100%; height: 100%; object-fit: cover; }
  .p1 { --x: 410; --y: 610; --d: 470; border: calc(var(--k) * 16) solid var(--cr); }
  .p2 { --x: 640; --y: 820; --d: 230; border: calc(var(--k) * 10) solid var(--m);
        box-shadow: 0 0 0 calc(var(--k) * 4) var(--cr), 0 calc(var(--k) * 18) calc(var(--k) * 34) rgba(40, 0, 12, .35); }
  .p3 { --x: 180; --y: 840; --d: 190; border: calc(var(--k) * 10) solid var(--cr); }

  /* ---------- sisi kanan: krem dengan tepi diagonal ---------- */
  .cream { position: absolute; inset: 0; z-index: 2; background: var(--cr);
           clip-path: polygon(calc(var(--k) * 940) 0, 100% 0, 100% 100%, calc(var(--k) * 730) 100%); }

  .doodle { position: absolute; z-index: 3; fill: none; stroke-linecap: round; stroke-linejoin: round; pointer-events: none;
            left: calc(var(--k) * var(--x)); top: calc(50% + var(--k) * (var(--y) - 640)); width: calc(var(--k) * var(--w)); }
  .doodle.pk { stroke: rgba(151, 23, 58, .55); stroke-width: 1.6; }
  .doodle.bl { stroke: var(--bl); stroke-width: 2.6; }
  .doodle.lt { stroke: rgba(248, 245, 230, .4); stroke-width: 1.6; }
  .fwrap { position: relative; display: flex; flex-direction: column; align-items: center; }
  .dd { position: absolute; fill: none; stroke-linecap: round; stroke-linejoin: round; pointer-events: none; }
  .dd.pk { stroke: rgba(151, 23, 58, .55); stroke-width: 1.6; }
  .dd.bl { stroke: var(--bl); stroke-width: 2.6; }
  .fwrap:has(.form.wide) .dd.side { right: calc(var(--k) * -34) !important; }

  .right { position: absolute; z-index: 5; top: 0; bottom: 0; right: 0; left: calc(var(--k) * 900);
           display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4vh 3vw 4vh 2vw; overflow-y: auto; }
  .right > * { flex: none; }
  .form { width: clamp(360px, calc(var(--k) * 500), 520px); display: flex; flex-direction: column; }
  .form.wide { width: clamp(530px, calc(var(--k) * 588), 620px); }
  .form h1 { font: 800 clamp(26px, calc(var(--k) * 40), 46px) / 1.18 var(--f-title); text-transform: uppercase; text-align: center; letter-spacing: .01em; }
  .lead { text-align: center; font: 400 clamp(13.5px, calc(var(--k) * 16.5), 17px) / 1.5 var(--f-body); margin-top: clamp(4px, calc(var(--k) * 8), 10px); opacity: .9; }
  .lead b { font-weight: 600; }
  .gap { height: clamp(20px, calc(var(--k) * 44), 46px); flex: none; }
  .gap.s { height: clamp(14px, calc(var(--k) * 22), 26px); }

  .field { display: flex; align-items: center; gap: 14px; min-height: clamp(46px, calc(var(--k) * 64), 64px); padding: 0 clamp(18px, calc(var(--k) * 26), 28px);
           border-radius: 999px; background: var(--pp); border: 1.5px solid transparent; cursor: text; transition: .15s;
           font: 400 clamp(14px, calc(var(--k) * 17), 17px) var(--f-body); }
  .field:focus-within { background: var(--cr); border-color: var(--m); box-shadow: 0 0 0 4px rgba(178, 194, 210, .7); }
  .field input, .field textarea { all: unset; flex: 1; width: 100%; min-width: 0; color: var(--m); font: inherit; }
  .field input::placeholder, .field textarea::placeholder { color: rgba(151, 23, 58, .62); }
  .field > svg { width: clamp(18px, calc(var(--k) * 22), 22px); height: clamp(18px, calc(var(--k) * 22), 22px); flex: none; }
  .field.ta { align-items: flex-start; border-radius: clamp(22px, calc(var(--k) * 32), 32px); padding-top: clamp(12px, calc(var(--k) * 18), 18px); padding-bottom: 12px; min-height: clamp(72px, calc(var(--k) * 92), 92px); }
  .field.ta textarea { min-height: clamp(40px, calc(var(--k) * 52), 54px); line-height: 1.45; }
  .field .eye { background: none; border: 0; cursor: pointer; display: grid; place-items: center; opacity: .75; flex: none; padding: 4px; }
  .field .eye:hover { opacity: 1; }
  .field .eye svg { width: 20px; height: 20px; }
  .stack { display: grid; grid-template-columns: minmax(0, 1fr); gap: clamp(12px, calc(var(--k) * 18), 20px); }
  .row2 { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: clamp(10px, calc(var(--k) * 16), 16px); }

  .forgot { align-self: flex-end; margin-top: clamp(12px, calc(var(--k) * 24), 26px); font: 700 clamp(13px, calc(var(--k) * 15.5), 16px) var(--f-accent); text-decoration: none; }
  .forgot:hover { text-decoration: underline; }
  .btn { align-self: center; min-width: clamp(200px, calc(var(--k) * 250), 260px); min-height: clamp(48px, calc(var(--k) * 62), 62px); padding: 0 32px;
         border: 0; border-radius: 999px; background: var(--m); color: var(--cr); cursor: pointer; transition: .15s;
         font: 700 clamp(15px, calc(var(--k) * 18), 18px) var(--f-title); text-transform: uppercase; letter-spacing: .04em; }
  .btn:hover { background: #7f1331; }
  .btn:disabled { opacity: .6; cursor: wait; }
  .foot { text-align: center; margin-top: clamp(20px, calc(var(--k) * 40), 42px); font: 400 clamp(14px, calc(var(--k) * 17), 17px) var(--f-body); }
  .foot a, .foot button { font: 800 1em var(--f-accent); text-decoration: underline; text-underline-offset: 3px; background: none; border: 0; cursor: pointer; color: var(--m); }
  .foot button:disabled { opacity: .5; cursor: default; text-decoration: none; }
  /* target sentuh minimal ±44px di HP (QA A1-#17) */
  .foot a, .foot button, .forgot { display: inline-flex; align-items: center; min-height: 44px; padding: 0 8px; }
  .field .eye { min-width: 44px; min-height: 44px; }
  .agree { min-height: 44px; }
  .agree input { width: 22px; height: 22px; }
  .btn.sekunder { background: transparent; color: var(--m); box-shadow: inset 0 0 0 1.8px var(--m); margin-top: 10px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
  .agree { display: flex; gap: 10px; align-items: center; font: 400 clamp(13.5px, calc(var(--k) * 16), 16px) var(--f-body); cursor: pointer; margin-top: clamp(12px, calc(var(--k) * 22), 24px); }
  .agree input { width: 20px; height: 20px; accent-color: var(--m); flex: none; }
  .login-opsi { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-top: clamp(12px, calc(var(--k) * 24), 26px); }

  .alert { margin-bottom: 16px; padding: 12px 18px; border-radius: 16px; background: var(--m); color: var(--cr); font: 600 13.5px/1.45 var(--f-body); }
  .alert ul { padding-left: 18px; }
  .alert.ok { background: var(--bl); color: var(--m); }
  .alert.dev { background: var(--pp); color: var(--m); border: 1.5px dashed var(--m); }
  .alert.dev b { font: 700 18px var(--f-title); letter-spacing: .2em; }

  /* ---------- kotak kode OTP ---------- */
  .otp { display: grid; grid-template-columns: repeat(6, 1fr); gap: clamp(8px, calc(var(--k) * 14), 14px); }
  .otp input { width: 100%; aspect-ratio: 1 / 1.12; max-height: 76px; border: 1.5px solid transparent; border-radius: clamp(14px, calc(var(--k) * 20), 20px); background: var(--pp);
               text-align: center; font: 700 clamp(22px, calc(var(--k) * 32), 32px) var(--f-title); color: var(--m); outline: none; caret-color: var(--m); transition: .15s; }
  .otp input:focus { background: var(--cr); border-color: var(--m); box-shadow: 0 0 0 4px rgba(178, 194, 210, .7); }
  .otp input.isi { background: var(--cr); border-color: rgba(151, 23, 58, .4); }
  .hint { text-align: center; font-size: 13px; opacity: .75; margin-top: 12px; }
  .field:has([aria-invalid="true"]) { box-shadow: inset 0 0 0 2px var(--m); }

  /* laptop kecil: geser form sedikit menjauhi tepi merah */
  @media (max-width: 1150px) { .right { padding-left: 6vw; padding-right: 1.5vw; } .form.wide { width: 470px; } .form.wide .field { padding: 0 16px; gap: 10px; } }

  /* ---------- tablet tegak & HP ---------- */
  @media (max-width: 900px), (max-aspect-ratio: 1/1) {
    body { overflow: auto; }
    .stage { height: auto; min-height: 100vh; display: flex; flex-direction: column; overflow: visible; }
    .strip { display: block; position: relative; left: 0; width: 100%; height: 200px; flex: none; background: linear-gradient(160deg, #a51b43, #7d1131); }
    .brand { top: 42px; left: 0; width: 100%; }
    .brand b { font-size: 30px; } .brand small { font-size: 11.5px; margin-top: 8px; } .brand i { width: 48px; margin-top: 14px; }
    .tagline, .plate, .doodle, .dd, .layanan-chip { display: none; }
    .fwrap { width: 100%; }
    .cream { display: none; }
    .right { position: relative; inset: auto; flex: 1; z-index: 5; margin-top: -34px; padding: 62px 22px 44px; overflow: visible;
             background: var(--cr); clip-path: polygon(0 34px, 100% 0, 100% 100%, 0 100%); justify-content: flex-start; }
    .form, .form.wide { width: 100%; max-width: 440px; }
    .row2 { grid-template-columns: minmax(0, 1fr); }
  }
  @media (max-width: 380px) { .otp { gap: 6px; } }
</style>
@stack('styles')
</head>
<body>
<main class="stage">
  <div class="strip" aria-hidden="true"></div>
  <div class="brand"><b>{{ strtoupper(config('resto.nama')) }}</b><small>PIZZA &amp; PASTA HOUSE</small><i></i></div>
  <div class="layanan-chip" aria-hidden="true"><span>Delivery</span><span>Take Away</span><span>Dine In</span></div>
  <div class="tagline" aria-hidden="true">PESAN · LACAK · NIKMATI</div>

  <div class="plate p1" aria-hidden="true"><img src="https://images.unsplash.com/photo-1595854341625-f33ee10dbf94?w=500&q=75" alt=""></div>
  <div class="plate p2" aria-hidden="true"><img src="https://images.unsplash.com/photo-1593560708920-61dd98c46a4e?w=500&q=75" alt=""></div>
  <div class="plate p3" aria-hidden="true"><img src="https://images.unsplash.com/photo-1574071318508-1cdbab80d002?w=400&q=75" alt=""></div>

  <div class="cream" aria-hidden="true"></div>

  {{-- coretan dekorasi (posisi dalam px desain 1600 × 1280) --}}
  <svg class="doodle lt" style="--x:70;--y:252;--w:120" viewBox="0 0 120 60"><path d="M2 4H118L66 56Z"/><circle cx="34" cy="16" r="5"/><circle cx="80" cy="26" r="5"/></svg>
  <svg class="doodle lt" style="--x:76;--y:318;--w:22" viewBox="0 0 22 30"><path d="M14 2L4 13l12 4L6 28"/></svg>
  <svg class="doodle lt" style="--x:108;--y:312;--w:20" viewBox="0 0 20 14"><path d="M2 2l8 9 8-4"/></svg>
  <svg class="doodle lt" style="--x:52;--y:636;--w:38" viewBox="0 0 38 64"><path d="M22 2C6 14 0 36 10 60 30 50 38 26 22 2Z"/><path d="M10 60C14 40 18 22 22 2"/></svg>

  <section class="right">
    <div class="fwrap">
      {{-- hiasan di sekitar form: diposisikan dari tepi kotak form agar tidak pernah menimpa isi --}}
      <svg class="dd pk" style="right:calc(var(--k) * -44);top:calc(var(--k) * -122);width:calc(var(--k) * 56)" viewBox="0 0 56 58"><circle cx="28" cy="32" r="23"/><path d="M28 9c-3-5-8-6-13-4M28 9c4-4 9-5 13-2M28 9V2"/></svg>
      <svg class="dd bl" style="left:calc(var(--k) * 28);top:calc(var(--k) * -84);width:calc(var(--k) * 18)" viewBox="0 0 18 30"><path d="M4 3l10 12L4 27"/></svg>
      <svg class="dd pk side" style="right:calc(var(--k) * -74);top:48%;width:calc(var(--k) * 30)" viewBox="0 0 30 46"><path d="M8 2C-2 18 2 34 22 44 30 28 26 12 8 2Z"/><path d="M8 2c6 14 10 28 14 42"/></svg>
      <svg class="dd bl side" style="right:calc(var(--k) * -66);top:76%;width:calc(var(--k) * 18)" viewBox="0 0 18 30"><path d="M4 3l10 12L4 27"/></svg>
      <svg class="dd pk" style="right:calc(var(--k) * -30);bottom:calc(var(--k) * -118);width:calc(var(--k) * 92)" viewBox="0 0 92 76"><path d="M4 4L88 34 30 72Z"/><circle cx="36" cy="38" r="5"/><circle cx="54" cy="48" r="4"/></svg>
      <svg class="dd bl" style="left:calc(var(--k) * 88);bottom:calc(var(--k) * -110);width:calc(var(--k) * 18)" viewBox="0 0 18 30"><path d="M4 3l10 12L4 27"/></svg>
      @yield('form')
    </div>
  </section>
</main>
<script>
  // tampilkan / sembunyikan password
  document.querySelectorAll('.field .eye').forEach(function (b) {
    b.addEventListener('click', function () {
      var i = b.parentNode.querySelector('input'); var lihat = i.type === 'password';
      i.type = lihat ? 'text' : 'password'; b.setAttribute('aria-label', lihat ? 'Sembunyikan password' : 'Tampilkan password');
      b.querySelector('.o').style.display = lihat ? 'none' : ''; b.querySelector('.c').style.display = lihat ? '' : 'none';
    });
  });
  // cegah kirim ganda
  // (dinilai setelah script halaman: bila submit dibatalkan, misal kode OTP belum lengkap, tombol tetap aktif)
  document.querySelectorAll('form').forEach(function (f) { f.addEventListener('submit', function (e) { var b = f.querySelector('.btn'); if (b) setTimeout(function () { if (!e.defaultPrevented) b.disabled = true; }, 0); }); });
</script>
@stack('scripts')
</body>
</html>
