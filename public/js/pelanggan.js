/* Resto Flow — skrip bersama halaman pelanggan */
(function () {
  var csrf = document.querySelector('meta[name="csrf-token"]').content;
  var t = document.getElementById('pToast');

  window.rp = function (n) { return 'Rp ' + Math.round(Number(n) || 0).toLocaleString('id-ID'); };
  window.pToast = function (msg) {
    if (!t) return; t.textContent = msg; t.classList.add('on');
    clearTimeout(window._pt); window._pt = setTimeout(function () { t.classList.remove('on'); }, 2600);
  };
  /* toast dengan satu tombol aksi (mis. "Batalkan"), tampil lebih lama */
  window.pToastAksi = function (msg, label, aksi) {
    if (!t) return;
    t.textContent = msg + ' ';
    var b = document.createElement('button'); b.type = 'button'; b.textContent = label;
    b.style.cssText = 'margin-left:10px;color:inherit;font:inherit;text-decoration:underline;font-weight:800;background:none;border:0;cursor:pointer';
    b.addEventListener('click', function () { t.classList.remove('on'); aksi(); });
    t.appendChild(b); t.classList.add('on');
    clearTimeout(window._pt); window._pt = setTimeout(function () { t.classList.remove('on'); }, 6000);
  };
  window.sendJson = function (url, method, data) {
    return fetch(url, {
      method: method,
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
      body: data ? JSON.stringify(data) : undefined
    }).catch(function () {
      // fetch gagal = tidak ada koneksi (pesan bawaan browser "Failed to fetch" tidak dipahami awam)
      throw new Error('Koneksi internet terputus. Periksa sinyal lalu coba lagi.');
    }).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (j) {
        if (r.status === 401 || r.status === 419) {
          setTimeout(function () { location.href = j.login || '/login'; }, 1800);
          throw new Error('Sesi kamu sudah berakhir. Mengarahkan ke halaman masuk…');
        }
        if (!r.ok) {
          var msg = j.message || 'Terjadi kesalahan, coba lagi.';
          if (j.errors) { var k = Object.keys(j.errors)[0]; if (k) msg = j.errors[k][0]; }
          throw new Error(msg);
        }
        return j;
      });
    });
  };
  /* perbarui angka keranjang di header: { jumlah, subtotal } */
  window.setCart = function (r) {
    document.querySelectorAll('[data-cart-total]').forEach(function (el) { el.textContent = rp(r.subtotal); });
    document.querySelectorAll('[data-cart-count]').forEach(function (el) { el.textContent = r.jumlah; if (el.closest('.bnav')) el.hidden = !r.jumlah; });
  };
  var boot = document.getElementById('p-boot');
  if (boot && boot.dataset.toast) pToast(boot.dataset.toast);
})();

/* Foto menu yang gagal dimuat (link rusak / sedang offline): sembunyikan, tampilkan latar kotak foto saja */
(function () {
  function sembunyikan(img) { if (img.tagName === 'IMG' && !img.dataset.gagal) { img.dataset.gagal = '1'; img.style.visibility = 'hidden'; } }
  document.addEventListener('error', function (e) { sembunyikan(e.target); }, true);
  document.querySelectorAll('img').forEach(function (img) { if (img.complete && img.naturalWidth === 0 && img.getAttribute('src')) sembunyikan(img); });
})();
