/* Resto Flow — skrip bersama halaman pelanggan */
(function () {
  var csrf = document.querySelector('meta[name="csrf-token"]').content;
  var t = document.getElementById('pToast');

  window.rp = function (n) { return 'Rp ' + Math.round(Number(n) || 0).toLocaleString('id-ID'); };
  window.pToast = function (msg) {
    if (!t) return; t.textContent = msg; t.classList.add('on');
    clearTimeout(window._pt); window._pt = setTimeout(function () { t.classList.remove('on'); }, 2600);
  };
  window.sendJson = function (url, method, data) {
    return fetch(url, {
      method: method,
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
      body: data ? JSON.stringify(data) : undefined
    }).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (j) {
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
    document.querySelectorAll('[data-cart-count]').forEach(function (el) { el.textContent = r.jumlah; });
  };
  var boot = document.getElementById('p-boot');
  if (boot && boot.dataset.toast) pToast(boot.dataset.toast);
})();
