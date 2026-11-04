@extends('layouts.panel')

@section('title', 'Kategori & Menu')
@section('crumb', 'Admin / Kategori & Menu')
@section('heading', 'Kategori & Menu')
@section('sub', 'Kelola kategori, harga, foto, dan status ketersediaan menu')
@section('delete-title', 'Hapus data ini?')

@php
  $menuEditId = old('_modal') === 'mMenu' ? old('_id') : null;
  $katEditId = old('_modal') === 'mKat' ? old('_id') : null;
  $filter = fn (array $ubah) => route('admin.menu', array_filter(array_merge(['kategori' => $kategoriId, 'status' => $status, 'q' => $q], $ubah)));
@endphp

@push('styles')
<style>
  .catlist { display: grid; gap: 4px; }
  .catrow { position: relative; border-radius: 14px; }
  .catrow:hover { background: var(--pp); }
  .catrow a.cat { min-width: 0; display: flex; justify-content: space-between; align-items: center; height: 44px; padding: 0 16px; border-radius: 14px; font: 700 14px var(--f-accent); }
  .catrow a.cat b { font: 600 12.5px var(--f-title); opacity: .7; }
  .catrow a.cat.on { background: var(--m); color: var(--cr); }
  .catrow a.cat.on b { opacity: .85; }
  .catrow .kact { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); display: none; gap: 4px; }
  .catrow .kact .ab { width: 30px; height: 30px; }
  .catrow:hover .kact, .catrow:focus-within .kact { display: flex; }
  .catrow:hover a.cat b, .catrow:focus-within a.cat b { visibility: hidden; }
  @media (hover: none) { .catrow .kact { display: flex; } .catrow a.cat b { visibility: hidden; } }
</style>
@endpush

@section('content')
  <div class="grid split split-md" style="grid-template-columns:minmax(210px,.27fr) minmax(0,1fr);align-items:start">
    <div class="grid" style="align-content:start">
      <div class="card"><div class="card-head" style="margin-bottom:10px"><h2>Kategori</h2><button class="icon-btn" id="btnAddKat" style="width:38px;height:38px;background:var(--m);color:var(--cr);box-shadow:none" aria-label="Tambah kategori"><svg class="ic" style="width:17px;height:17px"><use href="#i-plus"/></svg></button></div>
        <div class="catlist">
          <div class="catrow"><a @class(['cat', 'on' => ! $kategoriId]) href="{{ $filter(['kategori' => null]) }}"><span>Semua kategori</span><b>{{ $totalMenu }}</b></a></div>
          @foreach ($kategori as $k)
            <div class="catrow" data-id="{{ $k->id }}" data-url="{{ route('admin.kategori.update', $k) }}" data-nama="{{ $k->nama_kategori }}">
              <a @class(['cat', 'on' => $kategoriId === $k->id]) href="{{ $filter(['kategori' => $k->id]) }}"><span>{{ $k->nama_kategori }}</span><b>{{ $k->menu_count }}</b></a>
              <span class="kact">
                <button class="ab js-edit-kat" title="Ubah kategori"><svg class="ic"><use href="#i-edit"/></svg></button>
                <button class="ab f js-del" data-url="{{ route('admin.kategori.destroy', $k) }}" data-name="Kategori {{ $k->nama_kategori }}" title="Hapus kategori"><svg class="ic"><use href="#i-trash"/></svg></button>
              </span>
            </div>
          @endforeach
        </div>
        @if ($kategori->isEmpty())
          <div class="note" style="margin-top:10px"><svg class="ic"><use href="#i-info"/></svg><span>Belum ada kategori. Tambahkan kategori dulu sebelum menambah menu.</span></div>
        @endif
      </div>
      <div class="stamp-out"><div class="stamp" style="padding:20px 22px;background:var(--bl)">
        <div class="eyebrow">Tersinkron otomatis</div><p style="font:500 13px/1.5 var(--f-body);margin-top:6px">Perubahan harga, foto, dan ketersediaan langsung tampil di halaman menu pelanggan dan kasir. Menu berstatus Habis disembunyikan dari pelanggan.</p></div></div>
    </div>

    <div class="card">
      <div class="card-head">
        <div class="chips">
          <a @class(['on' => ! $status]) href="{{ $filter(['status' => null]) }}">Semua</a>
          <a @class(['on' => $status === 'tersedia']) href="{{ $filter(['status' => 'tersedia']) }}">Tersedia<span>{{ $totalTersedia }}</span></a>
          <a @class(['on' => $status === 'habis']) href="{{ $filter(['status' => 'habis']) }}">Habis<span>{{ $totalMenu - $totalTersedia }}</span></a>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <form method="get" action="{{ route('admin.menu') }}" class="search">
            @if ($kategoriId)<input type="hidden" name="kategori" value="{{ $kategoriId }}">@endif
            @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <svg class="ic"><use href="#i-search"/></svg><input name="q" value="{{ $q }}" placeholder="Cari menu…">
          </form>
          <button class="btn" id="btnAddMenu" @disabled($kategori->isEmpty())><svg class="ic"><use href="#i-plus"/></svg>Tambah Menu</button>
        </div>
      </div>
      <div class="tbl-wrap"><table><thead><tr><th>Menu</th><th>Kategori</th><th>Harga</th><th>Ketersediaan</th><th>Aksi</th></tr></thead><tbody id="tbMenu">
        @forelse ($menu as $m)
          <tr data-id="{{ $m->id }}" data-url="{{ route('admin.menu.update', $m) }}" data-nama="{{ $m->nama_menu }}" data-kategori="{{ $m->id_kategori }}"
              data-harga="{{ (int) $m->harga }}" data-tersedia="{{ $m->tersedia ? 1 : 0 }}" data-foto="{{ $m->fotoUrl() }}">
            <td><div class="who">
              @if ($m->fotoUrl())<img class="thumb" src="{{ $m->fotoUrl() }}" alt="">@else<span class="av" style="border-radius:14px"><svg class="ic"><use href="#i-tag"/></svg></span>@endif
              <div style="min-width:0"><b>{{ $m->nama_menu }}</b></div></div></td>
            <td><span class="pill b">{{ $m->kategori->nama_kategori }}</span></td>
            <td><b>Rp {{ number_format($m->harga, 0, ',', '.') }}</b></td>
            <td><div style="display:flex;gap:10px;align-items:center"><label class="toggle"><input type="checkbox" @checked($m->tersedia) class="js-avail" data-url="{{ route('admin.menu.tersedia', $m) }}"><i></i></label><span class="js-st">@if ($m->tersedia)<span class="pill m">Tersedia</span>@else<span class="pill p">Habis</span>@endif</span></div></td>
            <td><div class="acts"><button class="ab js-edit" title="Ubah"><svg class="ic"><use href="#i-edit"/></svg></button><button class="ab f js-del" data-url="{{ route('admin.menu.destroy', $m) }}" data-name="{{ $m->nama_menu }}" title="Hapus"><svg class="ic"><use href="#i-trash"/></svg></button></div></td>
          </tr>
        @empty
          <tr><td colspan="5"><div class="empty">Belum ada menu yang cocok.</div></td></tr>
        @endforelse
      </tbody></table></div>
      <div class="tfoot"><span style="opacity:.7">Menampilkan {{ $menu->count() }} dari {{ $menu->total() }} menu</span>
        @include('partials.panel.pager', ['paginator' => $menu])</div>
    </div>
  </div>
@endsection

@push('modals')
<div class="ovl" id="mMenu"><form class="modal" method="post" enctype="multipart/form-data" id="fMenu"
    action="{{ $menuEditId ? route('admin.menu.update', $menuEditId) : route('admin.menu.store') }}">
  @csrf
  <input type="hidden" name="_method" value="PUT" @disabled(! $menuEditId)>
  <input type="hidden" name="_modal" value="mMenu">
  <input type="hidden" name="_id" value="{{ $menuEditId }}">
  <div class="mh"><div><div class="eyebrow" style="opacity:.7">Kategori &amp; menu</div><h3 id="mnTitle">{{ $menuEditId ? 'Ubah Menu' : 'Tambah Menu' }}</h3></div><button type="button" class="icon-btn" data-close><svg class="ic"><use href="#i-x"/></svg></button></div>
  <div class="mb">
    @include('partials.panel.form-errors', ['modal' => 'mMenu'])
    <div class="fgrid">
      <div class="field full"><label>Foto menu</label><div class="drop">
        <img class="pv" id="mnPv" src="" alt="" hidden><span class="av" id="mnPvNone" style="width:84px;height:84px;border-radius:16px;flex:none"><svg class="ic"><use href="#i-upload"/></svg></span>
        <div style="flex:1;min-width:0"><b style="font:600 14px var(--f-title)" id="mnFile">Belum ada foto</b><div style="font-size:12.5px;opacity:.7">JPG / PNG / WEBP · maksimal 2 MB</div></div>
        <label class="btn o sm" style="cursor:pointer"><svg class="ic" style="width:15px;height:15px"><use href="#i-upload"/></svg><span id="mnBtn">Pilih foto</span><input type="file" name="foto" id="mnFoto" accept="image/jpeg,image/png,image/webp"></label></div></div>
      <div class="field full"><label>Nama menu</label><div class="in"><svg class="ic"><use href="#i-tag"/></svg><input name="nama_menu" id="mnNama" value="{{ old('nama_menu') }}" maxlength="100" required></div></div>
      <div class="field"><label>Kategori</label><div class="in"><svg class="ic"><use href="#i-grid"/></svg><select name="id_kategori" id="mnKat" required>
        @foreach ($kategori as $k)
          <option value="{{ $k->id }}" @selected((string) old('id_kategori', $kategoriId) === (string) $k->id)>{{ $k->nama_kategori }}</option>
        @endforeach
      </select></div></div>
      <div class="field"><label>Harga</label><div class="in"><span class="suf" style="opacity:1">Rp</span><input name="harga" id="mnHarga" value="{{ old('harga') }}" inputmode="numeric" placeholder="0" required></div></div>
    </div>
  </div>
  <div class="mf"><label style="display:flex;gap:10px;align-items:center;margin-right:auto;font:600 13.5px var(--f-body)"><label class="toggle"><input type="checkbox" name="tersedia" value="1" id="mnTersedia" @checked(old('_modal') === 'mMenu' ? old('tersedia') : true)><i></i></label>Tersedia untuk dipesan</label>
    <button type="button" class="btn o" data-close>Batal</button><button class="btn" type="submit"><svg class="ic"><use href="#i-check"/></svg>Simpan</button></div>
</form></div>

<div class="ovl" id="mKat"><form class="modal" style="width:min(460px,100%)" method="post" id="fKat"
    action="{{ $katEditId ? route('admin.kategori.update', $katEditId) : route('admin.kategori.store') }}">
  @csrf
  <input type="hidden" name="_method" value="PUT" @disabled(! $katEditId)>
  <input type="hidden" name="_modal" value="mKat">
  <input type="hidden" name="_id" value="{{ $katEditId }}">
  <div class="mh"><div><div class="eyebrow" style="opacity:.7">Kategori</div><h3 id="ktTitle">{{ $katEditId ? 'Ubah Kategori' : 'Tambah Kategori' }}</h3></div><button type="button" class="icon-btn" data-close><svg class="ic"><use href="#i-x"/></svg></button></div>
  <div class="mb" style="display:grid;gap:16px">
    @include('partials.panel.form-errors', ['modal' => 'mKat'])
    <div class="field"><label>Nama kategori</label><div class="in"><svg class="ic"><use href="#i-grid"/></svg><input type="text" name="nama_kategori" id="ktNama" value="{{ old('nama_kategori') }}" maxlength="100" placeholder="Contoh: Paket Hemat" required></div></div>
  </div>
  <div class="mf"><button type="button" class="btn o" data-close>Batal</button><button class="btn" type="submit"><svg class="ic"><use href="#i-check"/></svg>Simpan</button></div>
</form></div>
@endpush

@push('scripts')
<script>
  (function () {
    var fMenu = document.getElementById('fMenu'), fKat = document.getElementById('fKat');
    var rib = function (n) { return Number(n || 0).toLocaleString('id-ID'); };

    function preview(url, name) {
      mnPv.hidden = !url; mnPvNone.hidden = !!url; if (url) mnPv.src = url;
      mnFile.textContent = name || (url ? 'Foto saat ini' : 'Belum ada foto');
      mnBtn.textContent = url ? 'Ganti foto' : 'Pilih foto';
    }
    function setMode(form, edit, url, id) {
      var e = form.querySelector('.ferr'); if (e) e.remove();
      form.action = edit ? url : form.dataset.store;
      form.querySelector('[name=_method]').disabled = !edit;
      form.querySelector('[name=_id]').value = edit ? id : '';
    }
    fMenu.dataset.store = @json(route('admin.menu.store'));
    fKat.dataset.store = @json(route('admin.kategori.store'));

    /* menu */
    var addMenu = document.getElementById('btnAddMenu');
    addMenu && addMenu.addEventListener('click', function () {
      setMode(fMenu, false); mnTitle.textContent = 'Tambah Menu';
      mnNama.value = ''; mnHarga.value = ''; mnTersedia.checked = true; mnFoto.value = ''; preview(null);
      openModal('mMenu');
    });
    document.addEventListener('click', function (e) {
      var b = e.target.closest('.js-edit'); if (!b) return;
      var tr = b.closest('tr');
      setMode(fMenu, true, tr.dataset.url, tr.dataset.id); mnTitle.textContent = 'Ubah Menu';
      mnNama.value = tr.dataset.nama; mnKat.value = tr.dataset.kategori; mnHarga.value = rib(tr.dataset.harga);
      mnTersedia.checked = tr.dataset.tersedia === '1'; mnFoto.value = ''; preview(tr.dataset.foto);
      openModal('mMenu');
    });
    // dibuka ulang karena error validasi saat mengubah menu: tampilkan foto lamanya
    var reopenId = @json($menuEditId);
    if (reopenId) { var rt = document.querySelector('#tbMenu tr[data-id="' + reopenId + '"]'); preview(rt ? rt.dataset.foto : null); }

    mnFoto.addEventListener('change', function () {
      var f = mnFoto.files[0]; if (!f) return;
      if (f.size > 2 * 1024 * 1024) { toast('Ukuran foto maksimal 2 MB'); mnFoto.value = ''; return; }
      preview(URL.createObjectURL(f), f.name);
    });
    mnHarga.addEventListener('input', function () {
      var d = mnHarga.value.replace(/\D/g, ''); mnHarga.value = d ? rib(d) : '';
    });

    /* status tersedia langsung dari tabel */
    document.querySelectorAll('.js-avail').forEach(function (t) {
      t.addEventListener('change', function () {
        var st = t.closest('td').querySelector('.js-st'), tr = t.closest('tr');
        sendJson(t.dataset.url, 'PATCH', { tersedia: t.checked }).then(function (r) {
          st.innerHTML = r.tersedia ? '<span class="pill m">Tersedia</span>' : '<span class="pill p">Habis</span>';
          tr.dataset.tersedia = r.tersedia ? '1' : '0';
          toast(r.message);
        }).catch(function (err) { t.checked = !t.checked; toast(err.message); });
      });
    });

    /* kategori */
    document.getElementById('btnAddKat').addEventListener('click', function () {
      setMode(fKat, false); ktTitle.textContent = 'Tambah Kategori'; ktNama.value = ''; openModal('mKat');
    });
    document.addEventListener('click', function (e) {
      var b = e.target.closest('.js-edit-kat'); if (!b) return;
      var row = b.closest('.catrow');
      setMode(fKat, true, row.dataset.url, row.dataset.id); ktTitle.textContent = 'Ubah Kategori'; ktNama.value = row.dataset.nama;
      openModal('mKat');
    });
  })();
</script>
@endpush
