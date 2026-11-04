@extends('layouts.panel')

@use('App\Models\Kategori')

@section('title', 'Kategori & Menu')
@section('crumb', 'Admin / Kategori & Menu')
@section('heading', 'Kategori & Menu')
@section('sub', 'Kelola kategori, harga, foto, dan status ketersediaan menu')
@section('delete-title', 'Hapus data ini?')

@php
  $menuEditId = old('_modal') === 'mMenu' ? old('_id') : null;
  $katEditId = old('_modal') === 'mKat' ? old('_id') : null;
  $filter = fn (array $ubah) => route('admin.menu', array_filter(array_merge(['kategori' => $kategoriId, 'status' => $status, 'q' => $q], $ubah)));
  $navPenuh = $navbar->count() >= Kategori::MAKS_NAVBAR;
@endphp

@push('styles')
<style>
  /* tata letak: kolom kategori menyesuaikan lebar layar desktop */
  .menu-split { display: grid; grid-template-columns: clamp(250px, 20vw, 310px) minmax(0, 1fr); gap: 18px; align-items: start; }
  .kat-col { display: grid; gap: 18px; align-content: start; }
  .catlist { display: grid; gap: 4px; }
  .catrow { position: relative; border-radius: 14px; }
  .catrow:hover { background: var(--pp); }
  .catrow a.cat { display: flex; align-items: center; gap: 10px; min-height: 44px; padding: 8px 14px; border-radius: 14px; font: 700 14px var(--f-accent); }
  .catrow a.cat .nm { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .catrow a.cat .meta { display: flex; align-items: center; gap: 8px; flex: none; }
  .catrow a.cat b { font: 600 12.5px var(--f-title); opacity: .7; min-width: 16px; text-align: right; }
  .catrow a.cat.on { background: var(--m); color: var(--cr); }
  .catrow a.cat.on b { opacity: .85; }
  .navno { display: inline-grid; place-items: center; height: 20px; padding: 0 7px; border-radius: 999px; background: var(--bl); color: var(--m); font: 700 10.5px var(--f-accent); letter-spacing: .04em; }
  .catrow .kact { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); display: none; gap: 4px; }
  .catrow .kact .ab { width: 30px; height: 30px; }
  .catrow:hover .kact, .catrow:focus-within .kact { display: flex; }
  .catrow:hover a.cat .meta, .catrow:focus-within a.cat .meta { visibility: hidden; }
  @media (hover: none) { .catrow .kact { display: flex; } .catrow a.cat .meta { visibility: hidden; } }
  .navlist { display: grid; gap: 6px; margin-top: 4px; }
  .navlist div { display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-radius: 12px; background: var(--cr); box-shadow: inset 0 0 0 1.2px var(--m15); font: 700 13.5px var(--f-accent); }
  .navlist div span { display: inline-grid; place-items: center; width: 24px; height: 24px; border-radius: 50%; background: var(--m); color: var(--cr); font: 700 11.5px var(--f-title); flex: none; }
  .navlist div.promo span { background: var(--bl); color: var(--m); }
  .menu-tools { display: flex; gap: 10px; flex-wrap: wrap; }

  /* laptop 13-14" / tablet: kategori di atas, menu di bawah (tabel menu butuh ruang penuh) */
  @media (max-width: 1365px) {
    .menu-split { grid-template-columns: minmax(0, 1fr); }
    .kat-col { grid-template-columns: minmax(0, 1.7fr) minmax(240px, 1fr); align-items: start; }
    .catlist { grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 8px; }
    .catrow { box-shadow: inset 0 0 0 1.2px var(--m15); }
  }
  @media (max-width: 760px) {
    .kat-col { grid-template-columns: minmax(0, 1fr); }
    .menu-tools { width: 100%; }
    .menu-tools .btn { flex: 1; }
  }

  /* dialog menu: area foto */
  .drop .pv, .drop .pv-none { width: 84px; height: 84px; border-radius: 16px; flex: none; }
  .drop .pv-none { display: grid; place-items: center; background: var(--bl); }
  .drop .meta { flex: 1; min-width: 0; }
  .drop .meta b { display: block; font: 600 14px var(--f-title); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  @media (max-width: 520px) { .drop { flex-wrap: wrap; } .drop .btn { width: 100%; } }
  .switch-row { display: flex; gap: 12px; align-items: center; padding: 12px 14px; border-radius: 14px; background: var(--pp); font: 600 13.5px var(--f-body); }
  .switch-row > span { flex: 1; }
  .switch-row small { display: block; font-size: 12px; opacity: .7; font-weight: 500; }
</style>
@endpush

@section('content')
  <div class="menu-split">
    <div class="kat-col">
      <div class="card"><div class="card-head" style="margin-bottom:10px"><h2>Kategori</h2><button class="icon-btn" id="btnAddKat" style="width:38px;height:38px;background:var(--m);color:var(--cr);box-shadow:none" aria-label="Tambah kategori" title="Tambah kategori"><svg class="ic" style="width:17px;height:17px"><use href="#i-plus"/></svg></button></div>
        <div class="catlist">
          <div class="catrow"><a @class(['cat', 'on' => ! $kategoriId]) href="{{ $filter(['kategori' => null]) }}"><span class="nm">Semua kategori</span><span class="meta"><b>{{ $totalMenu }}</b></span></a></div>
          @foreach ($kategori as $k)
            <div class="catrow" data-id="{{ $k->id }}" data-url="{{ route('admin.kategori.update', $k) }}" data-nama="{{ $k->nama_kategori }}"
                 data-navbar="{{ $k->tampil_navbar ? 1 : 0 }}" data-urutan="{{ $k->urutan_navbar }}">
              <a @class(['cat', 'on' => $kategoriId === $k->id]) href="{{ $filter(['kategori' => $k->id]) }}" title="{{ $k->nama_kategori }}">
                <span class="nm">{{ $k->nama_kategori }}</span>
                <span class="meta">@if ($k->tampil_navbar)<i class="navno" title="Tampil di navbar, urutan {{ $k->urutan_navbar }}">NAV {{ $k->urutan_navbar }}</i>@endif<b>{{ $k->menu_count }}</b></span>
              </a>
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

      <div class="card soft">
        <div class="card-head" style="margin-bottom:8px"><div><h2>Navbar Beranda</h2><p>Urutan menu di navbar pelanggan</p></div><span class="pill b">{{ $navbar->count() }} / {{ Kategori::MAKS_NAVBAR }}</span></div>
        <div class="navlist">
          <div class="promo"><span>★</span>Promo</div>
          @forelse ($navbar as $n)
            <div><span>{{ $n->urutan_navbar }}</span>{{ $n->nama_kategori }}</div>
          @empty
            <p style="font-size:13px;opacity:.7;padding:4px 2px">Belum ada kategori yang ditampilkan.</p>
          @endforelse
        </div>
        <p style="font:500 12.5px/1.5 var(--f-body);opacity:.75;margin-top:12px">Atur lewat tombol ubah kategori. Menu berstatus Habis disembunyikan dari pelanggan.</p>
      </div>
    </div>

    <div class="card">
      <div class="card-head">
        <div class="chips">
          <a @class(['on' => ! $status]) href="{{ $filter(['status' => null]) }}">Semua</a>
          <a @class(['on' => $status === 'tersedia']) href="{{ $filter(['status' => 'tersedia']) }}">Tersedia<span>{{ $totalTersedia }}</span></a>
          <a @class(['on' => $status === 'habis']) href="{{ $filter(['status' => 'habis']) }}">Habis<span>{{ $totalMenu - $totalTersedia }}</span></a>
        </div>
        <div class="menu-tools">
          <form method="get" action="{{ route('admin.menu') }}" class="search keep">
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
      <div class="field full"><label for="mnFoto">Foto menu</label><div class="drop">
        <img class="pv" id="mnPv" src="" alt="" hidden><span class="pv-none" id="mnPvNone"><svg class="ic"><use href="#i-upload"/></svg></span>
        <div class="meta"><b id="mnFile">Belum ada foto</b><div style="font-size:12.5px;opacity:.7">JPG / PNG / WEBP · maksimal 2 MB</div></div>
        <button type="button" class="btn o sm" id="mnPick"><svg class="ic" style="width:15px;height:15px"><use href="#i-upload"/></svg><span id="mnBtn">Pilih foto</span></button>
        <input type="file" name="foto" id="mnFoto" accept="image/jpeg,image/png,image/webp" hidden></div></div>
      <div class="field full"><label for="mnNama">Nama menu</label><div class="in"><svg class="ic"><use href="#i-tag"/></svg><input name="nama_menu" id="mnNama" value="{{ old('nama_menu') }}" maxlength="100" placeholder="Contoh: Pizza Tuna" required></div></div>
      <div class="field"><label for="mnKat">Kategori</label><div class="in"><svg class="ic"><use href="#i-grid"/></svg><select name="id_kategori" id="mnKat" required>
        @foreach ($kategori as $k)
          <option value="{{ $k->id }}" @selected((string) old('id_kategori', $kategoriId) === (string) $k->id)>{{ $k->nama_kategori }}</option>
        @endforeach
      </select></div></div>
      <div class="field"><label for="mnHarga">Harga</label><div class="in"><span class="suf" style="opacity:1">Rp</span><input name="harga" id="mnHarga" value="{{ old('harga') }}" inputmode="numeric" placeholder="0" required></div></div>
      <label class="switch-row full" for="mnTersedia"><span>Tersedia untuk dipesan<small>Jika dimatikan, menu ditandai Habis dan disembunyikan dari pelanggan</small></span><span class="toggle" style="flex:none"><input type="checkbox" name="tersedia" value="1" id="mnTersedia" @checked(old('_modal') === 'mMenu' ? old('tersedia') : true)><i></i></span></label>
    </div>
  </div>
  <div class="mf"><button type="button" class="btn o" data-close>Batal</button><button class="btn" type="submit"><svg class="ic"><use href="#i-check"/></svg>Simpan</button></div>
</form></div>

<div class="ovl" id="mKat"><form class="modal" style="width:min(480px,100%)" method="post" id="fKat"
    action="{{ $katEditId ? route('admin.kategori.update', $katEditId) : route('admin.kategori.store') }}">
  @csrf
  <input type="hidden" name="_method" value="PUT" @disabled(! $katEditId)>
  <input type="hidden" name="_modal" value="mKat">
  <input type="hidden" name="_id" value="{{ $katEditId }}">
  <div class="mh"><div><div class="eyebrow" style="opacity:.7">Kategori</div><h3 id="ktTitle">{{ $katEditId ? 'Ubah Kategori' : 'Tambah Kategori' }}</h3></div><button type="button" class="icon-btn" data-close><svg class="ic"><use href="#i-x"/></svg></button></div>
  <div class="mb" style="display:grid;gap:16px">
    @include('partials.panel.form-errors', ['modal' => 'mKat'])
    <div class="field"><label for="ktNama">Nama kategori</label><div class="in"><svg class="ic"><use href="#i-grid"/></svg><input type="text" name="nama_kategori" id="ktNama" value="{{ old('nama_kategori') }}" maxlength="100" placeholder="Contoh: Paket Hemat" required></div></div>
    <label class="switch-row" for="ktNav"><span>Tampilkan di navbar beranda<small id="ktNavInfo">{{ $navbar->count() }} dari {{ Kategori::MAKS_NAVBAR }} tempat terpakai</small></span><span class="toggle" style="flex:none"><input type="checkbox" name="tampil_navbar" value="1" id="ktNav" @checked(old('_modal') === 'mKat' && old('tampil_navbar'))><i></i></span></label>
    <div class="field" id="ktUrutWrap"><label for="ktUrut">Urutan di navbar</label><div class="in"><svg class="ic"><use href="#i-filter"/></svg><select name="urutan_navbar" id="ktUrut">
      @for ($i = 1; $i <= Kategori::MAKS_NAVBAR; $i++)
        @php $pemilik = $navbar->firstWhere('urutan_navbar', $i); @endphp
        <option value="{{ $i }}" data-pemilik="{{ $pemilik?->id }}" @selected((string) old('urutan_navbar') === (string) $i)>{{ $i }}{{ $pemilik ? ' · sekarang '.$pemilik->nama_kategori : ' · kosong' }}</option>
      @endfor
    </select></div>
      <p style="font-size:12px;opacity:.7;margin-top:6px">Jika urutan sudah dipakai, kategori itu bertukar posisi.</p></div>
  </div>
  <div class="mf"><button type="button" class="btn o" data-close>Batal</button><button class="btn" type="submit"><svg class="ic"><use href="#i-check"/></svg>Simpan</button></div>
</form></div>
@endpush

@push('scripts')
<script>
  (function () {
    var fMenu = document.getElementById('fMenu'), fKat = document.getElementById('fKat');
    var rib = function (n) { return Number(n || 0).toLocaleString('id-ID'); };
    var navPenuh = @json($navPenuh), maks = {{ Kategori::MAKS_NAVBAR }};

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

    /* ---------- menu */
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

    mnPick.addEventListener('click', function () { mnFoto.click(); });
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

    /* ---------- kategori + navbar */
    function navState(sudahDiNavbar) {
      // navbar penuh: kategori baru / yang belum tampil tidak bisa diaktifkan
      var kunci = navPenuh && !sudahDiNavbar;
      ktNav.disabled = kunci; if (kunci) ktNav.checked = false;
      ktNavInfo.textContent = kunci ? 'Navbar sudah penuh (' + maks + ' kategori). Sembunyikan salah satu dulu.' : ktNavInfo.dataset.base;
      ktUrutWrap.hidden = !ktNav.checked; ktUrut.required = ktNav.checked;
    }
    function firstEmpty() {
      var o = [].find.call(ktUrut.options, function (x) { return !x.dataset.pemilik; });
      return o ? o.value : '1';
    }
    ktNavInfo.dataset.base = ktNavInfo.textContent;
    ktNav.addEventListener('change', function () { if (ktNav.checked && !ktUrut.dataset.set) ktUrut.value = firstEmpty(); navState(fKat.dataset.sudah === '1'); });

    document.getElementById('btnAddKat').addEventListener('click', function () {
      setMode(fKat, false); ktTitle.textContent = 'Tambah Kategori'; ktNama.value = '';
      fKat.dataset.sudah = '0'; ktNav.checked = false; ktUrut.dataset.set = ''; ktUrut.value = firstEmpty(); navState(false);
      openModal('mKat');
    });
    document.addEventListener('click', function (e) {
      var b = e.target.closest('.js-edit-kat'); if (!b) return;
      var row = b.closest('.catrow'), di = row.dataset.navbar === '1';
      setMode(fKat, true, row.dataset.url, row.dataset.id); ktTitle.textContent = 'Ubah Kategori'; ktNama.value = row.dataset.nama;
      fKat.dataset.sudah = di ? '1' : '0'; ktNav.checked = di;
      ktUrut.dataset.set = di ? '1' : ''; ktUrut.value = di ? row.dataset.urutan : firstEmpty();
      navState(di); openModal('mKat');
    });
    // dibuka ulang karena error validasi
    var katReopen = @json($katEditId);
    if (@json(old('_modal') === 'mKat')) {
      var kr = katReopen && document.querySelector('.catrow[data-id="' + katReopen + '"]');
      fKat.dataset.sudah = kr && kr.dataset.navbar === '1' ? '1' : '0';
      navState(fKat.dataset.sudah === '1');
    } else { navState(false); }
  })();
</script>
@endpush
