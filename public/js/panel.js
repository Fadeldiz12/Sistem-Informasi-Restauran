/* SIM Resto — skrip bersama panel staf. Diambil dari UI REV2, disesuaikan untuk data dari server. */
(function () {
  var csrf = document.querySelector('meta[name="csrf-token"]').content;
  var toastEl = document.getElementById('toast');
  var toastMsg = document.getElementById('toastMsg');

  /* notifikasi singkat */
  window.toast = function (msg) {
    toastMsg.textContent = msg; toastEl.classList.add('on');
    clearTimeout(window._t); window._t = setTimeout(function () { toastEl.classList.remove('on'); }, 2600);
  };
  window.rp = function (n) { return 'Rp ' + Math.round(Number(n) || 0).toLocaleString('id-ID'); };
  window.openModal = function (id) { var m = document.getElementById(id); if (m) m.classList.add('on'); };

  /* buka/tutup dialog */
  document.addEventListener('click', function (e) {
    var o = e.target.closest('[data-open]');
    if (o) { e.preventDefault(); openModal(o.dataset.open); }
    if (e.target.closest('[data-close]') || e.target.classList.contains('ovl')) {
      var ov = e.target.closest('.ovl'); if (ov) { e.preventDefault(); ov.classList.remove('on'); }
    }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') document.querySelectorAll('.ovl.on').forEach(function (o) { o.classList.remove('on'); });
  });

  /* grup tombol pilihan (chips / seg) yang berupa <button> */
  document.querySelectorAll('.chips, .seg').forEach(function (g) {
    g.addEventListener('click', function (e) {
      var b = e.target.closest('button'); if (!b || !g.contains(b)) return;
      g.querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x === b); });
      var input = g.dataset.input && document.getElementById(g.dataset.input);
      if (input) input.value = b.dataset.value;
    });
  });

  /* filter tabel di sisi browser: [data-filter-for] pada grup chips, [data-search-for] pada input */
  function applyFilter(tbodyId) {
    var tb = document.getElementById(tbodyId); if (!tb) return;
    var fs = [].slice.call(document.querySelectorAll('[data-filter-for="' + tbodyId + '"]')).map(function (g) { var o = g.querySelector('.on'); return o ? o.dataset.f : 'all'; });
    var s = document.querySelector('[data-search-for="' + tbodyId + '"]');
    var q = s ? s.value.toLowerCase() : '';
    var shown = 0;
    tb.querySelectorAll('tr[data-f]').forEach(function (tr) {
      var tags = (tr.dataset.f || '').split(' ');
      var ok = fs.every(function (f) { return f === 'all' || tags.indexOf(f) >= 0; }) && tr.textContent.toLowerCase().indexOf(q) >= 0;
      tr.style.display = ok ? '' : 'none'; if (ok) shown++;
    });
    var c = document.querySelector('[data-count-for="' + tbodyId + '"]'); if (c) c.textContent = shown;
  }
  document.querySelectorAll('[data-filter-for]').forEach(function (g) {
    g.addEventListener('click', function (e) {
      var b = e.target.closest('button'); if (!b) return;
      g.querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x === b); });
      applyFilter(g.dataset.filterFor);
    });
  });
  document.querySelectorAll('[data-search-for]').forEach(function (i) {
    i.addEventListener('input', function () { applyFilter(i.dataset.searchFor); });
  });

  /* hapus data dengan konfirmasi: tombol .js-del punya data-url (route destroy) dan data-name */
  var pendingUrl = null;
  document.addEventListener('click', function (e) {
    var d = e.target.closest('.js-del'); if (!d) return;
    e.preventDefault();
    pendingUrl = d.dataset.url;
    var n = document.getElementById('delName'); if (n) n.textContent = d.dataset.name || '';
    openModal('mHapus');
  });
  var delOk = document.getElementById('delOk');
  if (delOk) delOk.addEventListener('click', function () {
    if (!pendingUrl) return;
    var f = document.createElement('form');
    f.method = 'post'; f.action = pendingUrl; f.style.display = 'none';
    f.innerHTML = '<input type="hidden" name="_token"><input type="hidden" name="_method" value="DELETE">';
    f.querySelector('[name=_token]').value = csrf;
    document.body.appendChild(f); f.submit();
  });

  /* kirim permintaan kecil (mis. toggle status) tanpa pindah halaman */
  window.sendJson = function (url, method, data) {
    return fetch(url, {
      method: method,
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
      body: JSON.stringify(data || {})
    }).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (j) {
        if (!r.ok) throw new Error(j.message || 'Terjadi kesalahan, coba lagi.');
        return j;
      });
    });
  };

  /* pesan dari server (flash) dan dialog yang perlu dibuka ulang karena error validasi */
  var boot = document.getElementById('panel-boot');
  if (boot) {
    if (boot.dataset.toast) toast(boot.dataset.toast);
    if (boot.dataset.reopen) openModal(boot.dataset.reopen);
  }
})();
