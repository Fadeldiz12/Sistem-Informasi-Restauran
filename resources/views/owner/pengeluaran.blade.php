@extends('layouts.panel')

@use('App\Models\Pengeluaran')
@use('App\Services\LaporanService')

@section('title', 'Pengeluaran Operasional')
@section('crumb', 'Owner / Pengeluaran')
@section('heading', 'Pengeluaran Operasional')
@section('sub', 'Catat, ubah, dan hapus pengeluaran operasional restoran')
@section('delete-title', 'Hapus pengeluaran ini?')

@php
  $editId = old('_modal') === 'mOut' ? old('_id') : null;
  $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
  $katLama = old('kategori', Pengeluaran::KATEGORI[0]);
  $filter = fn (array $ubah) => route('owner.pengeluaran', array_filter(array_merge(['q' => $q, 'kategori' => $kategoriAktif], $ubah)));
@endphp

@push('styles')
<style>
  .seg button { white-space: nowrap; }
  td.ket { white-space: normal; min-width: 180px; opacity: .85; }
  .out-tools { display: flex; gap: 10px; flex-wrap: wrap; }
  @media (max-width: 760px) { .out-tools { width: 100%; } .out-tools .btn { flex: 1; } }
</style>
@endpush

@section('content')
  <div class="grid g3">
    <div class="card stat f"><div class="top"><span class="eyebrow">Pengeluaran bulan ini</span><span class="ico"><svg class="ic"><use href="#i-wallet"/></svg></span></div><div class="v">{{ LaporanService::ringkas($totalBulan) }}</div><div class="t">{{ $jmlBulan }} transaksi · {{ today()->translatedFormat('F Y') }}</div></div>
    <div class="card stat b"><div class="top"><span class="eyebrow">{{ $terbesar?->kategori ?? 'Kategori terbesar' }}</span><span class="ico"><svg class="ic"><use href="#i-box"/></svg></span></div><div class="v">{{ $terbesar ? $persenTerbesar.'%' : '—' }}</div><div class="t">{{ $terbesar ? 'Kategori pengeluaran terbesar · '.$rp($terbesar->total) : 'Belum ada pengeluaran bulan ini' }}</div></div>
    <div class="card stat"><div class="top"><span class="eyebrow">Rata-rata harian</span><span class="ico"><svg class="ic"><use href="#i-chart"/></svg></span></div><div class="v">{{ LaporanService::ringkas($rataHarian) }}</div><div class="t">1 – {{ today()->translatedFormat('j F Y') }}</div></div>
  </div>

  <div class="card" style="margin-top:18px">
    <div class="card-head"><div><h2>Daftar Pengeluaran Operasional</h2><p>Dipakai pada perhitungan laporan keuangan</p></div>
      <div class="out-tools">
        <form method="get" action="{{ route('owner.pengeluaran') }}" class="search keep">@if ($kategoriAktif)<input type="hidden" name="kategori" value="{{ $kategoriAktif }}">@endif<svg class="ic"><use href="#i-search"/></svg><input name="q" value="{{ $q }}" placeholder="Cari keterangan…"></form>
        <button class="btn" id="btnAdd"><svg class="ic"><use href="#i-plus"/></svg>Tambah Pengeluaran</button>
      </div></div>
    <div class="chips" style="margin-bottom:12px">
      <a @class(['on' => ! $kategoriAktif]) href="{{ $filter(['kategori' => null]) }}">Semua</a>
      @foreach (Pengeluaran::KATEGORI as $k)<a @class(['on' => $kategoriAktif === $k]) href="{{ $filter(['kategori' => $k]) }}">{{ $k }}</a>@endforeach
    </div>
    <div class="tbl-wrap"><table class="tbl-stack"><thead><tr><th>Tanggal</th><th>Kategori</th><th>Nominal</th><th>Keterangan</th><th>Aksi</th></tr></thead><tbody id="tbOut">
      @forelse ($pengeluaran as $p)
        <tr data-id="{{ $p->id }}" data-url="{{ route('owner.pengeluaran.update', $p) }}" data-tanggal="{{ $p->tanggal->toDateString() }}" data-kategori="{{ $p->kategori }}" data-nominal="{{ (int) $p->nominal }}" data-ket="{{ $p->keterangan }}">
          <td><b>{{ $p->tanggal->format('d/m/Y') }}</b></td>
          <td><span @class(['pill', 'b' => $p->kategori === 'Bahan baku'])>{{ $p->kategori }}</span></td>
          <td><b>{{ $rp($p->nominal) }}</b></td>
          <td class="ket">{{ $p->keterangan ?: '—' }}</td>
          <td><div class="acts"><button class="ab js-edit" title="Ubah"><svg class="ic"><use href="#i-edit"/></svg></button><button class="ab f js-del" data-url="{{ route('owner.pengeluaran.destroy', $p) }}" data-name="{{ $p->keterangan ?: $p->kategori.' · '.$rp($p->nominal) }}" title="Hapus"><svg class="ic"><use href="#i-trash"/></svg></button></div></td>
        </tr>
      @empty
        <tr><td colspan="5"><div class="empty">{{ $q !== '' || $kategoriAktif ? 'Tidak ada pengeluaran yang cocok.' : 'Belum ada pengeluaran. Tambahkan pengeluaran pertama.' }}</div></td></tr>
      @endforelse
    </tbody></table></div>
    <div class="tfoot"><span style="opacity:.7">Menampilkan {{ $pengeluaran->count() }} dari {{ $pengeluaran->total() }} pengeluaran</span>
      @include('partials.panel.pager', ['paginator' => $pengeluaran])</div>
  </div>
@endsection

@push('modals')
<div class="ovl" id="mOut"><form class="modal" method="post" id="fOut"
    action="{{ $editId ? route('owner.pengeluaran.update', $editId) : route('owner.pengeluaran.store') }}">
  @csrf
  <input type="hidden" name="_method" value="PUT" @disabled(! $editId)>
  <input type="hidden" name="_modal" value="mOut">
  <input type="hidden" name="_id" value="{{ $editId }}">
  <input type="hidden" name="kategori" id="oKat" value="{{ $katLama }}">
  <div class="mh"><div><div class="eyebrow" style="opacity:.7">Pengeluaran operasional</div><h3 id="oTitle">{{ $editId ? 'Ubah Pengeluaran' : 'Tambah Pengeluaran' }}</h3></div><button type="button" class="icon-btn" data-close><svg class="ic"><use href="#i-x"/></svg></button></div>
  <div class="mb">
    @include('partials.panel.form-errors', ['modal' => 'mOut'])
    <div class="fgrid">
      <div class="field"><label for="oTgl">Tanggal</label><div class="in"><svg class="ic"><use href="#i-cal"/></svg><input type="date" name="tanggal" id="oTgl" value="{{ old('tanggal', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required></div></div>
      <div class="field"><label for="oNom">Nominal</label><div class="in"><span class="suf" style="opacity:1">Rp</span><input name="nominal" id="oNom" value="{{ old('nominal') }}" inputmode="numeric" placeholder="0" required></div></div>
      <div class="field full"><label>Kategori pengeluaran</label><div class="seg" id="oSeg">
        @foreach (Pengeluaran::KATEGORI as $k)<button type="button" data-k="{{ $k }}" @class(['on' => $katLama === $k])>{{ $k }}</button>@endforeach
      </div></div>
      <div class="field full"><label for="oKet">Keterangan</label><div class="in ta"><textarea name="keterangan" id="oKet" maxlength="500" placeholder="Contoh: Isi ulang 6 tabung gas 12 kg">{{ old('keterangan') }}</textarea></div></div>
    </div>
  </div>
  <div class="mf"><button type="button" class="btn o" data-close>Batal</button><button class="btn" type="submit"><svg class="ic"><use href="#i-check"/></svg>Simpan</button></div>
</form></div>
@endpush

@push('scripts')
<script>
  (function () {
    var form = document.getElementById('fOut'), hariIni = @json(today()->toDateString());
    var rib = function (n) { return Number(n || 0).toLocaleString('id-ID'); };
    function kat(k) { oKat.value = k; oSeg.querySelectorAll('button').forEach(function (b) { b.classList.toggle('on', b.dataset.k === k); }); }
    oSeg.addEventListener('click', function (e) { var b = e.target.closest('button'); if (b) kat(b.dataset.k); });
    function mode(edit, tr) {
      var e = form.querySelector('.ferr'); if (e) e.remove();
      form.action = edit ? tr.dataset.url : @json(route('owner.pengeluaran.store'));
      form.querySelector('[name=_method]').disabled = !edit;
      form.querySelector('[name=_id]').value = edit ? tr.dataset.id : '';
      oTitle.textContent = edit ? 'Ubah Pengeluaran' : 'Tambah Pengeluaran';
      oTgl.value = edit ? tr.dataset.tanggal : hariIni;
      oNom.value = edit ? rib(tr.dataset.nominal) : '';
      oKet.value = edit ? tr.dataset.ket : '';
      kat(edit ? tr.dataset.kategori : @json(Pengeluaran::KATEGORI[0]));
      openModal('mOut');
    }
    document.getElementById('btnAdd').addEventListener('click', function () { mode(false); });
    document.addEventListener('click', function (e) { var b = e.target.closest('.js-edit'); if (b) mode(true, b.closest('tr')); });
    oNom.addEventListener('input', function () { var d = oNom.value.replace(/\D/g, ''); oNom.value = d ? rib(d) : ''; });
  })();
</script>
@endpush
