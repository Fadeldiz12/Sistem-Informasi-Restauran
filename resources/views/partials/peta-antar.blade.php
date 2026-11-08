{{--
  Titik pengantaran di peta (OpenStreetMap), dipakai checkout pelanggan & input pesanan kasir.
  GPS, cari alamat, atau geser pin; zona & ongkir dihitung dari jarak ke restoran (server menghitung ulang).
  Variabel: $petaAlamat, $petaPatokan, $petaLat, $petaLng, $petaAlamatProfil (opsional, chip "Pakai alamat profil").
  JS: window.PetaAntar.siapkan() saat panel tampil; event "peta:zona" (detail = zona atau null) setiap titik berubah.
--}}
@php
  $petaAlamatProfil = $petaAlamatProfil ?? null;
  $petaSet = \App\Models\PengaturanSistem::aktif();
  $petaZona = \App\Models\ZonaPengiriman::orderBy('radius_km')->get()
      ->map(fn ($z) => ['nama' => $z->nama_zona, 'radius' => (float) $z->radius_km, 'ongkir' => $z->ongkir()])->values();
@endphp
<div class="eyebrow" style="font-size:10.5px">Titik pengantaran</div>
<div class="peta-alat">
  <button type="button" class="btn sm" id="btnGps"><svg class="ic"><use href="#i-pin"/></svg>Pakai lokasi saya</button>
  <div class="cari-alamat"><svg class="ic"><use href="#i-search"/></svg><input id="cariAlamat" placeholder="Cari jalan / nama tempat, mis. Jl. Sei Batang Hari" autocomplete="off"></div>
</div>
<div class="hasil-cari" id="hasilCari" hidden></div>
<div id="peta" class="peta" role="application" aria-label="Peta titik pengantaran"></div>
<div class="peta-info" id="petaInfo">Ketuk peta atau geser pin ke lokasi pengantaran.</div>
<input type="hidden" name="lat" id="lat" value="{{ $petaLat }}">
<input type="hidden" name="lng" id="lng" value="{{ $petaLng }}">
@error('lat')<div class="peta-galat" role="alert">{{ $message }}</div>@enderror

<div class="field" style="margin-top:14px"><label for="alamat">Alamat lengkap</label><div class="in ta"><svg class="ic" style="margin-top:2px"><use href="#i-pin"/></svg><textarea id="alamat" name="alamat" maxlength="500" placeholder="Terisi otomatis dari peta; lengkapi nomor rumah bila perlu">{{ $petaAlamat }}</textarea></div>@error('alamat')<div style="font:600 12.5px var(--f-body);margin-top:6px">{{ $message }}</div>@enderror</div>
@if ($petaAlamatProfil)
  @php $pakaiProfil = trim((string) $petaAlamat) !== '' && trim((string) $petaAlamat) === trim($petaAlamatProfil); @endphp
  <div class="chips" id="addrChips"><button type="button" @class(['on' => $pakaiProfil]) data-addr="profil">Pakai alamat profil</button><button type="button" @class(['on' => ! $pakaiProfil]) data-addr="lain">Alamat lain</button></div>
@endif
<div class="field" style="margin-top:12px"><label for="patokan">Patokan untuk kurir (opsional)</label><div class="in"><svg class="ic"><use href="#i-info"/></svg><input id="patokan" name="patokan" maxlength="255" value="{{ $petaPatokan }}" placeholder="Mis. pagar hijau, sebelah warung"></div></div>

@once
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<style>
  .peta { height: 300px; border-radius: 18px; margin-top: 10px; box-shadow: inset 0 0 0 1.5px var(--m15); z-index: 0; }
  .peta-alat { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 8px; }
  .cari-alamat { flex: 1; min-width: 200px; display: flex; align-items: center; gap: 10px; min-height: 44px; padding: 0 16px; border-radius: 999px; background: var(--pp); }
  .cari-alamat input { all: unset; flex: 1; min-width: 0; font: 500 14px var(--f-body); color: var(--m); }
  .hasil-cari { margin-top: 8px; border-radius: 16px; box-shadow: inset 0 0 0 1.5px var(--m15); overflow: hidden; }
  .hasil-cari button { display: block; width: 100%; text-align: left; padding: 10px 14px; font: 500 13.5px var(--f-body); border-bottom: 1px solid var(--m15); }
  .hasil-cari button:hover { background: var(--pp); }
  .peta-info { margin-top: 10px; padding: 12px 14px; border-radius: 14px; background: var(--pp); font: 600 13.5px var(--f-body); }
  .peta-info.ok { background: var(--bl); }
  .peta-info.bad, .peta-galat { background: var(--m); color: var(--cr); }
  .peta-galat { margin-top: 10px; padding: 12px 14px; border-radius: 14px; font: 600 13.5px var(--f-body); }
</style>
@endpush
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
  (function () {
    var RESTO = [{{ (float) ($petaSet->lat_resto ?? \App\Models\PengaturanSistem::LAT_AWAL) }}, {{ (float) ($petaSet->lng_resto ?? \App\Models\PengaturanSistem::LNG_AWAL) }}];
    var ZONA = @json($petaZona), alamatProfil = @json($petaAlamatProfil);
    var peta = null, pin = null, zonaAktif = null, alamatDiubah = false;
    var lat = document.getElementById('lat'), lng = document.getElementById('lng'), alamat = document.getElementById('alamat');
    var petaInfo = document.getElementById('petaInfo'), hasilCari = document.getElementById('hasilCari'), cariAlamat = document.getElementById('cariAlamat'), btnGps = document.getElementById('btnGps');
    function beriTahu(m) { (window.pToast || window.toast || window.alert)(m); }
    function uang(n) { return 'Rp ' + Math.round(Number(n) || 0).toLocaleString('id-ID'); }
    /* jarak garis lurus (km), sama dengan perhitungan server */
    function jarakKm(a, b) {
      var r = Math.PI / 180, dLat = (b[0] - a[0]) * r, dLng = (b[1] - a[1]) * r;
      var h = Math.sin(dLat / 2) ** 2 + Math.cos(a[0] * r) * Math.cos(b[0] * r) * Math.sin(dLng / 2) ** 2;
      return 6371 * 2 * Math.asin(Math.min(1, Math.sqrt(h)));
    }
    function km(n) { return n.toLocaleString('id-ID', { maximumFractionDigits: 1 }); }
    function kabari() { document.dispatchEvent(new CustomEvent('peta:zona', { detail: zonaAktif })); }

    alamat.addEventListener('input', function () { alamatDiubah = true; });
    function siapkan() {
      if (peta || !window.L) { if (!window.L) petaInfo.textContent = 'Peta gagal dimuat. Periksa koneksi internet lalu muat ulang halaman.'; return; }
      peta = L.map('peta', { zoomControl: true }).setView(RESTO, 13);
      L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(peta);
      L.circleMarker(RESTO, { radius: 8, color: '#97173A', fillColor: '#F8F5E6', fillOpacity: 1, weight: 3 }).addTo(peta).bindTooltip('Restoran');
      var maks = ZONA.length ? ZONA[ZONA.length - 1].radius : 0;
      if (maks) L.circle(RESTO, { radius: maks * 1000, color: '#97173A', weight: 1, fillOpacity: .04, dashArray: '4 6' }).addTo(peta);
      peta.on('click', function (e) { taruhPin(e.latlng.lat, e.latlng.lng, true); });
      if (lat.value && lng.value) taruhPin(+lat.value, +lng.value, false);
    }
    function taruhPin(la, ln, isiAlamat) {
      if (!pin) {
        pin = L.marker([la, ln], { draggable: true }).addTo(peta);
        pin.on('dragend', function () { var p = pin.getLatLng(); taruhPin(p.lat, p.lng, true); });
      } else pin.setLatLng([la, ln]);
      peta.setView([la, ln], Math.max(peta.getZoom(), 16));
      lat.value = la.toFixed(7); lng.value = ln.toFixed(7);
      var d = jarakKm(RESTO, [la, ln]);
      zonaAktif = ZONA.find(function (z) { return d <= z.radius; }) || null;
      petaInfo.className = 'peta-info ' + (zonaAktif ? 'ok' : 'bad');
      petaInfo.textContent = zonaAktif
        ? km(d) + ' km dari restoran · ' + zonaAktif.nama + ' · ongkir ' + uang(zonaAktif.ongkir)
        : km(d) + ' km dari restoran · di luar jangkauan Delivery (maks ' + km(ZONA.length ? ZONA[ZONA.length - 1].radius : 0) + ' km). Pilih Take Away.';
      kabari();
      if (isiAlamat && !alamatDiubah) alamatDariTitik(la, ln);
    }
    /* nama alamat dari titik (OpenStreetMap Nominatim) */
    function alamatDariTitik(la, ln) {
      fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&accept-language=id&lat=' + la + '&lon=' + ln)
        .then(function (r) { return r.json(); }).then(function (j) { if (j && j.display_name && !alamatDiubah) { alamat.value = j.display_name; alamat.dispatchEvent(new Event('change')); } })
        .catch(function () {});
    }
    function cari() {
      var q = cariAlamat.value.trim(); if (q.length < 3) return;
      var vb = [RESTO[1] - .5, RESTO[0] + .5, RESTO[1] + .5, RESTO[0] - .5].join(',');
      fetch('https://nominatim.openstreetmap.org/search?format=jsonv2&limit=5&countrycodes=id&accept-language=id&viewbox=' + vb + '&q=' + encodeURIComponent(q))
        .then(function (r) { return r.json(); }).then(function (hasil) {
          hasilCari.innerHTML = ''; hasilCari.hidden = false;
          if (!hasil.length) { hasilCari.innerHTML = '<button type="button" disabled>Alamat tidak ditemukan. Coba nama jalan lain, atau geser pin langsung di peta.</button>'; return; }
          hasil.forEach(function (h) {
            var b = document.createElement('button'); b.type = 'button'; b.textContent = h.display_name;
            b.addEventListener('click', function () { hasilCari.hidden = true; alamatDiubah = false; alamat.value = h.display_name; alamat.dispatchEvent(new Event('change')); taruhPin(+h.lat, +h.lon, false); });
            hasilCari.appendChild(b);
          });
        }).catch(function () { beriTahu('Pencarian alamat gagal. Periksa koneksi internet.'); });
    }
    var tunda;
    cariAlamat.addEventListener('input', function () { clearTimeout(tunda); tunda = setTimeout(cari, 600); });
    cariAlamat.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); clearTimeout(tunda); cari(); } });
    btnGps.addEventListener('click', function () {
      if (!navigator.geolocation) { beriTahu('Perangkat tidak mendukung lokasi otomatis. Cari alamat atau geser pin.'); return; }
      btnGps.disabled = true; btnGps.lastChild.textContent = 'Mencari lokasi…';
      navigator.geolocation.getCurrentPosition(function (pos) {
        btnGps.disabled = false; btnGps.lastChild.textContent = 'Pakai lokasi saya';
        alamatDiubah = false; taruhPin(pos.coords.latitude, pos.coords.longitude, true);
      }, function () {
        btnGps.disabled = false; btnGps.lastChild.textContent = 'Pakai lokasi saya';
        beriTahu('Lokasi tidak bisa diambil. Izinkan akses lokasi, atau cari alamat / geser pin.');
      }, { enableHighAccuracy: true, timeout: 10000 });
    });
    var chips = document.getElementById('addrChips');
    chips && chips.addEventListener('click', function (e) {
      var b = e.target.closest('button'); if (!b) return;
      chips.querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x === b); });
      alamat.value = b.dataset.addr === 'profil' ? alamatProfil : ''; alamat.focus();
      if (b.dataset.addr === 'profil' && alamatProfil) { cariAlamat.value = alamatProfil; cari(); }
    });

    window.PetaAntar = {
      /* dipanggil saat panel Delivery tampil (Leaflet butuh ukuran yang terlihat) */
      siapkan: function () { siapkan(); setTimeout(function () { peta && peta.invalidateSize(); }, 50); },
      zona: function () { return zonaAktif; },
    };
  })();
</script>
@endpush
@endonce
