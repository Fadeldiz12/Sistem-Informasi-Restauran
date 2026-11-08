@extends('layouts.kurir', ['tab' => 'status'])

@section('title', 'Status Pengiriman')

@php $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.'); @endphp

@section('content')
  <h1 style="font:700 24px var(--f-title)">Status Pengiriman</h1><p style="font-size:13px;opacity:.7;margin-top:4px">Pesanan yang sedang kamu antar</p>

  @if (! $kirim)
    <div class="card soft" style="margin-top:16px"><div class="empty"><svg class="ic" style="width:30px;height:30px;margin:0 auto 10px"><use href="#i-truck"/></svg>Tidak ada pesanan yang sedang diantar.<br><a class="btn sm" href="{{ route('kurir.tugas') }}" style="margin-top:14px">Lihat tugas pengiriman</a></div></div>
  @else
    @php $p = $kirim->pesanan; @endphp
    @if ($aktif->count() > 1)
      <div class="switch" style="margin-top:14px">@foreach ($aktif as $a)<a @class(['on' => $a->id === $kirim->id]) href="{{ route('kurir.status', ['pengiriman' => $a]) }}">{{ $a->pesanan->kode() }}</a>@endforeach</div>
    @endif
    <div class="stamp-out" style="margin-top:14px"><div class="stamp" style="padding:18px 20px"><div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap"><b style="font:700 18px var(--f-title)">{{ $p->kode() }} · {{ $p->namaPemesan() }}</b><span class="pill m">Dalam Perjalanan</span></div>
      <div style="font-size:12.5px;opacity:.75;margin-top:6px">{{ $kirim->alamat }}{{ $kirim->patokan ? ' · Patokan: '.$kirim->patokan : '' }}</div>
      <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
        <a class="btn o sm" href="{{ route('kurir.tugas.show', $kirim) }}"><svg class="ic" style="width:15px;height:15px"><use href="#i-receipt"/></svg>Detail</a>
        @php $hpPenerima = $kirim->no_hp_penerima ?: $p->pelanggan?->no_hp; @endphp
        @if ($hpPenerima)<a class="btn o sm" href="tel:{{ preg_replace('/[^\d+]/', '', $hpPenerima) }}"><svg class="ic" style="width:15px;height:15px"><use href="#i-phone"/></svg>Telepon</a>@endif
        <a class="btn o sm" href="{{ \App\Models\ZonaPengiriman::urlMaps($kirim->lat !== null ? (float) $kirim->lat : null, $kirim->lng !== null ? (float) $kirim->lng : null, $kirim->alamat) }}" target="_blank" rel="noopener"><svg class="ic" style="width:15px;height:15px"><use href="#i-nav"/></svg>Maps</a></div></div></div>

    <form method="post" action="{{ route('kurir.tugas.diterima', $kirim) }}" enctype="multipart/form-data" id="fStatus">@csrf
      <div class="eyebrow" style="margin:20px 0 4px">Sudah sampai? Pilih Diterima lalu unggah bukti</div>
      @php $pilihTerima = old('uang_diterima') !== null || $errors->any(); @endphp
      <div id="opts">
        <button type="button" @class(['opt', 'on' => ! $pilihTerima]) data-s="jalan"><span class="rad"></span><div><b style="font:700 15px var(--f-title)">Dalam Perjalanan</b><div style="font-size:12px;opacity:.75">Pesanan sedang menuju pelanggan · sejak {{ ($kirim->berangkat_pada ?? $kirim->updated_at)->format('H:i') }}</div></div><span class="pill b now" style="font-size:10px">Saat ini</span></button>
        <button type="button" @class(['opt', 'on' => $pilihTerima]) data-s="terima"><span class="rad"></span><div><b style="font:700 15px var(--f-title)">Diterima pelanggan</b><div style="font-size:12px;opacity:.8">Wajib unggah foto bukti pengiriman</div></div></button>
      </div>

      <div @class(['need', 'on' => $pilihTerima]) id="need">
        <div class="field" style="margin-top:16px"><label for="bukti">Foto bukti pengiriman</label>
          <label class="drop" for="bukti" id="dropBox" style="flex-direction:column;text-align:center;padding:20px"><svg class="ic" style="width:28px;height:28px"><use href="#i-image"/></svg><b style="font:600 14px var(--f-title)">Ambil / unggah foto</b><span style="font-size:12px;opacity:.7">JPG / PNG · foto dari kamera otomatis dikecilkan</span></label>
          <input type="file" name="bukti" id="bukti" accept="image/jpeg,image/png,image/webp" hidden>
          <div class="pv-box" id="pvBox" hidden><img id="pvImg" alt="Pratinjau foto bukti"><div style="flex:1;min-width:0"><b id="pvName" style="font:600 13.5px var(--f-title);display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></b><div id="pvInfo" style="font-size:12px;opacity:.7"></div></div><button type="button" class="ab" id="pvDel" aria-label="Hapus foto"><svg class="ic"><use href="#i-x"/></svg></button></div>
        </div>
        @if ($tagihTunai)
          <label class="cek"><input type="checkbox" name="uang_diterima" value="1" id="uang" @checked(old('uang_diterima'))><span>Saya sudah menerima uang tunai <b>{{ $rp($p->total) }}</b> dari pelanggan.<small style="display:block;font-weight:500;opacity:.8;margin-top:2px">Setor ke kasir setelah kembali ke restoran.</small></span></label>
        @endif
      </div>

      <button class="btn lg" type="submit" style="width:100%;margin-top:18px" id="save"><svg class="ic"><use href="#i-check"/></svg>Simpan Status</button>
      <p style="font-size:12px;opacity:.6;text-align:center;margin-top:10px">Status Diterima mengubah pesanan menjadi Selesai dan tampil di halaman lacak pelanggan.</p>
    </form>
  @endif
@endsection

@push('scripts')
@if ($kirim)
<script>
  (function () {
    var form = document.getElementById('fStatus'), input = document.getElementById('bukti'), opts = document.getElementById('opts'), need = document.getElementById('need');
    var MAKS = 1600, siap = false;
    function ukuran(b) { return b > 1048576 ? (b / 1048576).toFixed(1).replace('.', ',') + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB'; }
    function tampil(file) {
      pvImg.src = URL.createObjectURL(file); pvName.textContent = file.name;
      pvInfo.textContent = ukuran(file.size) + ' · ' + new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
      pvBox.hidden = false; dropBox.hidden = true; siap = true;
    }
    // foto kamera ponsel biasanya besar: kecilkan ke maks 1600 px (JPEG) sebelum diunggah
    function kecilkan(file) {
      return new Promise(function (ok) {
        if (!/^image\//.test(file.type) || !window.createImageBitmap) return ok(file);
        createImageBitmap(file).then(function (bmp) {
          var s = Math.min(1, MAKS / Math.max(bmp.width, bmp.height));
          if (s === 1 && file.size <= 1.5 * 1048576) return ok(file);
          var c = document.createElement('canvas'); c.width = Math.round(bmp.width * s); c.height = Math.round(bmp.height * s);
          c.getContext('2d').drawImage(bmp, 0, 0, c.width, c.height);
          c.toBlob(function (blob) { ok(blob ? new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }) : file); }, 'image/jpeg', .82);
        }).catch(function () { ok(file); });
      });
    }
    input.addEventListener('change', function () {
      var f = input.files[0]; if (!f) return;
      // file bukan gambar (mis. dokumen dari menu File) langsung ditolak (QA A4-#11)
      if (!/^image\//.test(f.type)) { toast('File ini bukan foto. Pilih foto JPG atau PNG.', 'warn'); input.value = ''; return; }
      kecilkan(f).then(function (hasil) {
        if (hasil !== f && window.DataTransfer) { try { var dt = new DataTransfer(); dt.items.add(hasil); input.files = dt.files; } catch (e) { hasil = f; } }
        if (hasil.size > 5 * 1048576) { toast('Ukuran foto maksimal 5 MB', 'warn'); input.value = ''; return; }
        tampil(hasil);
      });
    });
    pvDel.addEventListener('click', function () { input.value = ''; pvBox.hidden = true; dropBox.hidden = false; siap = false; });
    opts.addEventListener('click', function (e) {
      var b = e.target.closest('.opt'); if (!b) return;
      opts.querySelectorAll('.opt').forEach(function (x) { x.classList.toggle('on', x === b); });
      need.classList.toggle('on', b.dataset.s === 'terima');
    });
    function sorot(el) { el.classList.remove('perlu'); void el.offsetWidth; el.classList.add('perlu'); el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
    form.addEventListener('submit', function (e) {
      if (!need.classList.contains('on')) { e.preventDefault(); toast('Pilih "Diterima pelanggan" bila pesanan sudah sampai', 'warn'); sorot(opts); return; }
      if (!siap) { e.preventDefault(); toast('Unggah foto bukti pengiriman dulu', 'warn'); sorot(dropBox); return; }
      var u = document.getElementById('uang');
      if (u && !u.checked) { e.preventDefault(); toast('Centang konfirmasi uang tunai sudah diterima', 'warn'); sorot(u.closest('.cek')); }
    });
  })();
</script>
@endif
@endpush
