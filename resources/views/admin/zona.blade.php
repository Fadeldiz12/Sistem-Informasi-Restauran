@extends('layouts.panel')

@section('title', 'Zona & Tarif')
@section('crumb', 'Admin / Zona & Tarif')
@section('heading', 'Zona & Tarif Pengiriman')
@section('sub', 'Atur zona, radius, dan tarif per km untuk pesanan Delivery')
@section('delete-title', 'Hapus zona ini?')

@php
  $editId = old('_modal') === 'mZona' ? old('_id') : null;
  $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
  $km = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
  // jarak minimum tiap zona = radius zona sebelumnya
  $baris = $zona->values()->map(fn ($z, $i) => [
    'z' => $z,
    'min' => $i === 0 ? 0 : (float) $zona[$i - 1]->radius_km,
    'max' => (float) $z->radius_km,
    'ongkir' => $z->ongkir(),
  ]);
  $maxRadius = (float) ($zona->max('radius_km') ?: 1);
  $simulasi = $baris->map(fn ($b) => ['nama' => $b['z']->nama_zona, 'min' => $b['min'], 'max' => $b['max'], 'ongkir' => $b['ongkir']])->values();
  $warna = ['#97173A', '#F8F5E6', '#B2C2D2'];
  // nama pendek untuk avatar & peta, contoh: "Zona A" -> "A"
  $singkat = fn ($z) => trim(preg_replace('/^zona\s*/i', '', $z->nama_zona)) ?: $z->nama_zona;
@endphp

@section('content')
  @if ($zona->isNotEmpty())
    <div class="grid g3">
      @foreach ($baris as $i => $b)
        <div @class(['card stat', 'f' => $i % 3 === 1, 'b' => $i % 3 === 2])><div class="top"><span class="eyebrow">{{ $b['z']->nama_zona }} · {{ $km($b['min']) }} – {{ $km($b['max']) }} km</span><span class="ico"><svg class="ic"><use href="#i-map"/></svg></span></div><div class="v">{{ $rp($b['ongkir']) }}</div><div class="t">{{ $b['z']->kirim_bulan_ini }} pengiriman bulan ini</div></div>
      @endforeach
    </div>
  @endif

  <div class="grid" style="margin-top:18px">
    <div class="card"><div class="card-head"><div><h2>Daftar Zona Pengiriman</h2><p>Ongkir = radius zona × tarif per km</p></div><button class="btn" id="btnAdd"><svg class="ic"><use href="#i-plus"/></svg>Tambah Zona</button></div>
      <div class="tbl-wrap"><table class="tbl-stack"><thead><tr><th>Zona</th><th>Jarak</th><th>Tarif per km</th><th>Ongkir</th><th>Penggunaan</th><th>Aksi</th></tr></thead><tbody>
        @forelse ($baris as $i => $b)
          <tr data-id="{{ $b['z']->id }}" data-url="{{ route('admin.zona.update', $b['z']) }}" data-nama="{{ $b['z']->nama_zona }}" data-wilayah="{{ $b['z']->contoh_wilayah }}" data-radius="{{ (float) $b['z']->radius_km }}" data-tarif="{{ (int) $b['z']->tarif_per_km }}">
            <td><div class="who"><span @class(['av', 'm' => $i % 2 === 1]) style="font-size:15px">{{ mb_substr($singkat($b['z']), 0, 2) }}</span><div><b>{{ $b['z']->nama_zona }}</b>@if ($b['z']->contoh_wilayah)<div style="font-size:12px;opacity:.7">{{ $b['z']->contoh_wilayah }}</div>@endif</div></div></td>
            <td>{{ $km($b['min']) }} – {{ $km($b['max']) }} km</td>
            <td>{{ $rp($b['z']->tarif_per_km) }} / km</td>
            <td><span @class(['kt', 'm' => $i % 2 === 1])>{{ $rp($b['ongkir']) }}</span></td>
            <td>{{ $b['z']->kirim_bulan_ini }} kiriman</td>
            <td><div class="acts"><button class="ab js-edit" title="Ubah"><svg class="ic"><use href="#i-edit"/></svg></button><button class="ab f js-del" data-url="{{ route('admin.zona.destroy', $b['z']) }}" data-name="{{ $b['z']->nama_zona }}" title="Hapus"><svg class="ic"><use href="#i-trash"/></svg></button></div></td>
          </tr>
        @empty
          <tr><td colspan="6"><div class="empty">Belum ada zona pengiriman. Pesanan Delivery membutuhkan minimal satu zona.</div></td></tr>
        @endforelse
      </tbody></table></div>
      @if ($zona->isNotEmpty())
        <div class="note" style="margin-top:16px"><svg class="ic"><use href="#i-info"/></svg><span>Alamat di luar {{ $km($maxRadius) }} km tidak dapat memilih layanan Delivery. Pelanggan diarahkan memilih Take Away.</span></div>
      @endif
    </div>

    @if ($zona->isNotEmpty())
      <div class="grid split" style="grid-template-columns:minmax(0,1fr) minmax(0,1fr);align-items:stretch">
        <div class="card"><div class="card-head" style="margin-bottom:8px"><h2>Peta Zona</h2><span class="pill">dari restoran</span></div>
          <svg viewBox="0 0 300 300" style="width:100%;max-width:280px;display:block;margin:0 auto">
            @foreach ($baris->reverse()->values() as $j => $b)
              @php $r = round(40 + 100 * $b['max'] / $maxRadius, 1); $fill = $warna[($baris->count() - 1 - $j) % 3]; @endphp
              <circle cx="150" cy="150" r="{{ $r }}" fill="{{ $fill }}" stroke="#97173A" stroke-width="1.5" @if ($j === 0) stroke-dasharray="4 6" @endif/>
              <text x="150" y="{{ 150 - $r + 19 }}" text-anchor="middle" font-family="Poppins" font-weight="700" font-size="13" fill="{{ $fill === '#97173A' ? '#F8F5E6' : '#97173A' }}">{{ $singkat($b['z']) }} · {{ $km($b['min']) }}–{{ $km($b['max']) }} km</text>
            @endforeach
            <circle cx="150" cy="156" r="13" fill="#F8F5E6"/><path d="M144 152v8M148 152v8M144 156h4M155 151c-2 2-2 5 0 7v3" stroke="#97173A" stroke-width="1.8" fill="none" stroke-linecap="round"/>
          </svg></div>
        <div class="card"><div class="card-head" style="margin-bottom:12px"><div><h2>Simulasi Ongkir</h2><p>Cek zona dan ongkir berdasarkan jarak alamat pelanggan</p></div></div>
          <div class="field"><label>Jarak alamat pelanggan</label><div class="in"><svg class="ic"><use href="#i-nav"/></svg><input id="km" type="number" value="{{ min(2, $maxRadius) }}" step="0.1" min="0"><span class="suf">km</span></div></div>
          <input id="kmRange" type="range" min="0" max="{{ ceil($maxRadius * 1.2) }}" step="0.1" value="{{ min(2, $maxRadius) }}" style="width:100%;margin:14px 0;accent-color:#97173A">
          <div class="stamp-out"><div class="stamp" style="padding:18px 20px;display:flex;justify-content:space-between;align-items:center;gap:12px">
            <div><div class="eyebrow">Hasil</div><div id="zRes" style="font:700 22px var(--f-title);margin-top:4px">-</div><div id="zSub" style="font-size:12.5px;opacity:.7">-</div></div>
            <span class="kt m" id="zFee" style="font-size:16px">-</span></div></div></div>
      </div>
    @endif
  </div>
@endsection

@push('modals')
<div class="ovl" id="mZona"><form class="modal" method="post" id="fZona"
    action="{{ $editId ? route('admin.zona.update', $editId) : route('admin.zona.store') }}">
  @csrf
  <input type="hidden" name="_method" value="PUT" @disabled(! $editId)>
  <input type="hidden" name="_modal" value="mZona">
  <input type="hidden" name="_id" value="{{ $editId }}">
  <div class="mh"><div><div class="eyebrow" style="opacity:.7">Zona pengiriman</div><h3 id="zTitle">{{ $editId ? 'Ubah Zona' : 'Tambah Zona' }}</h3></div><button type="button" class="icon-btn" data-close><svg class="ic"><use href="#i-x"/></svg></button></div>
  <div class="mb">
    @include('partials.panel.form-errors', ['modal' => 'mZona'])
    <div class="fgrid">
      <div class="field full"><label>Nama zona</label><div class="in"><svg class="ic"><use href="#i-map"/></svg><input type="text" name="nama_zona" id="zNama" value="{{ old('nama_zona') }}" maxlength="100" placeholder="Contoh: Zona A" required></div></div>
      <div class="field full"><label>Contoh wilayah (tampil ke pelanggan)</label><div class="in"><svg class="ic"><use href="#i-pin"/></svg><input type="text" name="contoh_wilayah" id="zWilayah" value="{{ old('contoh_wilayah') }}" maxlength="255" placeholder="Contoh: Medan Petisah, Medan Baru, Medan Polonia"></div></div>
      <div class="field"><label>Radius (jarak maksimum)</label><div class="in"><svg class="ic"><use href="#i-nav"/></svg><input type="number" name="radius_km" id="zRadius" value="{{ old('radius_km') }}" step="0.01" min="0.01" required><span class=suf>km</span></div></div>
      <div class="field"><label>Tarif per km</label><div class="in"><span class="suf" style="opacity:1">Rp</span><input name="tarif_per_km" id="zTarif" value="{{ old('tarif_per_km') }}" inputmode="numeric" placeholder="0" required></div></div>
    </div>
    <label class="switch-row" for="zGratis" id="zGratisWrap" style="margin-top:14px;display:none"><span>Ongkir gratis untuk zona ini<small>Tarif Rp 0: pelanggan di zona ini tidak membayar ongkir</small></span><span class="toggle" style="flex:none"><input type="checkbox" name="konfirmasi_gratis" value="1" id="zGratis" @checked(old('konfirmasi_gratis'))><i></i></span></label>
    <div class="note" style="margin-top:16px"><svg class="ic"><use href="#i-info"/></svg><span>Ongkir zona ini: <b id="zPrev">Rp 0</b> (radius × tarif per km)</span></div>
  </div>
  <div class="mf"><button type="button" class="btn o" data-close>Batal</button><button class="btn" type="submit"><svg class="ic"><use href="#i-check"/></svg>Simpan</button></div>
</form></div>
@endpush

@push('scripts')
<script>
  (function () {
    var form = document.getElementById('fZona');
    var rib = function (n) { return Number(n || 0).toLocaleString('id-ID'); };
    var num = function (s) { return Number(String(s || '').replace(/\./g, '').replace(',', '.')) || 0; };
    function prev() {
      zPrev.textContent = rp(num(zRadius.value) * num(zTarif.value));
      zGratisWrap.style.display = zTarif.value !== '' && num(zTarif.value) === 0 ? 'flex' : 'none';
    }
    function mode(edit, tr) {
      var e = form.querySelector('.ferr'); if (e) e.remove();
      form.action = edit ? tr.dataset.url : @json(route('admin.zona.store'));
      form.querySelector('[name=_method]').disabled = !edit;
      form.querySelector('[name=_id]').value = edit ? tr.dataset.id : '';
      zTitle.textContent = edit ? 'Ubah Zona' : 'Tambah Zona';
      zNama.value = edit ? tr.dataset.nama : ''; zWilayah.value = edit ? tr.dataset.wilayah : ''; zRadius.value = edit ? tr.dataset.radius : ''; zTarif.value = edit ? (tr.dataset.tarif === '0' ? '0' : rib(tr.dataset.tarif)) : '';
      zGratis.checked = edit && tr.dataset.tarif === '0';
      prev(); openModal('mZona');
    }
    document.getElementById('btnAdd').addEventListener('click', function () { mode(false); });
    document.addEventListener('click', function (e) { var b = e.target.closest('.js-edit'); if (b) mode(true, b.closest('tr')); });
    zTarif.addEventListener('input', function () { var d = zTarif.value.replace(/\D/g, ''); zTarif.value = d ? rib(d) : ''; prev(); });
    zRadius.addEventListener('input', prev);
    prev();

    /* simulasi: zona pertama yang radiusnya mencakup jarak */
    var Z = @json($simulasi);
    var km = document.getElementById('km'), kmRange = document.getElementById('kmRange');
    if (!km) return;
    function sim(v) {
      var z = Z.find(function (r) { return v <= r.max; });
      if (!z) { zRes.textContent = 'Di luar jangkauan'; zSub.textContent = 'Lebih dari ' + Z[Z.length - 1].max + ' km · Delivery tidak tersedia'; zFee.textContent = '—'; return; }
      zRes.textContent = z.nama; zSub.textContent = 'Jangkauan ' + z.min + ' – ' + z.max + ' km'; zFee.textContent = rp(z.ongkir);
    }
    km.addEventListener('input', function () { kmRange.value = km.value; sim(+km.value); });
    kmRange.addEventListener('input', function () { km.value = kmRange.value; sim(+kmRange.value); });
    sim(+km.value);
  })();
</script>
@endpush
