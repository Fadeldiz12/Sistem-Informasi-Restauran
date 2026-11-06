<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Rekap Tutup Hari · Resto Flow</title>
@include('partials.favicon')
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Raleway:wght@700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
<style>
  :root { --m: #97173A; --cr: #F8F5E6; --f-title: 'Poppins', sans-serif; --f-body: 'Open Sans', sans-serif; }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font: 400 13px/1.45 var(--f-body); color: #282828; background: #eee; }
  .kertas { width: 80mm; max-width: 100%; margin: 20px auto; background: #fff; padding: 18px 16px; }
  h1 { font: 700 16px var(--f-title); text-align: center; }
  .c { text-align: center; }
  .sl { border-top: 1.5px dashed #999; margin: 10px 0; }
  .sr { display: flex; justify-content: space-between; gap: 10px; padding: 2px 0; }
  .sr.t { font: 700 14px var(--f-title); }
  .mut { opacity: .7; }
  .aksi { text-align: center; margin: 0 0 30px; display: flex; gap: 10px; justify-content: center; }
  .aksi button, .aksi a { font: 600 14px var(--f-title); padding: 10px 22px; border-radius: 999px; border: 0; background: var(--m); color: var(--cr); cursor: pointer; text-decoration: none; }
  .aksi a { background: #fff; color: var(--m); border: 1.5px solid var(--m); }
  @media print { body { background: #fff; } .kertas { margin: 0; width: 100%; padding: 0; } .aksi { display: none; } @page { size: 80mm auto; margin: 4mm; } }
</style>
</head>
<body>
@php $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.'); @endphp
  <div class="kertas">
    <h1>REKAP TUTUP HARI</h1>
    <p class="c mut">Resto Flow · EOD #{{ $tutup->id }}</p>
    <div class="sl"></div>
    <div class="sr"><span class="mut">Dari</span><span>{{ $tutup->mulai_dari->translatedFormat('d M Y, H:i') }}</span></div>
    <div class="sr"><span class="mut">Sampai</span><span>{{ $tutup->waktu_tutup->translatedFormat('d M Y, H:i') }}</span></div>
    <div class="sr"><span class="mut">Kasir</span><span>{{ $tutup->kasir?->nama }}</span></div>
    <div class="sl"></div>
    <div class="sr"><span>Pesanan</span><span>{{ $tutup->jumlah_pesanan }}</span></div>
    <div class="sr"><span>Dibatalkan</span><span>{{ $tutup->jumlah_batal }}</span></div>
    @foreach ($rekap['per_layanan'] as $layanan => $n)
      <div class="sr mut"><span>· {{ $layanan }}</span><span>{{ $n }}</span></div>
    @endforeach
    <div class="sl"></div>
    @foreach ($rekap['per_metode'] as $metode => $jumlah)
      <div class="sr"><span>{{ $metode }}</span><span>{{ $rp($jumlah) }}</span></div>
    @endforeach
    <div class="sr"><span>Tunai</span><span>{{ $rp($tutup->total_tunai) }}</span></div>
    <div class="sr"><span>Non-tunai</span><span>{{ $rp($tutup->total_nontunai) }}</span></div>
    <div class="sr"><span>Setoran kurir diterima</span><span>{{ $rp($tutup->total_setoran) }}</span></div>
    <div class="sl"></div>
    <div class="sr t"><span>TOTAL PEMASUKAN</span><span>{{ $rp($tutup->total_pemasukan) }}</span></div>
    <div class="sl"></div>
    <p class="c mut">Dicetak {{ now()->translatedFormat('d M Y, H:i') }}</p>
  </div>
  <div class="aksi"><button onclick="window.print()">Cetak</button><a href="{{ route('kasir.dashboard') }}">Ke Dashboard</a></div>
  <script>window.addEventListener('load', function () { window.print(); });</script>
</body>
</html>
