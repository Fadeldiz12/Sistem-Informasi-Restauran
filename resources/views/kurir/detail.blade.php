@extends('layouts.kurir', ['tab' => 'tugas'])

@section('title', 'Detail Tugas')

@php
  $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
  $p = $kirim->pesanan;
  $hp = $p->pelanggan?->no_hp;
  $km = rtrim(rtrim(number_format((float) $kirim->jarak_km, 2, ',', '.'), '0'), ',');
  $siap = $urutan !== false;
@endphp

@section('content')
  <a class="back" href="{{ route('kurir.tugas') }}"><svg class="ic"><use href="#i-arr"/></svg>Kembali</a>
  <div class="stamp-out"><div class="stamp" style="padding:20px 22px"><div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap"><span class="eyebrow">{{ $siap ? 'Antrean ke-'.($urutan + 1) : 'Tugas pengiriman' }}</span><span class="pill b">{{ $kirim->status_kirim === \App\Models\Pengiriman::STATUS_DITERIMA ? 'Diterima' : ($siap ? 'Siap Diantar' : $p->statusLabel()) }}</span></div>
    <div style="font:700 24px var(--f-title);margin-top:6px">{{ $p->kode() }}</div>
    <div style="font-size:12.5px;opacity:.7">Dipesan {{ $p->waktu_pesan->translatedFormat('d M Y, H:i') }}@if ($kirim->waktu_siap) · siap {{ $kirim->waktu_siap->format('H:i') }}@endif</div></div></div>

  <div class="card" style="margin-top:14px;padding:16px"><div class="who"><span class="av">{{ $p->pelanggan?->inisial() ?? 'PL' }}</span><div style="flex:1;min-width:0"><b style="font:600 15px var(--f-title)">{{ $p->namaPemesan() }}</b><div style="font-size:12.5px;opacity:.7">{{ $hp ?: 'Nomor HP tidak tersedia' }}</div></div>
      @if ($hp)<a class="icon-btn" style="width:40px;height:40px" href="tel:{{ preg_replace('/[^\d+]/', '', $hp) }}" aria-label="Telepon pelanggan"><svg class="ic" style="width:16px;height:16px"><use href="#i-phone"/></svg></a>@endif</div>
    <div style="display:flex;gap:8px;font-size:13.5px;margin-top:12px;padding-top:12px;border-top:1.5px dashed var(--m60);line-height:1.45"><svg class="ic" style="width:17px;height:17px"><use href="#i-pin"/></svg><span style="flex:1">{{ $kirim->alamat }}</span></div>
    <a class="btn o sm" style="width:100%;margin-top:12px" href="https://www.google.com/maps/search/?api=1&query={{ urlencode($kirim->alamat) }}" target="_blank" rel="noopener"><svg class="ic" style="width:15px;height:15px"><use href="#i-nav"/></svg>Buka di Maps</a></div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px">
    <div class="card b" style="padding:14px"><div class="eyebrow" style="font-size:9.5px">Ongkos kirim</div><div style="font:700 20px var(--f-title);margin-top:2px">{{ $rp($p->ongkir) }}</div><div style="font-size:11.5px;opacity:.75">{{ $kirim->zona?->nama_zona }} · {{ $km }} km · sudah di dalam total</div></div>
    @if ($p->isLunas())
      <div class="card f" style="padding:14px"><div class="eyebrow" style="font-size:9.5px">Pembayaran</div><div style="font:700 20px var(--f-title);margin-top:2px">Lunas</div><div style="font-size:11.5px;opacity:.8">{{ $p->pembayaran->metodeLabel() }}</div></div>
    @else
      <div class="card f" style="padding:14px"><div class="eyebrow" style="font-size:9.5px">Total ditagih</div><div style="font:700 20px var(--f-title);margin-top:2px">{{ $rp($p->total) }}</div><div style="font-size:11.5px;opacity:.8">Bayar di tempat · sudah termasuk ongkir</div></div>
    @endif
  </div>

  <div class="card soft" style="margin-top:10px;padding:14px 16px"><div class="eyebrow" style="font-size:9.5px;margin-bottom:6px">Detail pesanan</div>
    @foreach ($p->detail as $d)
      <div class="item"><b>{{ $d->jumlah }}×</b><span>{{ $d->menu->nama_menu }}@if ($d->catatan)<small style="display:block;font-size:12px;opacity:.7">Catatan: {{ $d->catatan }}</small>@endif</span></div>
    @endforeach
  </div>

  @if ($siap && $urutan === 0)
    <form method="post" action="{{ route('kurir.tugas.terima', $kirim) }}">@csrf
      <button class="btn lg" type="submit" style="width:100%;margin-top:16px"><svg class="ic"><use href="#i-check"/></svg>Terima Tugas</button></form>
    <p style="font-size:12px;opacity:.6;text-align:center;margin-top:10px">Setelah diterima, status pesanan menjadi Dalam Perjalanan dan pelanggan dapat melacaknya.</p>
  @elseif ($siap)
    <div class="note" style="margin-top:16px"><svg class="ic"><use href="#i-lock"/></svg><span>Tugas ini terkunci. Ambil dulu tugas urutan pertama (antrean ke-{{ $urutan }}).</span></div>
  @endif
@endsection
