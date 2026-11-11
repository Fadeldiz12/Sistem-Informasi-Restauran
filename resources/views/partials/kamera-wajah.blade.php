{{--
  Kamera verifikasi wajah. Pakai di dalam <form>:
  @include('partials.kamera-wajah', ['tantangan' => [...], 'tantanganBaru' => url, 'sampel' => 3, 'setelah' => 'kirim'|'aktifkan', 'tombol' => 'Mulai'])
  Hasil (JSON) masuk ke <input name="bukti">. 'kirim' = form langsung dikirim; 'aktifkan' = tombol submit form diaktifkan.
--}}
<div class="kam" data-tantangan='@json($tantangan)' data-baru="{{ $tantanganBaru }}" data-sampel="{{ $sampel ?? 3 }}" data-setelah="{{ $setelah ?? 'kirim' }}">
  <input type="hidden" name="bukti" value="">
  <div class="kam-frame"><video playsinline muted></video><div class="kam-oval"></div><div class="kam-aksi" hidden></div></div>
  <ol class="kam-langkah">
    @foreach ($tantangan['langkah'] as $l)<li>{{ $l['teks'] }}</li>@endforeach
  </ol>
  <p class="kam-status" role="status">Tekan tombol di bawah, lalu ikuti {{ count($tantangan['langkah']) }} gerakan yang diminta. Tantangan berbeda setiap kali.</p>
  <button type="button" class="btn kam-mulai" style="width:100%">{{ $tombol ?? 'Mulai verifikasi wajah' }}</button>
</div>

@once
@push('styles')
<style>
  .kam { display: grid; gap: 12px; }
  .kam-frame { position: relative; width: 100%; max-width: 360px; margin: 0 auto; aspect-ratio: 4 / 3; border-radius: 20px; overflow: hidden; background: #1d0f13; }
  .kam-frame video { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); }
  .kam-oval { position: absolute; inset: 10% 24%; border: 3px dashed rgba(255, 255, 255, .7); border-radius: 50%; pointer-events: none; }
  .kam-aksi { position: absolute; left: 10px; right: 10px; bottom: 10px; padding: 8px 12px; border-radius: 12px; background: rgba(151, 23, 58, .92); color: #fff; font: 700 15px/1.3 system-ui, sans-serif; text-align: center; }
  .kam-langkah { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; padding: 0; margin: 0; list-style: none; counter-reset: k; }
  .kam-langkah li { counter-increment: k; padding: 6px 12px; border-radius: 999px; background: var(--pp, #eee); font: 600 13px var(--f-body, system-ui); }
  .kam-langkah li::before { content: counter(k) '. '; }
  .kam-langkah li.on { background: var(--m, #97173A); color: #fff; }
  .kam-langkah li.ok { background: var(--bl, #b6c4d6); }
  .kam-status { text-align: center; font: 500 13.5px/1.5 var(--f-body, system-ui); opacity: .85; margin: 0; }
  .kam-status.warn { color: var(--m, #97173A); font-weight: 700; opacity: 1; }
</style>
@endpush
@push('scripts')
<script src="{{ asset('vendor/face-api/face-api.js') }}?v=1.7.15"></script>
<script src="{{ asset('js/wajah.js') }}?v={{ filemtime(public_path('js/wajah.js')) }}" data-model="{{ asset('vendor/face-api/model') }}"></script>
<script>
  document.querySelectorAll('.kam').forEach(function (kam) {
    var form = kam.closest('form'), tombol = kam.querySelector('.kam-mulai'), status = kam.querySelector('.kam-status'),
        aksiEl = kam.querySelector('.kam-aksi'), daftar = kam.querySelectorAll('.kam-langkah li'), tantangan = JSON.parse(kam.dataset.tantangan);
    function lapor(t, jenis) {
      status.textContent = t; status.classList.toggle('warn', jenis === 'warn');
      aksiEl.hidden = jenis !== 'aksi'; if (jenis === 'aksi') { aksiEl.textContent = t; }
    }
    function langkahKe(i) { daftar.forEach(function (li, j) { li.classList.toggle('on', j === i); li.classList.toggle('ok', j < i); }); }
    function gambarUlang(t) {
      tantangan = t; var ol = kam.querySelector('.kam-langkah'); ol.innerHTML = '';
      t.langkah.forEach(function (l) { var li = document.createElement('li'); li.textContent = l.teks; ol.appendChild(li); });
      daftar = ol.querySelectorAll('li');
    }
    tombol.addEventListener('click', function () {
      tombol.disabled = true; tombol.textContent = 'Kamera aktif…';
      WajahResto.jalankan({ video: kam.querySelector('video'), tantangan: tantangan, sampel: +kam.dataset.sampel, lapor: lapor, langkahKe: langkahKe })
        .then(function (bukti) {
          kam.querySelector('input[name=bukti]').value = JSON.stringify(bukti);
          if (kam.dataset.setelah === 'kirim') { lapor('Wajah terbaca, memeriksa…'); form.submit(); return; }
          lapor('Wajah terbaca. Lanjutkan isi form lalu tekan tombol kirim.');
          tombol.textContent = 'Wajah terbaca ✓';
          var kirim = form.querySelector('[type=submit]'); if (kirim) { kirim.disabled = false; }
        })
        .catch(function (e) {
          lapor(e.message || 'Verifikasi wajah gagal. Coba lagi.', 'warn');
          tombol.disabled = false; tombol.textContent = 'Coba lagi'; langkahKe(-1);
          var t = form.querySelector('input[name=_token]');
          fetch(kam.dataset.baru, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': t ? t.value : '' } })
            .then(function (r) { return r.ok ? r.json() : null; }).then(function (j) { if (j) { gambarUlang(j); } });
        });
    });
  });
</script>
@endpush
@endonce
