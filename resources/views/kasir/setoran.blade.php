@extends('layouts.panel')

@section('title', 'Setoran Kurir')
@section('crumb', 'Kasir / Setoran Kurir')
@section('heading', 'Setoran Tunai Kurir')
@section('sub', 'Terima uang pesanan bayar di tempat (COD) yang dibawa kurir')

@php $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.'); @endphp

@push('styles')
<style>
  .setor-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 380px), 1fr)); gap: 18px; align-items: start; }
  .setor-row { display: flex; gap: 10px; align-items: baseline; padding: 9px 0; border-bottom: 1px solid var(--m15); font-size: 13.5px; }
  .setor-row:last-child { border-bottom: 0; }
  .setor-row b { font: 600 13.5px var(--f-title); }
  .setor-row .r { margin-left: auto; font: 600 13.5px var(--f-title); white-space: nowrap; }
  .setor-total { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 10px; margin-top: 12px; padding-top: 12px; border-top: 1.5px dashed var(--m60); }
</style>
@endpush

@section('content')
  <div class="grid g3">
    <div class="card stat f"><div class="top"><span class="eyebrow">Belum disetor</span><span class="ico"><svg class="ic"><use href="#i-cash"/></svg></span></div><div class="v">{{ $rp($totalBelum) }}</div><div class="t">{{ $perKurir->count() }} kurir · {{ $perKurir->flatten()->count() }} pesanan COD</div></div>
    <div class="card stat b"><div class="top"><span class="eyebrow">Disetor hari ini</span><span class="ico"><svg class="ic"><use href="#i-check"/></svg></span></div><div class="v">{{ $rp($riwayat->sum('jumlah')) }}</div><div class="t">{{ $riwayat->count() }} pesanan</div></div>
    <div class="card stat p"><div class="top"><span class="eyebrow">Cara kerja</span><span class="ico"><svg class="ic"><use href="#i-info"/></svg></span></div><div class="t" style="margin-top:12px;font-size:13px;line-height:1.5">Kurir menagih tunai saat mengantar. Saat kembali, hitung uangnya lalu tekan <b>Terima Setoran</b>.</div></div>
  </div>

  <div class="setor-grid" style="margin-top:18px">
    @forelse ($perKurir as $idKurir => $daftar)
      @php $kurir = $daftar->first()->kurir; @endphp
      <div class="card"><div class="card-head" style="margin-bottom:6px"><div class="who"><span class="av m">{{ $kurir?->inisial() }}</span><div><h2 style="font-size:16px">{{ $kurir?->nama ?? 'Kurir dihapus' }}</h2><p>{{ $kurir?->no_hp }}</p></div></div><span class="pill b">{{ $daftar->count() }} pesanan</span></div>
        @foreach ($daftar as $b)
          <div class="setor-row"><b>{{ $b->pesanan->kode() }}</b><span style="opacity:.75;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $b->pesanan->namaPemesan() }} · {{ $b->waktu_bayar->translatedFormat('d M, H:i') }}</span><span class="r">{{ $rp($b->jumlah) }}</span></div>
        @endforeach
        <form method="post" action="{{ route('kasir.setoran.terima', $idKurir) }}" class="setor-total">@csrf
          @foreach ($daftar as $b)<input type="hidden" name="ids[]" value="{{ $b->id }}">@endforeach
          <div><div class="eyebrow">Seharusnya</div><b style="font:700 22px var(--f-title)">{{ $rp($daftar->sum('jumlah')) }}</b></div>
          <div class="field" style="flex:1;min-width:180px"><label>Uang yang dihitung</label><div class="in"><span class="suf" style="opacity:1">Rp</span><input name="uang_dihitung" class="js-uang" data-harus="{{ (int) $daftar->sum('jumlah') }}" inputmode="numeric" placeholder="Hitung lalu isi" required value="{{ old('uang_dihitung') }}"></div>
            <small class="js-selisih" style="display:block;margin-top:6px;font-size:12.5px"></small></div>
          <div class="field js-catatan" style="flex-basis:100%;display:none"><label>Catatan selisih</label><div class="in"><svg class="ic"><use href="#i-edit"/></svg><input name="catatan" maxlength="255" placeholder="Mis. kurang Rp 5.000, akan dilunasi besok" value="{{ old('catatan') }}"></div></div>
          <button class="btn" type="submit"><svg class="ic"><use href="#i-check"/></svg>Terima Setoran</button>
        </form>
      </div>
    @empty
      <div class="card" style="grid-column:1/-1"><div class="empty"><svg class="ic" style="width:30px;height:30px;margin-bottom:8px"><use href="#i-check"/></svg><br>Tidak ada uang tunai yang belum disetor kurir.</div></div>
    @endforelse
  </div>

  @if ($setoranHariIni->contains(fn ($s) => $s->selisih() != 0))
    <div class="card" style="margin-top:18px"><div class="card-head"><div><h2>Selisih Setoran Hari Ini</h2><p>Setoran yang uangnya tidak sama dengan seharusnya</p></div></div>
      @foreach ($setoranHariIni->filter(fn ($s) => $s->selisih() != 0) as $s)
        <div class="setor-row"><b>{{ $s->kurir?->nama ?? 'Kurir dihapus' }}</b><span style="opacity:.8">{{ $s->waktu_setor->format('H:i') }} · seharusnya {{ $rp($s->jumlah_seharusnya) }}, diterima {{ $rp($s->jumlah_diterima) }}{{ $s->catatan ? ' · '.$s->catatan : '' }}</span><span class="r" style="font-weight:700">{{ $s->selisih() < 0 ? '-' : '+' }}{{ $rp(abs($s->selisih())) }}</span></div>
      @endforeach
    </div>
  @endif

  <div class="card" style="margin-top:18px"><div class="card-head"><div><h2>Setoran Hari Ini</h2><p>{{ now()->translatedFormat('l, j F Y') }}</p></div></div>
    <div class="tbl-wrap"><table class="tbl-stack"><thead><tr><th>Pesanan</th><th>Kurir</th><th>Jumlah</th><th>Disetor</th><th>Diterima oleh</th></tr></thead><tbody>
      @forelse ($riwayat as $b)
        <tr><td><b>{{ $b->pesanan->kode() }}</b></td><td>{{ $b->kurir?->nama }}</td><td>{{ $rp($b->jumlah) }}</td><td>{{ $b->disetor_pada->format('H:i') }}</td><td>{{ $b->kasir?->nama }}</td></tr>
      @empty
        <tr><td colspan="5"><div class="empty">Belum ada setoran hari ini.</div></td></tr>
      @endforelse
    </tbody></table></div></div>
@endsection

@push('scripts')
<script>
  (function () {
    // ketik uang yang dihitung: tampilkan selisihnya; catatan wajib bila tidak sama
    document.querySelectorAll('.js-uang').forEach(function (inp) {
      var f = inp.closest('form'), info = f.querySelector('.js-selisih'), cat = f.querySelector('.js-catatan');
      function cek() {
        var d = inp.value.replace(/\D/g, ''); inp.value = d ? (+d).toLocaleString('id-ID') : '';
        if (!d) { info.textContent = ''; cat.style.display = 'none'; return; }
        var s = +d - +inp.dataset.harus;
        info.textContent = s === 0 ? '✓ Sesuai' : (s < 0 ? 'Kurang ' : 'Lebih ') + rp(Math.abs(s));
        info.style.fontWeight = s === 0 ? '' : '700';
        cat.style.display = s === 0 ? 'none' : '';
      }
      inp.addEventListener('input', cek); cek();
    });
  })();
</script>
@endpush
