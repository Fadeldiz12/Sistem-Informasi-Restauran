/*
 * Logout otomatis staf saat tidak ada aktivitas (klik, ketik, sentuh, roda mouse).
 * Muat ulang halaman otomatis (dashboard/pesanan online) TIDAK dihitung aktivitas: waktu aktivitas terakhir
 * disimpan di localStorage dan dipakai bersama semua tab. Semenit sebelum keluar, muncul peringatan.
 */
(function () {
  var el = document.currentScript, KUNCI = 'resto_aktif_terakhir';
  var batas = (+el.dataset.batas || 30) * 60000, mulai = (+el.dataset.mulai || 0) * 1000;
  var simpanOk = true, lokal = 0, tulisTerakhir = 0, kotak = null, keluar = false;
  try { localStorage.setItem('resto_uji', '1'); localStorage.removeItem('resto_uji'); } catch (e) { simpanOk = false; lokal = Date.now(); }

  function terakhir() {
    var v = 0;
    if (simpanOk) { try { v = +localStorage.getItem(KUNCI) || 0; } catch (e) { /* abaikan */ } }
    return Math.max(v, lokal, mulai);
  }
  function tandai(e) {
    if (e && e.isTrusted === false) { return; }
    var n = Date.now();
    lokal = n;
    if (n - tulisTerakhir > 3000) { tulisTerakhir = n; if (simpanOk) { try { localStorage.setItem(KUNCI, String(n)); } catch (e) { /* abaikan */ } } }
    if (kotak) { kotak.remove(); kotak = null; }
  }
  // tanpa scroll/mousemove: keduanya bisa terpicu sendiri saat halaman dimuat ulang otomatis
  ['pointerdown', 'keydown', 'wheel', 'touchstart'].forEach(function (ev) {
    window.addEventListener(ev, tandai, { passive: true, capture: true });
  });

  function peringatan(detik) {
    if (!kotak) {
      kotak = document.createElement('div');
      kotak.setAttribute('role', 'alertdialog');
      kotak.style.cssText = 'position:fixed;left:50%;bottom:24px;transform:translateX(-50%);z-index:9999;width:min(420px,calc(100% - 32px));'
        + 'background:#fff;color:#2b1a1f;border:2px solid var(--m,#97173A);border-radius:18px;padding:16px 18px;box-shadow:0 18px 40px rgba(0,0,0,.18);font:500 14px/1.5 system-ui,sans-serif';
      kotak.innerHTML = '<b style="display:block;font-size:15px;margin-bottom:4px">Masih di sana?</b><span id="sesiSisa"></span>'
        + '<button type="button" style="margin-top:12px;width:100%;height:42px;border:0;border-radius:12px;background:var(--m,#97173A);color:#fff;font:700 14px system-ui,sans-serif;cursor:pointer">Saya masih di sini</button>';
      kotak.querySelector('button').addEventListener('click', tandai);
      document.body.appendChild(kotak);
    }
    kotak.querySelector('#sesiSisa').textContent = 'Tidak ada aktivitas. Demi keamanan, kamu akan otomatis keluar dalam ' + detik + ' detik.';
  }

  function cek() {
    if (keluar) { return; }
    var sisa = batas - (Date.now() - terakhir());
    if (sisa <= 0) {
      keluar = true;
      var f = document.getElementById('logout-form');
      if (!f) { location.reload(); return; }
      var a = document.createElement('input');
      a.type = 'hidden'; a.name = 'alasan'; a.value = 'diam';
      f.appendChild(a); f.submit();
    } else if (sisa <= 60000) {
      peringatan(Math.ceil(sisa / 1000));
    } else if (kotak) {
      kotak.remove(); kotak = null;
    }
  }
  setInterval(cek, 1000);

  // staf yang masuk lewat verifikasi GPS: posisi dikirim tiap 5 menit; keluar area outlet = sesi berakhir
  if (el.dataset.gps && navigator.geolocation) {
    var kirimGps = function () {
      navigator.geolocation.getCurrentPosition(function (pos) {
        var t = document.querySelector('#logout-form input[name=_token]');
        fetch(el.dataset.gps, {
          method: 'POST', credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': t ? t.value : '' },
          body: JSON.stringify({ lat: pos.coords.latitude, lng: pos.coords.longitude, akurasi: Math.round(pos.coords.accuracy) })
        }).then(function (r) { if (r.status === 401) { r.json().then(function (j) { location.href = j.login || '/login'; }); } });
      }, function () { /* gagal membaca: server mengakhiri sesi bila GPS tidak terkirim terlalu lama */ }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 60000 });
    };
    kirimGps(); setInterval(kirimGps, 5 * 60000);
  }
})();
