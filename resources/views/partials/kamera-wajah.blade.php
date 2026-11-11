{{--
  Kamera verifikasi wajah (FaceGuard: MediaPipe Face Landmarker + face-api.js). Pakai di dalam <form>:
  @include('partials.kamera-wajah', ['tantanganBaru' => url POST tantangan, 'setelah' => 'kirim'|'aktifkan', 'tombol' => 'Mulai'])
  Hasil (JSON tanpa foto) masuk ke <input name="bukti">. 'kirim' = form langsung dikirim; 'aktifkan' = tombol submit diaktifkan.
--}}
<div class="kam" data-baru="{{ $tantanganBaru }}" data-setelah="{{ $setelah ?? 'kirim' }}">
  <input type="hidden" name="bukti" value="">
  <div class="kam-face">(Wajah)</div>
  <p class="kam-status" role="status">Tekan tombol di bawah. Kamu akan diminta menghadap lurus, layar berkedip warna sebentar, lalu 3 gerakan acak.</p>
  <ol class="kam-langkah"></ol>
  <button type="button" class="btn kam-mulai" style="width:100%">{{ $tombol ?? 'Mulai verifikasi wajah' }}</button>
</div>

@once
@push('styles')
<style>
  .kam { display: grid; gap: 12px; }
  .kam-face { position: relative; width: min(280px, 72vw); aspect-ratio: 1; margin: 0 auto; border-radius: 50%; overflow: hidden; background: #1d0f13; color: rgba(255,255,255,.6);
    display: grid; place-items: center; font: 600 14px var(--f-body, system-ui); border: 4px solid var(--pp, #eee); }
  .kam-face.is-live { border-color: var(--bl, #b6c4d6); }
  .kam-face.is-scanning { border-color: var(--m, #97173A); box-shadow: 0 0 0 6px rgba(151, 23, 58, .15); }
  .kam-face video { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); }
  .kam-face .face__overlay { position: absolute; inset: 0; width: 100%; height: 100%; transform: scaleX(-1); }
  .kam-status { text-align: center; font: 600 13.5px/1.5 var(--f-body, system-ui); margin: 0; min-height: 40px; }
  .kam-status.is-error { color: var(--m, #97173A); }
  .kam-status.is-action { color: #fff; background: var(--m, #97173A); border-radius: 12px; padding: 8px 12px; font-size: 15px; }
  .kam-langkah { display: grid; gap: 6px; padding: 0; margin: 0; list-style: none; font: 600 13px var(--f-body, system-ui); }
  .kam-langkah li { display: flex; gap: 8px; align-items: center; opacity: .55; }
  .kam-langkah li.is-active { opacity: 1; color: var(--m, #97173A); }
  .kam-langkah li.is-done { opacity: .9; }
  .kam-langkah .liveness__dot { width: 12px; height: 12px; border-radius: 50%; border: 2px solid currentColor; flex: none; }
  .kam-langkah li.is-done .liveness__dot { background: currentColor; }
  /* layar berkedip warna untuk cek pantulan cahaya di wajah (anti layar HP / video) */
  .flash-overlay { position: fixed; inset: 0; z-index: 99999; display: flex; align-items: flex-end; justify-content: center; padding: 40px 20px; }
  .flash-overlay p { background: rgba(0,0,0,.55); color: #fff; padding: 10px 16px; border-radius: 12px; font: 600 14px/1.5 system-ui, sans-serif; text-align: center; }
</style>
@endpush
@push('scripts')
<script>window.AppConfig = @json(\App\Services\WajahService::configBrowser());</script>
<script src="{{ asset('vendor/face-api/face-api.js') }}?v=1.7.15"></script>
<script src="{{ asset('vendor/mediapipe/vision_bundle.js') }}"></script>
<script src="{{ asset('js/face-guard.js') }}?v={{ filemtime(public_path('js/face-guard.js')) }}"></script>
<script>
  document.querySelectorAll('.kam').forEach(function (kam) {
    var form = kam.closest('form'), tombol = kam.querySelector('.kam-mulai'), statusEl = kam.querySelector('.kam-status'),
        faceEl = kam.querySelector('.kam-face'), stepsEl = kam.querySelector('.kam-langkah'), jalan = false;
    function status(teks, jenis) { statusEl.textContent = teks; statusEl.className = 'kam-status' + (jenis ? ' is-' + jenis : ''); }
    function token() { var t = form.querySelector('input[name=_token]'); return t ? t.value : ''; }

    tombol.addEventListener('click', async function () {
      if (jalan) { return; }
      jalan = true; tombol.disabled = true; tombol.textContent = 'Menyiapkan kamera…'; stepsEl.innerHTML = '';
      status('Memuat model pengenalan wajah…');
      try {
        if (!window.isSecureContext || !navigator.mediaDevices) {
          throw new Error('Kamera hanya bisa dipakai lewat HTTPS atau http://localhost. Minta admin memasang HTTPS di server.');
        }
        var r = await fetch(kam.dataset.baru, { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token() } });
        if (!r.ok) { throw new Error(r.status === 419 ? 'Waktu verifikasi habis. Muat ulang halaman.' : 'Gagal meminta tantangan dari server. Muat ulang halaman.'); }
        var tantangan = await r.json();

        await FaceGuard.start(faceEl);
        tombol.textContent = 'Verifikasi berlangsung…'; faceEl.classList.add('is-scanning');
        var hasil = await FaceGuard.verify({ challenge: tantangan, samples: tantangan.samples, onStatus: status, stepsEl: stepsEl });
        delete hasil.image; // foto tidak dikirim & tidak disimpan
        FaceGuard.stop(faceEl);

        kam.querySelector('input[name=bukti]').value = JSON.stringify(hasil);
        if (kam.dataset.setelah === 'kirim') { status('Memeriksa data wajah di server…', 'ok'); form.submit(); return; }
        status('Wajah terverifikasi di perangkat. Lanjutkan isi form lalu tekan tombol kirim.', 'ok');
        tombol.textContent = 'Wajah terbaca ✓';
        var kirim = form.querySelector('[type=submit]'); if (kirim) { kirim.disabled = false; }
      } catch (e) {
        FaceGuard.stop && FaceGuard.stop(faceEl);
        status(e.message || 'Verifikasi wajah gagal. Silakan ulangi.', 'error');
        tombol.disabled = false; tombol.textContent = 'Ulangi verifikasi';
        jalan = false;
      }
    });
  });
</script>
@endpush
@endonce
