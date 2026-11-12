@extends('layouts.panel')

@use('App\Models\PengaturanSistem')

@section('title', 'Pengaturan Sistem')
@section('crumb', 'Admin / Pengaturan Sistem')
@section('heading', 'Pengaturan Sistem')
@section('sub', 'Pajak, biaya layanan, dan jam operasional restoran')
@section('error-sendiri', '1')

@php
  $contohSubtotal = 236000;
  $angka = fn ($v) => str_replace(',', '.', PengaturanSistem::persen($v));
@endphp

@push('styles')
<style>
  .save-bar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-top: 18px; }
  .save-bar p { flex: 1; min-width: 220px; font: 500 13.5px/1.5 var(--f-body); opacity: .75; display: flex; gap: 10px; align-items: center; }
  @media (max-width: 640px) { .save-bar .btn { flex: 1; } }
</style>
@endpush

@section('content')
  @include('partials.auth-alert')

  <form method="post" action="{{ route('admin.pengaturan.update') }}" id="fSet">
    @csrf
    @method('PUT')
    <div class="grid split" style="grid-template-columns:minmax(0,1fr) minmax(0,1fr);align-items:start">
      <div class="card"><div class="card-head"><div><h2>Biaya &amp; Pajak</h2><p>Persen dari subtotal, dipakai pada perhitungan total pesanan</p></div></div>
        <div class="fgrid">
          <div class="field"><label for="tax">Pajak restoran</label><div class="in"><svg class="ic"><use href="#i-file"/></svg><input id="tax" name="pajak" type="number" value="{{ old('pajak', $angka($pengaturan->pajak)) }}" min="0" max="100" step="0.01" required><span class="suf">%</span></div></div>
          <div class="field"><label for="svc">Biaya layanan</label><div class="in"><svg class="ic"><use href="#i-receipt"/></svg><input id="svc" name="biaya_layanan" type="number" value="{{ old('biaya_layanan', $angka($pengaturan->biaya_layanan)) }}" min="0" max="100" step="0.01" required><span class="suf">%</span></div></div>
        </div>
        <div class="stamp-out" style="margin-top:18px"><div class="stamp" style="padding:20px 22px;background:var(--bl)">
          <div class="eyebrow">Contoh perhitungan · Delivery zona terdekat</div>
          <div style="display:grid;gap:4px;margin-top:10px;font-size:13.5px">
            <div style="display:flex;justify-content:space-between"><span>Subtotal</span><span>Rp {{ number_format($contohSubtotal, 0, ',', '.') }}</span></div>
            <div style="display:flex;justify-content:space-between"><span>Pajak (<span id="tP">0</span>%)</span><span id="tV">Rp 0</span></div>
            <div style="display:flex;justify-content:space-between"><span>Biaya layanan (<span id="sP">0</span>%)</span><span id="sV">Rp 0</span></div>
            <div style="display:flex;justify-content:space-between"><span>Ongkos kirim</span><span>Rp {{ number_format($contohOngkir, 0, ',', '.') }}</span></div>
            <div style="display:flex;justify-content:space-between;border-top:1.5px dashed var(--m60);padding-top:8px;margin-top:4px;font:700 18px var(--f-title)"><span>Total</span><span id="gV">Rp 0</span></div></div></div></div>
      </div>

      <div class="card"><div class="card-head"><div><h2>Jam Operasional &amp; Sesi</h2><p>Di luar jam ini pesanan baru tidak dapat dibuat</p></div><span class="pill b">buka / tutup</span></div>
        <div class="fgrid">
          <div class="field"><label for="jamBuka">Jam buka</label><div class="in"><svg class="ic"><use href="#i-clock"/></svg><input type="time" id="jamBuka" name="jam_buka" value="{{ old('jam_buka', substr($pengaturan->jam_buka, 0, 5)) }}" required></div></div>
          <div class="field"><label for="jamTutup">Jam tutup</label><div class="in"><svg class="ic"><use href="#i-clock"/></svg><input type="time" id="jamTutup" name="jam_tutup" value="{{ old('jam_tutup', substr($pengaturan->jam_tutup, 0, 5)) }}" required></div></div>
        </div>
        <div class="field" style="margin-top:16px"><label for="diam">Logout otomatis staf bila tidak ada aktivitas</label><div class="in"><svg class="ic"><use href="#i-lock"/></svg><input id="diam" name="batas_diam_menit" type="number" value="{{ old('batas_diam_menit', $pengaturan->batas_diam_menit ?? 30) }}" min="5" max="240" step="1" required><span class="suf">menit</span></div></div>
        <div class="note" style="margin-top:16px"><svg class="ic"><use href="#i-info"/></svg><span>Jam operasional berlaku untuk setiap hari dan tampil di sidebar semua panel. Jam tutup yang lebih awal dari jam buka berarti tutup <b>keesokan harinya</b> (contoh 16:00 – 02:00).@if ($pengaturan->lewatTengahMalam()) Saat ini restoran tutup keesokan harinya pukul {{ substr($pengaturan->jam_tutup, 0, 5) }}.@endif</span></div>
      </div>
    </div>

    <div class="card" style="margin-top:18px"><div class="card-head"><div><h2>Titik Restoran di Peta</h2><p>Dipakai menghitung jarak & zona ongkir Delivery secara otomatis</p></div><span class="pill b">{{ config('resto.alamat') }}</span></div>
      <div id="petaResto" style="height:300px;border-radius:18px;z-index:0"></div>
      <input type="hidden" name="lat_resto" id="latResto" value="{{ old('lat_resto', (float) ($pengaturan->lat_resto ?? \App\Models\PengaturanSistem::LAT_AWAL)) }}">
      <input type="hidden" name="lng_resto" id="lngResto" value="{{ old('lng_resto', (float) ($pengaturan->lng_resto ?? \App\Models\PengaturanSistem::LNG_AWAL)) }}">
      <div class="note" style="margin-top:12px"><svg class="ic"><use href="#i-info"/></svg><span>Geser pin merah ke lokasi restoran yang tepat, lalu tekan Simpan. Lingkaran putus-putus = jangkauan zona terjauh. Titik saat ini: <b id="titikResto"></b></span></div>
    </div>

    <div class="card save-bar">
      <p><svg class="ic"><use href="#i-info"/></svg>Perubahan langsung berlaku pada pesanan baru.</p>
      <a class="btn o" href="{{ route('admin.pengaturan') }}">Batal</a>
      <button class="btn" type="submit"><svg class="ic"><use href="#i-check"/></svg>Simpan Pengaturan</button>
    </div>
  </form>
@endsection

@push('styles')<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">@endpush
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
  (function () {
    if (!window.L) { document.getElementById('petaResto').textContent = 'Peta gagal dimuat (periksa koneksi internet).'; return; }
    var titik = [+latResto.value, +lngResto.value], maks = {{ (float) \App\Models\ZonaPengiriman::max('radius_km') }};
    var m = L.map('petaResto').setView(titik, 14);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(m);
    var pin = L.marker(titik, { draggable: true }).addTo(m), area = maks ? L.circle(titik, { radius: maks * 1000, color: '#97173A', weight: 1, fillOpacity: .05, dashArray: '4 6' }).addTo(m) : null;
    function set(ll) { latResto.value = ll.lat.toFixed(7); lngResto.value = ll.lng.toFixed(7); titikResto.textContent = latResto.value + ', ' + lngResto.value; if (area) area.setLatLng(ll); }
    pin.on('dragend', function () { set(pin.getLatLng()); });
    m.on('click', function (e) { pin.setLatLng(e.latlng); set(e.latlng); });
    set(pin.getLatLng());
  })();
</script>
<script>
  (function () {
    var s = {{ $contohSubtotal }}, ongkir = {{ (float) $contohOngkir }};
    function calc() {
      var t = Math.round(s * (+tax.value || 0) / 100), v = Math.round(s * (+svc.value || 0) / 100);
      tP.textContent = tax.value || 0; sP.textContent = svc.value || 0;
      tV.textContent = rp(t); sV.textContent = rp(v); gV.textContent = rp(s + t + v + ongkir);
    }
    tax.addEventListener('input', calc); svc.addEventListener('input', calc);
    calc();
  })();
</script>
@endpush
