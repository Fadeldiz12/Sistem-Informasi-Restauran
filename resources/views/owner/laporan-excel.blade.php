<table>
  <tr><td colspan="4"><b>Laporan Keuangan {{ config('resto.nama', 'SIM Resto') }}</b></td></tr>
  <tr><td colspan="4">Periode {{ $dari->translatedFormat('d F Y') }} – {{ $sampai->translatedFormat('d F Y') }}</td></tr>
  <tr><td colspan="4"></td></tr>
  <tr><th><b>Tanggal</b></th><th><b>Pendapatan</b></th><th><b>Pengeluaran</b></th><th><b>Laba</b></th></tr>
  @foreach ($baris as $b)
    <tr><td>{{ $b['tanggal']->format('d/m/Y') }}</td><td>{{ $b['pendapatan'] }}</td><td>{{ $b['pengeluaran'] }}</td><td>{{ $b['laba'] }}</td></tr>
  @endforeach
  <tr><td><b>TOTAL</b></td><td><b>{{ $pendapatan }}</b></td><td><b>{{ $pengeluaran }}</b></td><td><b>{{ $laba }}</b></td></tr>
</table>
