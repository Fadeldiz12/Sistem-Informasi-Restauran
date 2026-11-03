<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk · SIM Resto</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Raleway:wght@600;700;800&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  /* =========================================================
     PALET (wajib) & FONT
     Poppins  : judul + tombol utama
     Raleway  : logo, tautan, label kecil, tombol sosial
     Open Sans: teks isi & input
     ========================================================= */
  :root {
    --m:  #97173A;  /* maroon    */
    --cr: #F8F5E6;  /* krem      */
    --bl: #B2C2D2;  /* biru abu  */
    --pp: #E7E0D6;  /* kertas    */
    --dk: #282828;  /* abu gelap */

    /* Skala otomatis: mengikuti sisi layar yang paling sempit
       (desain dasar 1440 × 810), jadi pas di semua ukuran laptop */
    --k: min(calc(100vw / 1440), calc(100vh / 810));
    --f-title: 'Poppins', sans-serif;
    --f-accent: 'Raleway', sans-serif;
    --f-body: 'Open Sans', sans-serif;

    /* bentuk area maroon (dipakai sebagai background & mask ornamen) */
    --shape: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 810' preserveAspectRatio='none'%3E%3Cpath d='M0 0H1440V44C1160 58 905 142 765 268C628 392 530 600 478 810H0Z'/%3E%3C/svg%3E");
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  html, body { height: 100%; background: var(--cr); }
  body { font-family: var(--f-body); color: var(--m); -webkit-font-smoothing: antialiased; }

  .stage { position: relative; width: 100%; height: 100vh; min-height: 600px; overflow: hidden; background: var(--cr); }

  /* area maroon: melar mengikuti lebar & tinggi layar */
  .bg { position: absolute; inset: 0; background: var(--m);
        -webkit-mask: var(--shape) 0 0 / 100% 100% no-repeat; mask: var(--shape) 0 0 / 100% 100% no-repeat; }

  /* ornamen line-art: 2 lapis, krem di area maroon & maroon di area krem */
  .art { position: absolute; inset: 0; pointer-events: none; }
  .art.on-maroon { color: var(--cr); opacity: .28;
        -webkit-mask: var(--shape) 0 0 / 100% 100% no-repeat; mask: var(--shape) 0 0 / 100% 100% no-repeat; }
  .art.on-cream { color: var(--m); opacity: .5;
        -webkit-mask: var(--shape) 0 0 / 100% 100% no-repeat, linear-gradient(#000 0 0);
        -webkit-mask-composite: xor; mask: var(--shape) 0 0 / 100% 100% no-repeat exclude, linear-gradient(#000 0 0); }
  .art svg { position: absolute; fill: none; stroke: currentColor; stroke-width: 3.2; stroke-linecap: round; stroke-linejoin: round; overflow: visible; }
  .d-pizza { width: calc(var(--k) * 400); left: calc(41.7vw - var(--k) * 200); bottom: calc(var(--k) * -190); }
  .d-slice { width: calc(var(--k) * 170); left: 3vw; top: 14vh; }
  .d-tomato { width: calc(var(--k) * 190); left: 2.5vw; top: 58vh; }
  .d-leaf { width: calc(var(--k) * 160); right: 6vw; top: 6vh; }
  .d-slice2 { width: calc(var(--k) * 150); right: 6vw; bottom: -2vh; transform: rotate(28deg); }

  /* coretan petir */
  .zap { position: absolute; width: calc(var(--k) * 46); fill: none; stroke-width: 4; stroke-linecap: round; stroke-linejoin: round; }

  /* kolom visual (foto pizza) */
  .visual { position: absolute; left: 0; top: 0; bottom: 0; width: calc(var(--k) * 520); }
  .strip { position: absolute; top: 0; bottom: 0; left: calc(var(--k) * 174); width: calc(var(--k) * 300);
           background: url('https://images.unsplash.com/photo-1590947132387-155cc02f3212?w=900&q=80') center / cover; }
  .strip::after { content: ''; position: absolute; inset: 0; background: var(--m); mix-blend-mode: multiply; opacity: .86; }
  .logo { position: absolute; z-index: 5; top: calc(var(--k) * 24); left: calc(var(--k) * 174); width: calc(var(--k) * 300);
          display: flex; flex-direction: column; align-items: center; color: var(--cr); text-align: center; }
  .logo b { font: 800 calc(var(--k) * 28) var(--f-accent); letter-spacing: .08em; line-height: 1; }
  .logo small { font: 600 calc(var(--k) * 10) var(--f-accent); letter-spacing: .3em; text-transform: uppercase; margin-top: calc(var(--k) * 6); opacity: .85; }

  /* kumpulan piring, selalu di tengah vertikal */
  .plates { position: absolute; left: 0; width: calc(var(--k) * 520); height: calc(var(--k) * 810); top: 50%; transform: translateY(-50%); }
  .plate { position: absolute; z-index: 4; border-radius: 50%; overflow: hidden; }
  .plate img { width: 100%; height: 100%; object-fit: cover; display: block; }
  .p1 { left: calc(var(--k) * 66);  top: calc(var(--k) * 96);  width: calc(var(--k) * 400); height: calc(var(--k) * 400); border: calc(var(--k) * 12) solid var(--cr); }
  .p1 img { object-position: 46% 52%; transform: scale(1.42); }
  .p2 { left: calc(var(--k) * 252); top: calc(var(--k) * 414); width: calc(var(--k) * 236); height: calc(var(--k) * 236);
        border: calc(var(--k) * 9) solid var(--m); box-shadow: 0 0 0 calc(var(--k) * 4) var(--cr); }
  .p2 img { object-position: 50% 52%; transform: scale(1.18); }
  .p3 { left: calc(var(--k) * 142); top: calc(var(--k) * 548); width: calc(var(--k) * 190); height: calc(var(--k) * 190);
        border: calc(var(--k) * 18) solid var(--cr); box-shadow: 0 0 0 calc(var(--k) * 2) var(--bl); }
  .p3 img { object-position: 48% 50%; transform: scale(1.3); }

  /* kolom form: mengisi sisa lebar layar, form di tengah */
  .right { position: absolute; z-index: 6; top: 0; bottom: 0; right: 0; left: 56vw;
           display: flex; align-items: center; justify-content: center; padding: 14vh 4vw 6vh; }
  .form { width: clamp(320px, calc(var(--k) * 400), 480px); display: flex; flex-direction: column; }
  .form h1 { font: 700 clamp(26px, calc(var(--k) * 36), 46px) / 1.15 var(--f-title); text-transform: uppercase;
             text-align: center; letter-spacing: .01em; margin-bottom: clamp(24px, calc(var(--k) * 34), 44px); }
  .field { height: clamp(46px, calc(var(--k) * 52), 60px); border-radius: 999px; background: var(--pp);
           display: flex; align-items: center; gap: 12px; padding: 0 22px; cursor: text;
           font: 500 clamp(14px, calc(var(--k) * 14.5), 17px) var(--f-body); border: 1.5px solid transparent; }
  .field + .field { margin-top: clamp(14px, calc(var(--k) * 18), 22px); }
  .field:focus-within { border-color: var(--m); background: var(--cr); box-shadow: 0 0 0 4px var(--bl); }
  .field input { all: unset; flex: 1; min-width: 0; color: var(--m); font: inherit; }
  .field input::placeholder { color: rgba(151, 23, 58, .55); }
  .field svg { width: 18px; height: 18px; flex: none; opacity: .75; }
  .forgot { align-self: flex-end; margin-top: 12px; font: 700 clamp(12.5px, calc(var(--k) * 13), 15px) var(--f-accent); color: var(--m); text-decoration: none; }
  .forgot:hover { text-decoration: underline; }
  .btn { align-self: center; margin-top: clamp(16px, calc(var(--k) * 20), 26px); min-width: 46%;
         height: clamp(44px, calc(var(--k) * 48), 56px); padding: 0 32px; border: 0; border-radius: 999px;
         background: var(--m); color: var(--cr); font: 600 clamp(14px, calc(var(--k) * 15), 17px) var(--f-title); cursor: pointer; }
  .btn:hover { background: #7f1331; }
  .divider { display: flex; align-items: center; gap: 14px; margin: clamp(20px, calc(var(--k) * 26), 32px) 0;
             font: 700 clamp(12px, calc(var(--k) * 12.5), 14px) var(--f-accent); letter-spacing: .08em; }
  .divider::before, .divider::after { content: ''; flex: 1; height: 1.5px; background: var(--m); opacity: .35; }
  .social { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
  .social a { height: clamp(44px, calc(var(--k) * 48), 56px); border-radius: 999px; box-shadow: inset 0 0 0 1.5px var(--m);
              display: flex; align-items: center; justify-content: center; gap: 10px;
              font: 700 clamp(13.5px, calc(var(--k) * 14), 16px) var(--f-accent); color: var(--m); text-decoration: none; }
  .social a:hover { background: var(--bl); box-shadow: none; }
  .social svg { width: 20px; height: 20px; }
  .signup { text-align: center; margin-top: clamp(22px, calc(var(--k) * 30), 38px); font: 400 clamp(13.5px, calc(var(--k) * 14), 16px) var(--f-body); }
  .signup a { color: var(--m); font: 800 1em var(--f-accent); }

  /* tablet & HP: susun ke bawah */
  @media (max-width: 900px), (max-aspect-ratio: 1/1) {
    :root { --k: 1px; }
    .stage { height: auto; min-height: 100vh; }
    .bg, .art, .zap, .p2, .p3 { display: none; }
    .visual { position: relative; width: 100%; height: 280px; }
    .strip { left: 0; width: 100%; }
    .logo { left: 0; width: 100%; }
    .plates { width: 100%; height: 280px; top: 0; transform: none; }
    .p1 { left: 50%; top: 84px; width: 230px; height: 230px; margin-left: -115px; border-width: 8px; }
    .right { position: relative; left: 0; padding: 90px 24px 40px; }
    .form { width: 100%; max-width: 420px; }
  }
</style>
</head>
<body>
<main class="stage">
  <div class="bg" aria-hidden="true"></div>

  <!-- simbol ornamen (digambar sekali, dipakai 2 lapis) -->
  <svg width="0" height="0" style="position:absolute" aria-hidden="true">
    <symbol id="s-pizza" viewBox="-200 -200 400 400">
      <circle r="190"/><circle r="160"/><path d="M0-160V160M-160 0H160M-113-113 113 113M113-113-113 113"/>
      <circle cx="-62" cy="-92" r="20"/><circle cx="70" cy="-60" r="20"/><circle cx="-90" cy="40" r="20"/>
      <circle cx="40" cy="80" r="20"/><circle cx="108" cy="28" r="16"/><circle cx="-24" cy="-26" r="14"/>
      <path d="M-30 110c18-22 44-22 56 0-18 18-40 18-56 0zM60-118c18-20 42-18 52 4-20 14-40 12-52-4z"/>
    </symbol>
    <symbol id="s-slice" viewBox="-10 -50 175 180">
      <g transform="rotate(-18 70 40)"><path d="M0 0L150-30Q160 40 120 120Z"/><path d="M6 6L146-22"/>
      <circle cx="70" cy="20" r="11"/><circle cx="110" cy="50" r="10"/><circle cx="95" cy="-2" r="8"/></g>
    </symbol>
    <symbol id="s-tomato" viewBox="0 0 200 150">
      <circle cx="40" cy="40" r="34"/><path d="M28 10l12 10 12-10M40 20V6"/>
      <path d="M80 110c30-50 90-50 110 0-40 30-80 30-110 0zM88 110h98"/>
    </symbol>
    <symbol id="s-leaf" viewBox="-5 -50 160 90"><path d="M0 0c40-60 120-60 150 0-50 40-110 40-150 0zM8 0h138"/></symbol>
  </svg>

  <div class="art on-maroon" aria-hidden="true">
    <svg class="d-pizza" viewBox="0 0 400 400"><use href="#s-pizza"/></svg>
    <svg class="d-slice" viewBox="0 0 175 180"><use href="#s-slice"/></svg>
    <svg class="d-tomato" viewBox="0 0 200 150"><use href="#s-tomato"/></svg>
    <svg class="d-leaf" viewBox="0 0 160 90"><use href="#s-leaf"/></svg>
    <svg class="d-slice2" viewBox="0 0 175 180"><use href="#s-slice"/></svg>
  </div>
  <div class="art on-cream" aria-hidden="true">
    <svg class="d-pizza" viewBox="0 0 400 400"><use href="#s-pizza"/></svg>
    <svg class="d-slice" viewBox="0 0 175 180"><use href="#s-slice"/></svg>
    <svg class="d-tomato" viewBox="0 0 200 150"><use href="#s-tomato"/></svg>
    <svg class="d-leaf" viewBox="0 0 160 90"><use href="#s-leaf"/></svg>
    <svg class="d-slice2" viewBox="0 0 175 180"><use href="#s-slice"/></svg>
  </div>

  <!-- coretan petir -->
  <svg class="zap" style="left:3.6vw;top:4vh" viewBox="0 0 50 30" stroke="#F8F5E6"><path d="M3 5l38 4-26 12 32 4"/></svg>
  <svg class="zap" style="left:62vw;top:2.5vh" viewBox="0 0 30 70" stroke="#F8F5E6"><path d="M18 3L4 31l22-6-16 36"/></svg>
  <svg class="zap" style="left:51vw;top:24vh" viewBox="0 0 30 70" stroke="#F8F5E6"><path d="M18 3L6 33l20-8-16 34"/></svg>
  <svg class="zap" style="right:2.5vw;top:64vh" viewBox="0 0 50 30" stroke="#B2C2D2" stroke-width="5"><path d="M3 15l20-10-10 22 26-14"/></svg>
  <svg class="zap" style="left:54vw;bottom:5vh" viewBox="0 0 50 30" stroke="#97173A"><path d="M3 5l38 4-26 12 32 4"/></svg>
  <svg class="zap" style="left:1.6vw;bottom:2vh" viewBox="0 0 50 34" stroke="#B2C2D2" stroke-width="5"><path d="M3 30l18-24 2 18 18-22"/></svg>

  <section class="visual" aria-hidden="true">
    <div class="strip"></div>
    <div class="logo"><b>SIM RESTO</b><small>Pizza &amp; Pasta House</small></div>
    <div class="plates">
      <div class="plate p1"><img src="https://images.unsplash.com/photo-1595854341625-f33ee10dbf94?w=900&q=80" alt=""></div>
      <div class="plate p2"><img src="https://images.unsplash.com/photo-1593560708920-61dd98c46a4e?w=600&q=80" alt=""></div>
      <div class="plate p3"><img src="https://images.unsplash.com/photo-1574071318508-1cdbab80d002?w=600&q=80" alt=""></div>
    </div>
  </section>

  <section class="right">
    <form class="form" action="{{ route('login.submit') }}" method="post">
      @csrf
      <h1>Selamat Datang Kembali!</h1>
      <label class="field">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
        <input type="email" name="email" placeholder="Email" autocomplete="email" required>
      </label>
      <label class="field">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
        <input type="password" name="password" placeholder="Password" autocomplete="current-password" required>
      </label>
      <a class="forgot" href="#">Lupa password?</a>
      <button class="btn" type="submit">Masuk</button>
      <div class="divider">atau</div>
      <div class="social">
        <a href="#"><svg viewBox="0 0 24 24" fill="#97173A"><path d="M21.6 10.2H12v3.9h5.5c-.5 2.5-2.7 4.1-5.5 4.1a6.2 6.2 0 1 1 4-10.9l2.8-2.8A10 10 0 1 0 12 22c5.8 0 9.8-4.1 9.8-9.8 0-.7-.1-1.3-.2-2z"/></svg>Google</a>
        <a href="#"><svg viewBox="0 0 24 24" fill="#97173A"><path d="M12 2a10 10 0 0 0-1.6 19.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.4 2.9h-2.3v7A10 10 0 0 0 12 2z"/></svg>Facebook</a>
      </div>
      <p class="signup">Belum punya akun? <a href="{{ route('registrasi') }}">Daftar</a></p>
    </form>
  </section>
</main>
</body>
</html>
