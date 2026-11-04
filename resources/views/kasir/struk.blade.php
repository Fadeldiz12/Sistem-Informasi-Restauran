<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Struk {{ $pesanan->kode() }} · SIM Resto</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Raleway:wght@700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
<style>
  :root { --m: #97173A; --cr: #F8F5E6; --m60: rgba(151, 23, 58, .6); --f-title: 'Poppins', sans-serif; --f-accent: 'Raleway', sans-serif; --f-body: 'Open Sans', sans-serif; }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font: 400 13px/1.45 var(--f-body); color: #282828; background: #eee; }
  .kertas { width: 80mm; max-width: 100%; margin: 20px auto; background: #fff; padding: 18px 16px; }
  .struk .sl { border-top: 1.5px dashed #999; margin: 10px 0; }
  .struk .sr { display: flex; justify-content: space-between; gap: 10px; padding: 2px 0; }
  .struk .mut { opacity: .7; }
  .pill { display: inline-block; padding: 4px 12px; border-radius: 999px; border: 1.5px solid #282828; font: 700 11.5px var(--f-accent); letter-spacing: .06em; }
  .aksi { text-align: center; margin: 0 0 30px; }
  .aksi button { font: 600 14px var(--f-title); padding: 10px 22px; border-radius: 999px; border: 0; background: var(--m); color: var(--cr); cursor: pointer; }
  @media print { body { background: #fff; } .kertas { margin: 0; width: 100%; padding: 0; } .aksi { display: none; } @page { size: 80mm auto; margin: 4mm; } }
</style>
</head>
<body>
  <div class="kertas">@include('kasir.partials.struk-isi')</div>
  <div class="aksi"><button onclick="window.print()">Cetak lagi</button></div>
  <script>window.addEventListener('load', function () { window.print(); });</script>
</body>
</html>
