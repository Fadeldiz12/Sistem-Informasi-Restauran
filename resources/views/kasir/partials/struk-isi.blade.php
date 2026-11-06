{{-- Isi struk, dipakai di dialog Cetak Struk dan halaman cetak --}}
@php
  $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
  $bayar = $pesanan->pembayaran;
  $set = \App\Models\PengaturanSistem::aktif();
@endphp
<div class="struk">
  <div style="text-align:center"><img src="{{ asset('images/logo-192.png') }}" alt="" width="52" height="52" style="border-radius:50%;display:block;margin:0 auto 6px"><b style="font:700 22px var(--f-title)">{{ mb_strtoupper(config('resto.nama')) }}</b><div style="font-size:11.5px;opacity:.7">{{ config('resto.alamat') }}</div><div style="font-size:11.5px;opacity:.7">{{ config('resto.telepon') }}</div></div>
  <div class="sl"></div>
  <div class="sr"><span class="mut">No. pesanan</span><span>{{ $pesanan->kode() }}</span></div>
  <div class="sr"><span class="mut">Layanan</span><span>{{ $pesanan->layananDetail() }}</span></div>
  <div class="sr"><span class="mut">Pelanggan</span><span>{{ $pesanan->namaPemesan() }}</span></div>
  <div class="sr"><span class="mut">Kasir</span><span>{{ $bayar?->kasir?->nama ?? '-' }}</span></div>
  <div class="sr"><span class="mut">Waktu</span><span>{{ ($bayar?->waktu_bayar ?? now())->format('d/m/Y H:i') }}</span></div>
  <div class="sl"></div>
  @foreach ($pesanan->detail as $d)
    <div class="sr"><span>{{ $d->jumlah }}× {{ $d->menu->nama_menu }}</span><span>{{ $rp($d->subtotal) }}</span></div>
    @if ($d->catatan)<div class="sr" style="font-size:11.5px;opacity:.65;padding-top:0"><span>&nbsp;&nbsp;{{ $d->catatan }}</span></div>@endif
  @endforeach
  <div class="sl"></div>
  <div class="sr"><span class="mut">Subtotal</span><span>{{ $rp($pesanan->subtotal) }}</span></div>
  <div class="sr"><span class="mut">Pajak &amp; layanan</span><span>{{ $rp($pesanan->pajakDanLayanan()) }}</span></div>
  @if ($pesanan->isDelivery())<div class="sr"><span class="mut">Ongkir ({{ $pesanan->pengiriman?->zona?->nama_zona }})</span><span>{{ $rp($pesanan->ongkir) }}</span></div>@endif
  <div class="sr" style="font:700 16px var(--f-title);padding:6px 0"><span>TOTAL</span><span>{{ $rp($pesanan->total) }}</span></div>
  <div class="sr"><span class="mut">{{ $bayar?->metodeLabel() }}</span><span>{{ $rp($bayar?->uang_diterima ?? $pesanan->total) }}</span></div>
  @if ($bayar?->kembalian() !== null)<div class="sr"><span class="mut">Kembalian</span><span>{{ $rp($bayar->kembalian()) }}</span></div>@endif
  <div style="text-align:center;margin-top:12px"><span class="pill m">LUNAS</span></div>
  <div style="text-align:center;font-size:11.5px;opacity:.65;margin-top:10px">Terima kasih atas kunjungan Anda</div>
</div>
