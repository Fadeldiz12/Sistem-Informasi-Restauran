@php $rp = fn ($n) => ($n < 0 ? '-' : '').'Rp '.number_format(abs((float) $n), 0, ',', '.'); @endphp
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Laporan Keuangan {{ $judulPeriode }}</title>
<style>
  @page { margin: 28px 34px; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #282828; }
  .kop { border-bottom: 2px solid #97173A; padding-bottom: 10px; margin-bottom: 16px; }
  .kop td { vertical-align: middle; }
  .kop img { width: 54px; height: 54px; }
  .nama { font-size: 18px; font-weight: bold; color: #97173A; letter-spacing: 1px; }
  .kecil { font-size: 10px; color: #555; }
  h1 { font-size: 15px; margin: 0 0 2px; color: #97173A; }
  .ring { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 12px -8px 16px; }
  .ring td { width: 33%; padding: 10px 12px; border: 1px solid #97173A; border-radius: 8px; }
  .ring .lbl { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #97173A; }
  .ring .v { font-size: 15px; font-weight: bold; margin-top: 3px; }
  table.data { width: 100%; border-collapse: collapse; }
  table.data th { background: #97173A; color: #fff; padding: 7px 8px; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; text-align: right; }
  table.data td { padding: 6px 8px; border-bottom: 1px solid #e6dcdc; text-align: right; }
  table.data th:first-child, table.data td:first-child { text-align: left; }
  table.data tr:nth-child(even) td { background: #faf6ee; }
  table.data tr.total td { background: #F8F5E6; font-weight: bold; border-top: 2px solid #97173A; border-bottom: 2px solid #97173A; }
  .rugi { color: #c0392b; }
  .kaki { margin-top: 18px; font-size: 9.5px; color: #666; }
</style>
</head>
<body>
  <table class="kop" width="100%"><tr>
    <td width="64"><img src="{{ public_path('images/logo-192.png') }}" alt=""></td>
    <td><div class="nama">{{ strtoupper(config('resto.nama', 'SIM Resto')) }}</div><div class="kecil">{{ config('resto.alamat') }}@if (config('resto.telepon')) · {{ config('resto.telepon') }}@endif</div></td>
    <td style="text-align:right" class="kecil">Dicetak {{ now()->translatedFormat('d F Y, H:i') }}<br>oleh {{ auth()->user()?->nama }}</td>
  </tr></table>

  <h1>Laporan Keuangan</h1>
  <div class="kecil">Periode {{ $dari->translatedFormat('d F Y') }} – {{ $sampai->translatedFormat('d F Y') }} ({{ $baris->count() }} hari)</div>

  <table class="ring"><tr>
    <td><div class="lbl">Total pendapatan</div><div class="v">{{ $rp($pendapatan) }}</div></td>
    <td><div class="lbl">Total pengeluaran</div><div class="v">{{ $rp($pengeluaran) }}</div></td>
    <td><div class="lbl">{{ $laba < 0 ? 'Rugi' : 'Laba' }}</div><div class="v {{ $laba < 0 ? 'rugi' : '' }}">{{ $rp($laba) }}</div></td>
  </tr></table>

  <table class="data">
    <thead><tr><th>Tanggal</th><th>Pendapatan</th><th>Pengeluaran</th><th>Laba</th></tr></thead>
    <tbody>
      @foreach ($baris as $b)
        <tr><td>{{ $b['tanggal']->translatedFormat('d M Y') }}</td><td>{{ $rp($b['pendapatan']) }}</td><td>{{ $rp($b['pengeluaran']) }}</td><td class="{{ $b['laba'] < 0 ? 'rugi' : '' }}">{{ $rp($b['laba']) }}</td></tr>
      @endforeach
      <tr class="total"><td>TOTAL</td><td>{{ $rp($pendapatan) }}</td><td>{{ $rp($pengeluaran) }}</td><td class="{{ $laba < 0 ? 'rugi' : '' }}">{{ $rp($laba) }}</td></tr>
    </tbody>
  </table>
  <div class="kaki">Pendapatan dihitung dari pembayaran berstatus Lunas (pesanan dibatalkan tidak dihitung). Laba = pendapatan − pengeluaran operasional.</div>
</body>
</html>
