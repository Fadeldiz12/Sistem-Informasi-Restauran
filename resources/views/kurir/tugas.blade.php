@extends('layouts.kurir', ['tab' => 'tugas'])

@section('title', 'Tugas Pengiriman')

@php $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.'); @endphp

@section('content')
  <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:6px;gap:10px"><h1 style="font:700 24px var(--f-title)">Tugas Pengiriman</h1><span class="pill b">FIFO</span></div>
  <p style="font-size:13px;opacity:.7">Pesanan Delivery berstatus Siap Diantar, diurutkan dari waktu siap paling awal.</p>

  @if ($jmlDiantar)
    <a class="note" href="{{ route('kurir.status') }}" style="margin-top:14px"><svg class="ic"><use href="#i-truck"/></svg><span style="flex:1">Kamu sedang mengantar <b>{{ $jmlDiantar }}</b> pesanan. Perbarui statusnya setelah diterima pelanggan.</span><svg class="ic"><use href="#i-arr"/></svg></a>
  @endif

  <div style="margin-top:8px">
    @forelse ($antrean as $i => $k)
      @php $p = $k->pesanan; @endphp
      <div @class(['tc', 'new' => $i === 0])><span class="fifo">FIFO #{{ $i + 1 }}</span>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px"><b style="font:700 17px var(--f-title)">{{ $p->kode() }}</b><span @class(['pill', 'c' => $i === 0, 'b' => $i > 0])>{{ $k->zona?->nama_zona }} · {{ $rp($p->ongkir) }}</span></div>
        <div style="font-size:12px;opacity:.8;margin-top:4px;display:flex;gap:5px;align-items:center"><svg class="ic" style="width:13px;height:13px"><use href="#i-clock"/></svg>Siap diantar {{ $k->waktu_siap->format('H:i') }} · {{ $k->waktu_siap->diffForHumans() }}</div>
        <div style="font:700 14px var(--f-accent);margin-top:8px">{{ $p->namaPemesan() }}</div>
        <div style="display:flex;gap:6px;font-size:12.5px;opacity:.8;margin-top:4px"><svg class="ic" style="width:15px;height:15px"><use href="#i-pin"/></svg>{{ $k->alamat }}</div>
        @if ($i === 0)
          <a class="btn cr" href="{{ route('kurir.tugas.show', $k) }}" style="width:100%;margin-top:14px">Lihat Detail <svg class="ic" style="width:16px;height:16px"><use href="#i-arr"/></svg></a>
        @else
          <div class="lk"><svg class="ic" style="width:13px;height:13px"><use href="#i-lock"/></svg>Menunggu tugas #{{ $i }} diterima</div>
        @endif
      </div>
    @empty
      <div class="card soft" style="margin-top:16px"><div class="empty"><svg class="ic" style="width:30px;height:30px;margin:0 auto 10px"><use href="#i-truck"/></svg>Belum ada pesanan yang siap diantar.<br><small style="font-weight:500">Tugas baru muncul otomatis setelah dapur menandai pesanan Delivery siap.</small></div></div>
    @endforelse
  </div>
@endsection

@push('scripts')
<script>setTimeout(function () { location.reload(); }, 20000);</script>
@endpush
