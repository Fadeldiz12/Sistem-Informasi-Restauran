@extends('layouts.panel')

@use('App\Models\MutasiBahanBaku')
@use('App\Services\BahanBakuService')

@section('title', 'Bahan Baku')
@section('crumb', 'Dapur / Bahan Baku')
@section('heading', 'Kelola Bahan Baku')
@section('sub', 'Catat stok masuk dan pemakaian; sistem menghitung ROP dan memberi notifikasi stok kritis')
@section('delete-title', 'Hapus bahan baku ini?')
@section('delete-text', 'Bahan diarsipkan: hilang dari daftar, tetapi riwayat stoknya tetap tersimpan. Bahan yang masih dipakai resep tidak bisa dihapus.')

@php
  $a = fn ($n) => BahanBakuService::angka($n);
  $bEdit = old('_modal') === 'mBahan' ? old('_id') : null;
  $mutasiLama = old('_modal') === 'mMutasi';
@endphp

@push('styles')
<style>
  .stok-cell { display: flex; gap: 10px; align-items: center; }
  .stok-cell b { min-width: 44px; }
  tr.kritis td:first-child b { color: var(--m); }
  /* nama bahan yang sangat panjang dibungkus supaya kolom aksi tetap terlihat (QA A3-#21) */
  td:first-child b { overflow-wrap: anywhere; word-break: break-word; }
  td:first-child { max-width: 320px; }
  tr.kritis { background: rgba(151, 23, 58, .05); }
  .riwayat { display: grid; gap: 0; }
  .riwayat div { display: flex; gap: 12px; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--m15); font-size: 13.5px; }
  .riwayat .av { width: 32px; height: 32px; }
  .riwayat b { font: 600 13.5px var(--f-title); }
  .riwayat .jml { margin-left: auto; font: 700 14px var(--f-title); white-space: nowrap; }
  .pv-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-top: 10px; }
  .pv-grid small { font-size: 11.5px; opacity: .7; display: block; }
  .pv-grid b { font: 700 19px var(--f-title); }
  @media (max-width: 520px) { .pv-grid { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

@section('content')
  @if ($kritis->isNotEmpty())
    <div class="card f" style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;padding:14px 20px;border-radius:18px;margin-bottom:18px"><svg class="ic" style="width:22px;height:22px"><use href="#i-alert"/></svg><span style="flex:1;min-width:220px;font:600 14px var(--f-accent)">Notifikasi stok kritis: {{ $kritis->pluck('nama_bahan')->join(', ', ' dan ') }} berada pada atau di bawah ROP. Segera lakukan pemesanan ulang.</span></div>
  @endif

  <div class="grid g4">
    <div class="card stat f"><div class="top"><span class="eyebrow">Total bahan baku</span><span class="ico"><svg class="ic"><use href="#i-box"/></svg></span></div><div class="v">{{ $bahan->count() }}</div><div class="t">Terdaftar di dapur</div></div>
    <div class="card stat b"><div class="top"><span class="eyebrow">Stok kritis</span><span class="ico"><svg class="ic"><use href="#i-alert"/></svg></span></div><div class="v">{{ $kritis->count() }}</div><div class="t">Stok ≤ ROP</div></div>
    <div class="card stat "><div class="top"><span class="eyebrow">Mutasi hari ini</span><span class="ico"><svg class="ic"><use href="#i-refresh"/></svg></span></div><div class="v">{{ $masukHariIni + $pakaiHariIni }}</div><div class="t">Masuk {{ $masukHariIni }} · Pemakaian {{ $pakaiHariIni }}</div></div>
    <div class="stamp-out"><div class="stamp" style="padding:20px 22px;height:100%"><div class="eyebrow">Rumus reorder point</div><div style="font:700 clamp(17px,1.5vw,22px) var(--f-title);margin:8px 0 4px;white-space:nowrap">ROP = (d × L) + SS</div>
      <div style="font-size:12.5px;opacity:.72;line-height:1.5">d = rata-rata pemakaian {{ $hariRata }} hari terakhir · L = lead time · SS = safety stock</div></div></div>
  </div>

  <div class="card" style="margin-top:18px"><div class="card-head">
      <div class="chips" data-filter-for="tbB"><button class="on" data-f="all">Semua</button><button data-f="kritis">Kritis<span>{{ $kritis->count() }}</span></button><button data-f="aman">Aman<span>{{ $bahan->count() - $kritis->count() }}</span></button></div>
      <div style="display:flex;gap:10px;flex-wrap:wrap"><button class="btn o" id="btnMutasi" @disabled($bahan->isEmpty())><svg class="ic"><use href="#i-refresh"/></svg>Catat Mutasi Stok</button><button class="btn" id="btnTambah"><svg class="ic"><use href="#i-plus"/></svg>Tambah Bahan Baku</button></div></div>
    <div class="tbl-wrap"><table class="tbl-stack"><thead><tr><th>Bahan baku</th><th>Stok</th><th>Parameter ROP</th><th>ROP</th><th>Status</th><th>Aksi</th></tr></thead><tbody id="tbB">
      @forelse ($bahan as $b)
        @php $k = $b->isStokKritis(); $persen = min(100, round((float) $b->stok / max((float) $b->rop * 2, 0.01) * 100)); @endphp
        <tr data-f="{{ $k ? 'kritis' : 'aman' }}" @class(['kritis' => $k]) data-id="{{ $b->id }}" data-url="{{ route('dapur.bahan-baku.update', $b) }}" data-nama="{{ $b->nama_bahan }}" data-satuan="{{ $b->satuan }}"
            data-stok="{{ (float) $b->stok }}" data-d="{{ (float) $b->rata_pemakaian }}" data-l="{{ $b->lead_time }}" data-ss="{{ (float) $b->safety_stock }}">
          <td><b>{{ $b->nama_bahan }}</b><div style="font-size:12px;opacity:.6">{{ $b->satuan }}</div></td>
          <td><div class="stok-cell"><b>{{ $a($b->stok) }}</b><div class="bar dark ropbar" style="width:90px"><i style="width:{{ $persen }}%"></i></div></div></td>
          <td style="font-size:13px">d {{ $a($b->rata_pemakaian) }} · L {{ $b->lead_time }} hr · SS {{ $a($b->safety_stock) }}</td>
          <td><span @class(['kt', 'm' => $k])>{{ $a($b->rop) }}</span></td>
          <td>@if ($k)<span class="pill m">Kritis · pesan ulang</span>@else<span class="pill b">Aman</span>@endif</td>
          <td><div class="acts"><button class="ab js-mutasi" title="Catat mutasi"><svg class="ic"><use href="#i-refresh"/></svg></button><button class="ab js-edit" title="Ubah"><svg class="ic"><use href="#i-edit"/></svg></button><button class="ab f js-del" data-url="{{ route('dapur.bahan-baku.destroy', $b) }}" data-name="{{ $b->nama_bahan }}" title="Hapus"><svg class="ic"><use href="#i-trash"/></svg></button></div></td>
        </tr>
      @empty
        <tr><td colspan="6"><div class="empty">Belum ada bahan baku. Tambahkan bahan baku pertama.</div></td></tr>
      @endforelse
    </tbody></table></div>
    <div class="note" style="margin-top:16px"><svg class="ic"><use href="#i-info"/></svg><span>Pemakaian bahan tercatat otomatis saat tiket ditekan <b>Mulai Masak</b> (takaran resep × jumlah porsi; resep diatur admin di menu Kategori &amp; Menu). Catat Mutasi Stok dipakai untuk barang masuk, bahan rusak/terbuang, atau koreksi stok.</span></div></div>

  <div class="card" style="margin-top:18px"><div class="card-head" style="margin-bottom:4px"><div><h2>Riwayat Mutasi</h2><p>12 mutasi stok terbaru</p></div></div>
    <div class="riwayat">
      @forelse ($riwayat as $m)
        @php $masuk = $m->jenis === MutasiBahanBaku::JENIS_MASUK; @endphp
        <div><span @class(['av', 'm' => ! $masuk])><svg class="ic" style="width:15px;height:15px"><use href="#{{ $masuk ? 'i-download' : 'i-upload' }}"/></svg></span>
          <span style="min-width:0"><b>{{ $m->bahanBaku->nama_bahan }}</b><br><span style="font-size:12px;opacity:.7">{{ $masuk ? 'Stok masuk' : 'Pemakaian' }} · {{ $m->tanggal->translatedFormat('d M Y') }}</span></span>
          <span class="jml">{{ $masuk ? '+' : '−' }}{{ $a($m->jumlah) }} {{ $m->bahanBaku->satuan }}</span></div>
      @empty
        <p class="empty" style="padding:20px 8px">Belum ada mutasi stok.</p>
      @endforelse
    </div></div>
@endsection

@push('modals')
<div class="ovl" id="mBahan"><form class="modal" method="post" id="fBahan" action="{{ $bEdit ? route('dapur.bahan-baku.update', $bEdit) : route('dapur.bahan-baku.store') }}">
  @csrf
  <input type="hidden" name="_method" value="PUT" @disabled(! $bEdit)>
  <input type="hidden" name="_modal" value="mBahan">
  <input type="hidden" name="_id" value="{{ $bEdit }}">
  <div class="mh"><div><div class="eyebrow" style="opacity:.7">Bahan baku</div><h3 id="bTitle">{{ $bEdit ? 'Ubah Bahan Baku' : 'Tambah Bahan Baku' }}</h3></div><button type="button" class="icon-btn" data-close><svg class="ic"><use href="#i-x"/></svg></button></div>
  <div class="mb">
    @include('partials.panel.form-errors', ['modal' => 'mBahan'])
    <div class="fgrid">
      <div class="field"><label for="bNama">Nama bahan</label><div class="in"><svg class="ic"><use href="#i-box"/></svg><input type="text" name="nama_bahan" id="bNama" value="{{ old('nama_bahan') }}" maxlength="100" placeholder="Contoh: Keju Cheddar" required></div></div>
      <div class="field"><label for="bSat">Satuan</label><div class="in"><svg class="ic"><use href="#i-tag"/></svg><select name="satuan" id="bSat">@foreach ($satuan as $s)<option @selected(old('satuan', 'kg') === $s)>{{ $s }}</option>@endforeach</select></div></div>
      <div class="field" id="bStokWrap" @if ($bEdit) hidden @endif><label for="bStok">Stok awal</label><div class="in"><svg class="ic"><use href="#i-box"/></svg><input type="number" name="stok" id="bStok" value="{{ old('stok', 0) }}" step="0.01" min="0" @disabled($bEdit)></div></div>
      <div class="field"><label for="bL">Lead time (L)</label><div class="in"><svg class="ic"><use href="#i-clock"/></svg><input type="number" name="lead_time" id="bL" value="{{ old('lead_time', 2) }}" min="0" max="365" required><span class="suf">hari</span></div></div>
      <div class="field"><label for="bS">Safety stock (SS)</label><div class="in"><svg class="ic"><use href="#i-lock"/></svg><input type="number" name="safety_stock" id="bS" value="{{ old('safety_stock', 1) }}" step="0.01" min="0" required></div></div>
      <div class="field"><label>Rata-rata pemakaian/hari (d)</label><div class="in" style="opacity:.75"><svg class="ic"><use href="#i-chart"/></svg><input id="bD" value="0" readonly><span class="suf">otomatis</span></div></div>
    </div>
    <div class="mods" style="margin-top:18px"><div class="mod"><div style="display:flex;justify-content:space-between;align-items:center;gap:12px"><div><div class="eyebrow" style="opacity:.8">ROP dihitung otomatis</div><div id="bF" style="font-size:13.5px;margin-top:4px">(0 × 2) + 1</div></div><b id="bR" style="font:700 30px var(--f-title)">1</b></div></div></div>
    <p style="font-size:12.5px;opacity:.7;margin-top:10px" id="bNote">d dihitung dari pemakaian {{ $hariRata }} hari terakhir. Untuk bahan baru, d = 0 sampai ada catatan pemakaian.</p>
  </div>
  <div class="mf"><button type="button" class="btn o" data-close>Batal</button><button class="btn" type="submit"><svg class="ic"><use href="#i-check"/></svg>Simpan</button></div></form></div>

<div class="ovl" id="mMutasi"><form class="modal" method="post" id="fMutasi" action="{{ route('dapur.mutasi.store') }}">
  @csrf
  <input type="hidden" name="_modal" value="mMutasi">
  <input type="hidden" name="jenis" id="mJenis" value="{{ old('jenis', MutasiBahanBaku::JENIS_MASUK) }}">
  <div class="mh"><div><div class="eyebrow" style="opacity:.7">Bahan baku</div><h3>Catat Mutasi Stok</h3></div><button type="button" class="icon-btn" data-close><svg class="ic"><use href="#i-x"/></svg></button></div>
  <div class="mb">
    @include('partials.panel.form-errors', ['modal' => 'mMutasi'])
    <div class="fgrid">
      <div class="field full"><label for="mBahanSel">Bahan baku</label><div class="in"><svg class="ic"><use href="#i-box"/></svg><select name="id_bahan" id="mBahanSel">
        @foreach ($bahan as $b)<option value="{{ $b->id }}" @selected((string) old('id_bahan') === (string) $b->id)>{{ $b->nama_bahan }} ({{ $a($b->stok) }} {{ $b->satuan }})</option>@endforeach
      </select></div></div>
      <div class="field full"><label>Jenis mutasi</label><div class="seg" id="mSeg" data-input="mJenis">
        <button type="button" data-value="masuk" @class(['on' => old('jenis', 'masuk') === 'masuk'])><svg class="ic" style="width:15px;height:15px"><use href="#i-download"/></svg> Stok masuk</button>
        <button type="button" data-value="pemakaian" @class(['on' => old('jenis') === 'pemakaian'])><svg class="ic" style="width:15px;height:15px"><use href="#i-upload"/></svg> Pemakaian</button></div></div>
      <div class="field"><label for="mJml">Jumlah</label><div class="in"><svg class="ic"><use href="#i-plus"/></svg><input type="number" name="jumlah" id="mJml" value="{{ old('jumlah') }}" step="0.01" min="0.01" required><span class="suf" id="mSat">kg</span></div></div>
      <div class="field"><label for="mTgl">Tanggal</label><div class="in"><svg class="ic"><use href="#i-cal"/></svg><input type="date" name="tanggal" id="mTgl" value="{{ old('tanggal', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required></div></div>
    </div>
    <div class="stamp-out" style="margin-top:18px"><div class="stamp" style="padding:18px 22px;background:var(--bl)"><div class="eyebrow" id="pvJudul">Setelah disimpan</div>
      <div class="pv-grid"><div><small>Stok baru</small><b id="pvStok">-</b></div><div><small>d & ROP dihitung ulang</small><b id="pvRop">-</b></div><div><small>Status</small><b id="pvSt">-</b></div></div></div></div>
  </div>
  <div class="mf"><button type="button" class="btn o" data-close>Batal</button><button class="btn" type="submit"><svg class="ic"><use href="#i-check"/></svg>Simpan</button></div></form></div>
@endpush

@push('scripts')
<script>
  (function () {
    var hari = {{ $hariRata }};
    var fmt = function (n) { return (Math.round(n * 100) / 100).toLocaleString('id-ID'); };
    var row = function (id) { return document.querySelector('#tbB tr[data-id="' + id + '"]'); };

    /* ---------- tambah / ubah bahan */
    var fB = document.getElementById('fBahan'), dNow = 0;
    function rop() {
      var l = +bL.value || 0, s = +bS.value || 0, r = dNow * l + s;
      bD.value = fmt(dNow); bF.textContent = '(' + fmt(dNow) + ' × ' + l + ') + ' + fmt(s); bR.textContent = fmt(r);
    }
    function modeBahan(tr) {
      var e = fB.querySelector('.ferr'); if (e) e.remove();
      var edit = !!tr;
      fB.action = edit ? tr.dataset.url : @json(route('dapur.bahan-baku.store'));
      fB.querySelector('[name=_method]').disabled = !edit; fB.querySelector('[name=_id]').value = edit ? tr.dataset.id : '';
      bTitle.textContent = edit ? 'Ubah Bahan Baku' : 'Tambah Bahan Baku';
      bNama.value = edit ? tr.dataset.nama : ''; bSat.value = edit ? tr.dataset.satuan : 'kg';
      bL.value = edit ? tr.dataset.l : 2; bS.value = edit ? tr.dataset.ss : 1;
      bStokWrap.hidden = edit; bStok.disabled = edit; bStok.value = 0;
      dNow = edit ? +tr.dataset.d : 0; rop(); openModal('mBahan');
    }
    btnTambah.addEventListener('click', function () { modeBahan(null); });
    document.addEventListener('click', function (e) { var b = e.target.closest('.js-edit'); if (b) modeBahan(b.closest('tr')); });
    [bL, bS].forEach(function (i) { i.addEventListener('input', rop); });
    var bEdit = @json($bEdit); if (bEdit && row(bEdit)) dNow = +row(bEdit).dataset.d; rop();

    /* ---------- mutasi stok */
    function pratinjau() {
      var tr = row(mBahanSel.value); if (!tr) return;
      var stok = +tr.dataset.stok, d = +tr.dataset.d, l = +tr.dataset.l, ss = +tr.dataset.ss, j = +mJml.value || 0, pakai = mJenis.value === 'pemakaian';
      var tglOk = (Date.now() - new Date(mTgl.value).getTime()) / 864e5 < hari;
      var baru = stok + (pakai ? -j : j), dBaru = d + (pakai && tglOk ? j / hari : 0), r = dBaru * l + ss;
      mSat.textContent = tr.dataset.satuan; pvJudul.textContent = 'Setelah disimpan · ' + tr.dataset.nama;
      pvStok.textContent = fmt(baru) + ' ' + tr.dataset.satuan; pvRop.textContent = fmt(r) + ' ' + tr.dataset.satuan;
      pvSt.textContent = baru < 0 ? 'Stok kurang' : (baru <= r ? 'Kritis' : 'Aman');
    }
    function bukaMutasi(id) { if (id) mBahanSel.value = id; var e = fMutasi.querySelector('.ferr'); if (e) e.remove(); pratinjau(); openModal('mMutasi'); }
    btnMutasi.addEventListener('click', function () { bukaMutasi(); });
    document.addEventListener('click', function (e) { var b = e.target.closest('.js-mutasi'); if (b) bukaMutasi(b.closest('tr').dataset.id); });
    mSeg.addEventListener('click', function () { setTimeout(pratinjau); });
    [mBahanSel, mJml, mTgl].forEach(function (i) { i.addEventListener('input', pratinjau); i.addEventListener('change', pratinjau); });
    if (window.mBahanSel) pratinjau();
  })();
</script>
@endpush
