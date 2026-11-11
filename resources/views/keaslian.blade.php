@extends('layouts.auth')

@section('title', 'Cek Keaslian Dokumen')

@php $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.'); @endphp

@section('form')
    <div class="form">
      <h1>Cek Keaslian<br>{{ $jenis === 'struk' ? 'Struk' : 'Laporan' }}</h1>
      @if ($asli)
        <div class="alert ok" role="status" style="font-size:15px">✓ ASLI · tanda tangan digital cocok dengan data {{ config('resto.nama') }}</div>
        <p class="lead">Cocokkan angka di bawah dengan dokumen di tangan Anda. Bila ada yang berbeda, dokumen itu sudah diubah.</p>
        <div style="display:grid;gap:6px;font-size:14px;margin-top:10px">
          @if ($jenis === 'struk')
            <div style="display:flex;justify-content:space-between"><span>No. pesanan</span><b>{{ $pesanan->kode() }}</b></div>
            <div style="display:flex;justify-content:space-between"><span>Waktu bayar</span><b>{{ $pesanan->pembayaran->waktu_bayar->format('d/m/Y H:i') }}</b></div>
            @foreach ($pesanan->detail as $d)
              <div style="display:flex;justify-content:space-between;opacity:.85"><span>{{ $d->jumlah }}× {{ $d->menu?->nama_menu }}</span><span>{{ $rp($d->subtotal) }}</span></div>
            @endforeach
            <div style="display:flex;justify-content:space-between;border-top:1.5px dashed currentColor;padding-top:8px;margin-top:4px;font-size:17px"><span>TOTAL</span><b>{{ $rp($pesanan->total) }}</b></div>
            <div style="display:flex;justify-content:space-between"><span>Metode</span><b>{{ $pesanan->pembayaran->metodeLabel() }}</b></div>
          @else
            <div style="display:flex;justify-content:space-between"><span>Periode</span><b>{{ $laporan['dari'] }} s/d {{ $laporan['sampai'] }}</b></div>
            <div style="display:flex;justify-content:space-between"><span>Total pendapatan</span><b>{{ $rp($laporan['pd']) }}</b></div>
            <div style="display:flex;justify-content:space-between"><span>Total pengeluaran</span><b>{{ $rp($laporan['pk']) }}</b></div>
            <div style="display:flex;justify-content:space-between"><span>Laba bersih</span><b>{{ $rp($laporan['lb']) }}</b></div>
            @if ($laporan['waktu'])<div style="display:flex;justify-content:space-between"><span>Dibuat</span><b>{{ $laporan['waktu']->translatedFormat('d F Y, H:i') }}</b></div>@endif
          @endif
          <div style="display:flex;justify-content:space-between;opacity:.75;margin-top:6px"><span>Kode verifikasi</span><span>{{ $kode }}</span></div>
        </div>
      @else
        <div class="alert" role="alert" style="font-size:15px">✗ TIDAK VALID · kode verifikasi tidak cocok</div>
        <p class="lead">Dokumen ini bukan dari {{ config('resto.nama') }} atau isinya sudah diubah setelah dicetak. Hubungi restoran di {{ config('resto.telepon') }}.</p>
      @endif
    </div>
@endsection
