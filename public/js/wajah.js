/*
 * Resto Flow — verifikasi wajah di browser (face-api.js).
 * Membaca wajah dari kamera, menjalankan tantangan gerakan acak dari server (liveness), lalu mengirim
 * angka ciri wajah (bukan foto) ke server untuk dicocokkan. Kamera hanya jalan di HTTPS atau localhost.
 */
(function () {
  var MODEL = document.currentScript.dataset.model;
  var siap = null, aliran = null;

  function muat() {
    if (!siap) {
      siap = Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(MODEL),
        faceapi.nets.faceLandmark68Net.loadFromUri(MODEL),
        faceapi.nets.faceRecognitionNet.loadFromUri(MODEL)
      ]);
    }
    return siap;
  }
  function jarak(a, b) { return Math.hypot(a.x - b.x, a.y - b.y); }
  function tengah(t) { var x = 0, y = 0; t.forEach(function (p) { x += p.x; y += p.y; }); return { x: x / t.length, y: y / t.length }; }
  function rasioMata(p) { return (jarak(p[1], p[5]) + jarak(p[2], p[4])) / (2 * jarak(p[0], p[3])); }

  /* ear = keterbukaan mata, mar = bukaan mulut, yaw = geser hidung dari tengah kedua mata (positif = menoleh ke kiri pengguna) */
  function metrik(lm) {
    var p = lm.positions, ka = p.slice(36, 42), ki = p.slice(42, 48), c1 = tengah(ka), c2 = tengah(ki);
    var mid = { x: (c1.x + c2.x) / 2, y: (c1.y + c2.y) / 2 };
    return { ear: (rasioMata(ka) + rasioMata(ki)) / 2, mar: jarak(p[62], p[66]) / jarak(p[60], p[64]), yaw: (p[30].x - mid.x) / jarak(c1, c2) };
  }
  function opsi() { return new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 }); }
  function jeda(ms) { return new Promise(function (r) { setTimeout(r, ms); }); }

  async function baca(video, denganCiri) {
    var q = faceapi.detectAllFaces(video, opsi()).withFaceLandmarks();
    if (denganCiri) { q = q.withFaceDescriptors(); }
    return q;
  }

  /* tunggu n frame wajah tunggal yang menghadap depan (dan mata terbuka bila ada patokan) */
  async function tungguDepan(video, n, dasar, lapor) {
    var batas = performance.now() + 20000, kumpul = [];
    while (performance.now() < batas) {
      var r = await baca(video, true);
      if (r.length > 1) { lapor('Hanya satu wajah yang boleh terlihat di kamera.', 'warn'); kumpul = []; }
      else if (r.length === 1) {
        var m = metrik(r[0].landmarks);
        var depan = Math.abs(m.yaw - (dasar ? dasar.yaw : 0)) < (dasar ? 0.08 : 0.25) && (!dasar || m.ear > dasar.ear * 0.8);
        if (depan) { kumpul.push({ m: m, ciri: r[0].descriptor }); if (kumpul.length >= n) { break; } }
      } else { lapor('Wajah belum terlihat. Hadapkan wajah ke kamera dengan cahaya cukup.', 'warn'); }
      await jeda(60);
    }
    if (kumpul.length < n) { throw new Error('Wajah tidak terbaca dengan jelas. Pastikan wajah terang dan menghadap kamera.'); }
    var rata = { ear: 0, mar: 0, yaw: 0 };
    kumpul.forEach(function (k) { rata.ear += k.m.ear / n; rata.mar += k.m.mar / n; rata.yaw += k.m.yaw / n; });
    rata.ciri = kumpul[kumpul.length - 1].ciri;
    return rata;
  }

  /* tunggu gerakan; kembalikan nilai yang dicek ulang server */
  async function tungguGerakan(video, aksi, dasar) {
    var batas = performance.now() + 12000, tutup = false, minMata = 9;
    while (performance.now() < batas) {
      var r = await baca(video, false);
      if (r.length === 1) {
        var m = metrik(r[0].landmarks);
        if (aksi === 'kedip') {
          var rasio = m.ear / dasar.ear; minMata = Math.min(minMata, rasio);
          if (rasio < 0.7) { tutup = true; } else if (tutup && rasio > 0.85) { return +minMata.toFixed(3); }
        } else if (aksi === 'buka_mulut' && m.mar - dasar.mar >= 0.3) { return +(m.mar - dasar.mar).toFixed(3); }
        else if (aksi === 'noleh_kiri' && m.yaw - dasar.yaw >= 0.18) { return +(m.yaw - dasar.yaw).toFixed(3); }
        else if (aksi === 'noleh_kanan' && m.yaw - dasar.yaw <= -0.18) { return +(m.yaw - dasar.yaw).toFixed(3); }
      }
      await jeda(30);
    }
    return null;
  }

  function berhenti() {
    if (aliran) { aliran.getTracks().forEach(function (t) { t.stop(); }); aliran = null; }
  }

  /* o = { video, tantangan: {nonce, langkah:[{aksi, teks}]}, sampel, lapor(teks, jenis), langkahKe(i) } */
  async function jalankan(o) {
    if (!window.isSecureContext || !navigator.mediaDevices) {
      throw new Error('Kamera hanya bisa dipakai lewat HTTPS atau http://localhost. Minta admin memasang HTTPS di server.');
    }
    o.lapor('Memuat pengenal wajah…');
    await muat();
    try {
      aliran = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } }, audio: false });
    } catch (e) {
      throw new Error(e && e.name === 'NotAllowedError'
        ? 'Akses kamera ditolak. Izinkan kamera untuk situs ini (ikon gembok/kamera di samping alamat), lalu coba lagi.'
        : 'Kamera tidak ditemukan atau sedang dipakai aplikasi lain.');
    }
    try {
      o.video.srcObject = aliran; await o.video.play();
      o.lapor('Hadapkan wajah ke kamera…');
      var dasar = await tungguDepan(o.video, 4, null, o.lapor), ciri = [dasar.ciri], langkah = [];
      for (var i = 0; i < o.tantangan.langkah.length; i++) {
        var l = o.tantangan.langkah[i], mulai = performance.now();
        if (o.langkahKe) { o.langkahKe(i); }
        o.lapor(l.teks + '…', 'aksi');
        var nilai = await tungguGerakan(o.video, l.aksi, dasar);
        if (nilai === null) { throw new Error('Gerakan "' + l.teks + '" tidak terdeteksi. Lakukan perlahan dan jelas, lalu coba lagi.'); }
        langkah.push({ aksi: l.aksi, durasi_ms: Math.round(performance.now() - mulai), nilai: nilai });
        o.lapor('Bagus! Hadap ke depan lagi…');
        ciri.push((await tungguDepan(o.video, 2, dasar, o.lapor)).ciri);
      }
      while (ciri.length < (o.sampel || 3)) { ciri.push((await tungguDepan(o.video, 2, dasar, o.lapor)).ciri); }
      if (o.langkahKe) { o.langkahKe(o.tantangan.langkah.length); }
      return { nonce: o.tantangan.nonce, langkah: langkah, ciri: ciri.map(function (c) { return Array.from(c).map(function (v) { return +v.toFixed(6); }); }) };
    } finally {
      berhenti();
    }
  }

  window.WajahResto = { jalankan: jalankan, berhenti: berhenti, muat: muat };
})();
