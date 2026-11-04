@extends('layouts.kurir', ['tab' => 'riwayat'])

@section('title', 'Riwayat Pengiriman')

@php $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.'); @endphp

@section('content')
  <h1 style="font:700 24px var(--f-title)">Riwayat Pengiriman</h1><p style="font-size:13px;opacity:.7;margin-top:4px">Pesanan yang sudah kamu antar sampai diterima pelanggan</p>

  <div class="card soft" style="margin-top:14px;padding:6px 16px">
    @forelse ($riwayat as $k)
      <div class="rw">
        @if ($k->bukti_kirim)
          <a href="{{ route('pengiriman.bukti', $k) }}" target="_blank" rel="noopener" title="Lihat foto bukti"><img class="ft" src="{{ route('pengiriman.bukti', $k) }}" alt="Bukti {{ $k->pesanan->kode() }}" loading="lazy"></a>
        @else
          <span class="ft stripes" title="Tanpa foto bukti"></span>
        @endif
        <div style="flex:1;min-width:0"><div style="display:flex;justify-content:space-between;gap:8px;align-items:center"><b style="font:700 15px var(--f-title)">{{ $k->pesanan->kode() }}</b><span class="pill p" style="font-size:10.5px">Diterima {{ $k->updated_at->format('H:i') }}</span></div>
          <div style="font:700 13px var(--f-accent);margin-top:2px">{{ $k->pesanan->namaPemesan() }}</div>
          <div style="font-size:12px;opacity:.7;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $k->updated_at->translatedFormat('d M Y') }} · {{ $k->zona?->nama_zona }} · {{ $rp($k->pesanan->ongkir) }}</div></div>
      </div>
    @empty
      <div class="empty">Belum ada riwayat pengiriman.</div>
    @endforelse
  </div>
@endsection
