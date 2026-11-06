@extends('layouts.panel')

@section('title', 'Tutup Hari (EOD)')
@section('crumb', 'Kasir / Tutup Hari')
@section('heading', 'Tutup Hari (EOD)')
@section('sub', 'Rekap penjualan sejak '.($dari->isAfter(today()) ? 'tutup hari terakhir pukul '.$dari->format('H:i') : 'pukul 00:00 hari ini'))

@php
  $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
  $terhalang = $halang['pesanan']->isNotEmpty() || $halang['setoran']->isNotEmpty();
@endphp

@push('styles')
<style>
  .eod-row { display: flex; gap: 10px; align-items: baseline; padding: 9px 0; border-bottom: 1px solid var(--m15); font-size: 13.5px; }
  .eod-row:last-child { border-bottom: 0; }
  .eod-row b { font: 600 13.5px var(--f-title); }
  .eod-row .r { margin-left: auto; font: 600 13.5px var(--f-title); white-space: nowrap; }
  .eod-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr)); gap: 18px; align-items: start; margin-top: 18px; }
</style>
@endpush

@section('content')
  <div class="grid g4">
    <div class="card stat p"><div class="top"><span class="eyebrow">Pemasukan</span><span class="ico"><svg class="ic"><use href="#i-cash"/></svg></span></div><div class="v">{{ $rp($rekap['total_pemasukan']) }}</div><div class="t">{{ $rekap['jumlah_lunas'] }} pembayaran lunas</div></div>
    <div class="card stat f"><div class="top"><span class="eyebrow">Tunai</span><span class="ico"><svg class="ic"><use href="#i-wallet"/></svg></span></div><div class="v">{{ $rp($rekap['total_tunai']) }}</div><div class="t">Non-tunai {{ $rp($rekap['total_nontunai']) }}</div></div>
    <div class="card stat b"><div class="top"><span class="eyebrow">Pesanan</span><span class="ico"><svg class="ic"><use href="#i-receipt"/></svg></span></div><div class="v">{{ $rekap['jumlah_pesanan'] }}</div><div class="t">{{ $rekap['jumlah_batal'] }} dibatalkan</div></div>
    <div class="card stat"><div class="top"><span class="eyebrow">Setoran kurir</span><span class="ico"><svg class="ic"><use href="#i-truck"/></svg></span></div><div class="v">{{ $rp($rekap['total_setoran']) }}</div><div class="t">Uang COD yang sudah diterima</div></div>
  </div>

  <div class="eod-grid">
    <div class="card"><div class="card-head"><div><h2>Rincian</h2><p>Per metode bayar dan jenis layanan</p></div></div>
      @forelse ($rekap['per_metode'] as $metode => $jumlah)
        <div class="eod-row"><b>{{ $metode }}</b><span class="r">{{ $rp($jumlah) }}</span></div>
      @empty
        <div class="empty">Belum ada pembayaran lunas.</div>
      @endforelse
      @foreach ($rekap['per_layanan'] as $layanan => $n)
        <div class="eod-row"><span>{{ $layanan }}</span><span class="r">{{ $n }} pesanan</span></div>
      @endforeach
    </div>

    <div class="card"><div class="card-head"><div><h2>Tutup Hari</h2><p>Setelah ditutup, angka penjualan kasir mulai lagi dari nol</p></div></div>
      @if ($terhalang)
        <div class="alert" role="alert" style="margin-bottom:12px">Belum bisa tutup hari. Bereskan dulu daftar di bawah ini.</div>
        @foreach ($halang['pesanan'] as $p)
          <div class="eod-row"><b>{{ $p->kode() }}</b><span style="opacity:.8;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $p->namaPemesan() }} · {{ $p->layananDetail() }}</span><span class="r">{{ $p->statusLabel() }}</span></div>
        @endforeach
        @foreach ($halang['setoran']->groupBy('id_kurir') as $daftar)
          <div class="eod-row"><b>{{ $daftar->first()->kurir?->nama ?? 'Kurir dihapus' }}</b><span style="opacity:.8">belum menyetor {{ $daftar->count() }} pesanan COD</span><span class="r">{{ $rp($daftar->sum('jumlah')) }}</span></div>
        @endforeach
        <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:14px">
          @if ($halang['pesanan']->isNotEmpty())<a class="btn o" href="{{ route('kasir.dashboard') }}#berjalan">Lihat pesanan berjalan</a>@endif
          @if ($halang['setoran']->isNotEmpty())<a class="btn o" href="{{ route('kasir.setoran') }}">Terima setoran kurir</a>@endif
        </div>
      @else
        <p style="font-size:13.5px;line-height:1.55;margin-bottom:14px">Semua pesanan sudah selesai dan semua uang kurir sudah disetor. Setelah tutup hari, kasir <b>tidak bisa lagi melihat</b> penjualan dan struk sebelum waktu ini. Rekap lengkap tetap tersedia di laporan owner.</p>
        <form method="post" action="{{ route('kasir.eod.store') }}" data-konfirmasi-judul="Tutup hari sekarang?" data-konfirmasi="Pemasukan {{ $rp($rekap['total_pemasukan']) }} dari {{ $rekap['jumlah_lunas'] }} pembayaran akan direkap. Setelah itu penjualan kasir mulai lagi dari nol dan transaksi sebelumnya tidak bisa dilihat kasir.">@csrf
          <button class="btn" type="submit"><svg class="ic"><use href="#i-lock"/></svg>Tutup Hari &amp; Cetak Rekap</button>
        </form>
      @endif
    </div>
  </div>
@endsection
