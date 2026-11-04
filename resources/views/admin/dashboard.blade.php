@extends('layouts.panel')

@use('App\Models\Pesanan')
@use('App\Models\User')

@section('title', 'Dashboard Admin')
@section('crumb', 'Admin / Dashboard')
@section('heading', 'Dashboard')
@section('sub', 'Ringkasan data master dan aktivitas pesanan hari ini')

@php
  $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
  $selisih = $totalKemarin > 0 ? round(($totalHariIni - $totalKemarin) / $totalKemarin * 100) : null;
  $alur = [
    Pesanan::STATUS_MENUNGGU => '',
    Pesanan::STATUS_DIPROSES => 'b',
    Pesanan::STATUS_SIAP => '',
    Pesanan::STATUS_DIANTAR => 'b',
    Pesanan::STATUS_SELESAI => 'c',
    Pesanan::STATUS_DIBATALKAN => '',
  ];
  $layananIkon = [Pesanan::LAYANAN_DINE_IN => 'i-dine', Pesanan::LAYANAN_DELIVERY => 'i-truck', Pesanan::LAYANAN_TAKE_AWAY => 'i-bag'];
  $maxRole = max(1, $perRole->max() ?? 1);
  $totalUser = $perRole->sum();
  $habis = $totalMenu - $menuTersedia;
  $perhatian = collect([
    ['route' => 'admin.menu', 'q' => ['status' => 'habis'], 'ikon' => 'i-alert', 'teks' => "{$habis} menu berstatus Habis", 'show' => $habis > 0],
    ['route' => null, 'ikon' => 'i-box', 'teks' => "{$bahanKritis} bahan baku mencapai ROP (dapur)", 'show' => $bahanKritis > 0],
    ['route' => null, 'ikon' => 'i-receipt', 'teks' => "{$menungguKonfirmasi} pesanan menunggu konfirmasi kasir", 'show' => $menungguKonfirmasi > 0],
    ['route' => 'admin.zona', 'q' => [], 'ikon' => 'i-map', 'teks' => 'Belum ada zona pengiriman', 'show' => $jumlahZona === 0],
  ])->where('show', true);
@endphp

@section('content')
  <div class="grid g4">
    <div class="card stat f"><div class="top"><span class="eyebrow">Pesanan hari ini</span><span class="ico"><svg class="ic"><use href="#i-receipt"/></svg></span></div><div class="v">{{ $totalHariIni }}</div>
      <div class="t">@if ($selisih === null) Kemarin {{ $totalKemarin }} pesanan @else {{ $selisih >= 0 ? '↑' : '↓' }} {{ abs($selisih) }}% dibanding kemarin @endif</div></div>
    <div class="card stat "><div class="top"><span class="eyebrow">Total pengguna</span><span class="ico"><svg class="ic"><use href="#i-users"/></svg></span></div><div class="v">{{ $totalUser }}</div><div class="t">{{ $perRole->count() }} peran terisi</div></div>
    <div class="card stat b"><div class="top"><span class="eyebrow">Menu aktif</span><span class="ico"><svg class="ic"><use href="#i-tag"/></svg></span></div><div class="v">{{ $menuTersedia }} / {{ $totalMenu }}</div><div class="t">{{ $habis }} menu berstatus Habis</div></div>
    <div class="card stat p"><div class="top"><span class="eyebrow">Zona pengiriman</span><span class="ico"><svg class="ic"><use href="#i-map"/></svg></span></div><div class="v">{{ $jumlahZona }}</div>
      <div class="t">@if ($jumlahZona) Ongkir {{ $rp($ongkirMin) }}@if ($ongkirMax != $ongkirMin) – {{ $rp($ongkirMax) }}@endif @else Belum ada zona @endif</div></div>
  </div>

  <div class="grid split" style="grid-template-columns:minmax(0,1.45fr) minmax(0,1fr);margin-top:18px">
    <div class="card"><div class="card-head"><div><h2>Alur Pesanan Hari Ini</h2><p>Jumlah pesanan pada tiap status</p></div><span class="pill">{{ $totalHariIni }} pesanan</span></div>
      <div class="mods">
        @foreach ($alur as $status => $warna)
          @php $n = $perStatus[$status] ?? 0; @endphp
          <div class="mod {{ $warna }}" style="z-index:{{ $loop->index }}"><div class="row"><span class="no">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><span class="lbl">{{ Pesanan::LABEL_STATUS[$status] }}</span><span class="val">{{ $n }}</span><div class="bar"><i style="width:{{ $totalHariIni ? round($n / $totalHariIni * 100) : 0 }}%"></i></div></div></div>
        @endforeach
      </div></div>
    <div class="grid" style="align-content:start">
      <div class="card soft" style="background-image:linear-gradient(var(--m08) 1px,transparent 1px),linear-gradient(90deg,var(--m08) 1px,transparent 1px);background-size:24px 24px">
        <div class="card-head"><h2>Pengaturan Aktif</h2><a class="btn o sm" href="{{ route('admin.pengaturan') }}"><svg class="ic" style="width:15px;height:15px"><use href="#i-edit"/></svg>Ubah</a></div>
        @foreach ([['i-file', 'Pajak restoran', rtrim(rtrim((string) $pengaturan->pajak, '0'), '.').'%'], ['i-receipt', 'Biaya layanan', $rp($pengaturan->biaya_layanan).' / pesanan'], ['i-clock', 'Jam operasional', $pengaturan->jamLabel()]] as [$ikon, $label, $nilai])
          <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;padding:11px 14px;border-radius:14px;background:var(--cr);box-shadow:inset 0 0 0 1.2px var(--m15);margin-bottom:8px;font-size:13.5px"><span style="display:flex;gap:9px;align-items:center;font-weight:600"><svg class="ic" style="width:16px;height:16px"><use href="#{{ $ikon }}"/></svg>{{ $label }}</span><b style="font:600 13.5px var(--f-title);text-align:right">{{ $nilai }}</b></div>
        @endforeach
      </div>
      <div class="card f"><div class="card-head" style="margin-bottom:12px"><h2>Perlu Perhatian</h2><span class="pill c">{{ $perhatian->count() }}</span></div>
        <div style="display:grid;gap:12px;font:600 13.5px var(--f-body)">
          @forelse ($perhatian as $p)
            @if ($p['route'])
              <a href="{{ route($p['route'], $p['q']) }}" style="display:flex;gap:10px;align-items:center"><svg class="ic" style="width:17px;height:17px"><use href="#{{ $p['ikon'] }}"/></svg><span style="flex:1">{{ $p['teks'] }}</span><svg class="ic" style="width:15px;height:15px"><use href="#i-arr"/></svg></a>
            @else
              <div style="display:flex;gap:10px;align-items:center"><svg class="ic" style="width:17px;height:17px"><use href="#{{ $p['ikon'] }}"/></svg><span style="flex:1">{{ $p['teks'] }}</span></div>
            @endif
          @empty
            <div style="display:flex;gap:10px;align-items:center"><svg class="ic" style="width:17px;height:17px"><use href="#i-check"/></svg><span style="flex:1">Semua aman, tidak ada yang perlu ditindaklanjuti.</span></div>
          @endforelse
        </div></div>
    </div>
  </div>

  <div class="card" style="margin-top:18px"><div class="card-head"><div><h2>Pesanan Terbaru</h2><p>6 pesanan terakhir yang masuk ke sistem</p></div>
    <div class="chips" data-filter-for="tbPesanan"><button class="on" data-f="all">Semua</button>@foreach (Pesanan::LABEL_LAYANAN as $kode => $label)<button data-f="{{ $kode }}">{{ $label }}</button>@endforeach</div></div>
    <div class="tbl-wrap"><table><thead><tr><th>No. pesanan</th><th>Pelanggan</th><th>Layanan</th><th>Total</th><th>Status</th><th class="hide-md">Masuk</th></tr></thead><tbody id="tbPesanan">
      @forelse ($pesananTerbaru as $p)
        <tr data-f="{{ $p->jenis_layanan }}"><td><b>{{ $p->kode() }}</b></td><td>{{ $p->pelanggan?->nama ?? 'Pesanan di tempat' }}</td><td>{{ $p->layananLabel() }}</td><td>{{ $rp($p->total) }}</td><td><span class="pill {{ $p->statusPill() }}">{{ $p->statusLabel() }}</span></td><td class="hide-md">{{ $p->waktu_pesan->isToday() ? $p->waktu_pesan->format('H:i') : $p->waktu_pesan->translatedFormat('d M H:i') }}</td></tr>
      @empty
        <tr><td colspan="6"><div class="empty">Belum ada pesanan.</div></td></tr>
      @endforelse
    </tbody></table></div></div>

  <div class="grid g2" style="margin-top:18px">
    <div class="card"><div class="card-head"><h2>Pesanan per Layanan</h2><span class="pill b">{{ $totalHariIni }} pesanan hari ini</span></div>
      @foreach (Pesanan::LABEL_LAYANAN as $kode => $label)
        @php $n = $perLayanan[$kode] ?? 0; $pct = $totalHariIni ? round($n / $totalHariIni * 100) : 0; @endphp
        <div style="margin-bottom:14px"><div style="display:flex;justify-content:space-between;font:700 13.5px var(--f-accent);margin-bottom:7px"><span style="display:flex;gap:8px;align-items:center"><svg class="ic" style="width:16px;height:16px"><use href="#{{ $layananIkon[$kode] }}"/></svg>{{ $label }}</span><span>{{ $n }} pesanan · {{ $pct }}%</span></div><div class="bar dark"><i style="width:{{ $pct }}%"></i></div></div>
      @endforeach
    </div>
    <div class="card"><div class="card-head"><h2>Pengguna per Peran</h2><a class="btn o sm" href="{{ route('admin.pengguna') }}">Kelola</a></div>
      @foreach ($perRole->sortDesc() as $role => $n)
        <div style="display:grid;grid-template-columns:92px minmax(0,1fr) 28px;gap:10px;align-items:center;margin-bottom:10px;font:700 13px var(--f-accent)"><span>{{ ucfirst($role) }}</span><div class="bar dark"><i style="width:{{ round($n / $maxRole * 100) }}%"></i></div><span style="text-align:right;font-family:var(--f-title)">{{ $n }}</span></div>
      @endforeach
    </div>
  </div>
@endsection
