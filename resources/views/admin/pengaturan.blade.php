@extends('layouts.panel')

@section('title', 'Pengaturan Sistem')
@section('crumb', 'Admin / Pengaturan Sistem')
@section('heading', 'Pengaturan Sistem')
@section('sub', 'Pajak, biaya layanan, dan jam operasional restoran')

@php
  $contohSubtotal = 236000;
  $pajak = old('pajak', rtrim(rtrim((string) $pengaturan->pajak, '0'), '.'));
  $biaya = old('biaya_layanan', (int) $pengaturan->biaya_layanan);
@endphp

@section('content')
  @include('partials.auth-alert')

  <form method="post" action="{{ route('admin.pengaturan.update') }}" id="fSet">
    @csrf
    @method('PUT')
    <div class="grid split" style="grid-template-columns:minmax(0,1fr) minmax(0,1fr);align-items:start">
      <div class="card"><div class="card-head"><div><h2>Biaya &amp; Pajak</h2><p>Dipakai pada perhitungan total pesanan</p></div></div>
        <div class="fgrid">
          <div class="field"><label>Pajak restoran</label><div class="in"><svg class="ic"><use href="#i-file"/></svg><input id="tax" name="pajak" type="number" value="{{ $pajak }}" min="0" max="100" step="0.01" required><span class="suf">%</span></div></div>
          <div class="field"><label>Biaya layanan</label><div class="in"><span class="suf" style="opacity:1">Rp</span><input id="svc" name="biaya_layanan" value="{{ number_format((float) str_replace('.', '', (string) $biaya), 0, ',', '.') }}" inputmode="numeric" required><span class="suf">/ pesanan</span></div></div>
        </div>
        <div class="stamp-out" style="margin-top:18px"><div class="stamp" style="padding:20px 22px;background:var(--bl)">
          <div class="eyebrow">Contoh perhitungan · Delivery zona terdekat</div>
          <div style="display:grid;gap:4px;margin-top:10px;font-size:13.5px">
            <div style="display:flex;justify-content:space-between"><span>Subtotal</span><span>Rp {{ number_format($contohSubtotal, 0, ',', '.') }}</span></div>
            <div style="display:flex;justify-content:space-between"><span>Pajak (<span id="tP">0</span>%)</span><span id="tV">Rp 0</span></div>
            <div style="display:flex;justify-content:space-between"><span>Biaya layanan</span><span id="sV">Rp 0</span></div>
            <div style="display:flex;justify-content:space-between"><span>Ongkos kirim</span><span>Rp {{ number_format($contohOngkir, 0, ',', '.') }}</span></div>
            <div style="display:flex;justify-content:space-between;border-top:1.5px dashed var(--m60);padding-top:8px;margin-top:4px;font:700 18px var(--f-title)"><span>Total</span><span id="gV">Rp 0</span></div></div></div></div>
      </div>

      <div class="card"><div class="card-head"><div><h2>Jam Operasional</h2><p>Di luar jam ini pesanan baru tidak dapat dibuat</p></div><span class="pill b">buka / tutup</span></div>
        <div class="fgrid">
          <div class="field"><label>Jam buka</label><div class="in"><svg class="ic"><use href="#i-clock"/></svg><input type="time" name="jam_buka" value="{{ old('jam_buka', substr($pengaturan->jam_buka, 0, 5)) }}" required></div></div>
          <div class="field"><label>Jam tutup</label><div class="in"><svg class="ic"><use href="#i-clock"/></svg><input type="time" name="jam_tutup" value="{{ old('jam_tutup', substr($pengaturan->jam_tutup, 0, 5)) }}" required></div></div>
        </div>
        <div class="note" style="margin-top:16px"><svg class="ic"><use href="#i-info"/></svg><span>Jam operasional berlaku untuk setiap hari dan tampil di sidebar semua panel.</span></div>
      </div>
    </div>

    <div style="position:sticky;bottom:16px;margin-top:20px;z-index:10">
      <div class="card f" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:14px 16px 14px 22px;border-radius:999px">
        <svg class="ic"><use href="#i-info"/></svg><span style="flex:1;font:600 14px var(--f-accent)">Periksa kembali sebelum menyimpan. Perubahan langsung berlaku pada pesanan baru.</span>
        <a class="btn cr" href="{{ route('admin.pengaturan') }}" style="background:transparent;color:var(--cr);box-shadow:inset 0 0 0 1.5px var(--cr)">Batal</a>
        <button class="btn cr" type="submit"><svg class="ic"><use href="#i-check"/></svg>Simpan Pengaturan</button></div></div>
  </form>
@endsection

@push('scripts')
<script>
  (function () {
    var s = {{ $contohSubtotal }}, ongkir = {{ (float) $contohOngkir }};
    var num = function (v) { return Number(String(v || '').replace(/\./g, '')) || 0; };
    function calc() {
      var t = Math.round(s * (+tax.value || 0) / 100), v = num(svc.value);
      tP.textContent = tax.value || 0; tV.textContent = rp(t); sV.textContent = rp(v); gV.textContent = rp(s + t + v + ongkir);
    }
    tax.addEventListener('input', calc);
    svc.addEventListener('input', function () { var d = svc.value.replace(/\D/g, ''); svc.value = d ? Number(d).toLocaleString('id-ID') : ''; calc(); });
    calc();
  })();
</script>
@endpush
