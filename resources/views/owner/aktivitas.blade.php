@extends('layouts.panel')

@use('App\Models\LogAktivitas')

@section('title', 'Log Aktivitas')
@section('crumb', 'Owner / Log Aktivitas')
@section('heading', 'Log Aktivitas')
@section('sub', 'Siapa melakukan apa dan kapan: login, ubah harga, hapus data, tandai lunas, dan lainnya')
@section('error-sendiri', '1')

@php
  $filter = fn (array $ubah) => route('owner.aktivitas', array_filter(array_merge(['dari' => $dari->toDateString(), 'sampai' => $sampai->toDateString(), 'kategori' => $kategori, 'q' => $q, 'peringatan' => $peringatan ? request('peringatan') : null], $ubah)));
@endphp

@push('styles')
<style>
  .log-tools { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
  .log-tools .in { height: 42px; }
  .log-tools input[type=date] { min-width: 140px; }
  td.ket { white-space: normal; min-width: 240px; line-height: 1.45; }
  td.ket small { display: block; opacity: .6; font-size: 12px; margin-top: 2px; }
  tr.penting td:first-child { box-shadow: inset 4px 0 0 var(--m); }
  .waktu b { display: block; }
  .waktu small { opacity: .65; }
  @media (max-width: 760px) {
    .log-tools, .log-tools form { width: 100%; } .log-tools .in { flex: 1; }
    tr.penting td:first-child { box-shadow: none; }
    tr.penting { border-left: 4px solid var(--m); padding-left: 12px; }
  }
</style>
@endpush

@section('content')
  @include('partials.auth-alert')

  <div class="grid g3">
    <div class="card stat f"><div class="top"><span class="eyebrow">Login staf hari ini</span><span class="ico"><svg class="ic"><use href="#i-user"/></svg></span></div><div class="v">{{ $loginHariIni }}</div><div class="t">{{ today()->translatedFormat('l, j F Y') }}</div></div>
    <div @class(['card', 'stat', 'b' => ! $gagal7Hari])><div class="top"><span class="eyebrow">Password salah</span><span class="ico"><svg class="ic"><use href="#i-alert"/></svg></span></div><div class="v">{{ $gagal7Hari }}</div><div class="t">Percobaan masuk staf yang gagal, 7 hari terakhir</div></div>
    <a class="card stat" href="{{ route('owner.pengguna') }}#akunTerkunci" style="text-decoration:none;color:inherit{{ $terkunci ? ';border:2px solid var(--m)' : '' }}"><div class="top"><span class="eyebrow">Akun terkunci</span><span class="ico"><svg class="ic"><use href="#i-lock"/></svg></span></div><div class="v">{{ $terkunci }}</div><div class="t">{{ $terkunci ? 'Buka di Pengguna & Role' : 'Tidak ada akun terkunci' }}</div></a>
  </div>

  <div class="card" style="margin-top:18px" id="peringatan">
    <div class="card-head"><div><h2>Peringatan Keamanan 24 Jam Terakhir</h2><p>Ringkasan juga dikirim ke email Owner tiap pagi bila ada kejadian</p></div>
      <span @class(['pill', 'm' => $ringkasan['total'], 'b' => ! $ringkasan['total']])>{{ $ringkasan['total'] }} kejadian</span></div>
    @if ($ringkasan['beruntun'])
      <div class="note" style="margin-bottom:12px;background:var(--m);color:var(--cr)"><svg class="ic"><use href="#i-alert"/></svg><span><b>Percobaan gagal beruntun:</b>
        {!! collect($ringkasan['beruntun'])->map(fn ($b) => e($b['jenis']).' <b>'.e($b['nilai']).'</b> ('.$b['jumlah'].'×)')->implode(', ') !!}.
        Bila bukan staf yang lupa password, ganti password akun terkait dan aktifkan verifikasi 2 langkah.</span></div>
    @endif
    @forelse ($ringkasan['kelompok'] as $k)
      <a class="note" href="{{ route('owner.aktivitas', ['peringatan' => $k['kunci'], 'dari' => now()->subDay()->toDateString()]) }}#riwayat" style="margin-top:8px;text-decoration:none;color:inherit">
        <svg class="ic"><use href="#i-alert"/></svg><span><b>{{ $k['label'] }}: {{ $k['jumlah'] }}×</b> · {{ $k['saran'] }}</span></a>
    @empty
      <div class="empty">Tidak ada kejadian keamanan dalam 24 jam terakhir.</div>
    @endforelse
  </div>

  <div class="card" style="margin-top:18px" id="riwayat">
    <div class="card-head"><div><h2>Riwayat Aktivitas</h2><p>{{ $dari->translatedFormat('j M Y') }} – {{ $sampai->translatedFormat('j M Y') }} · catatan tidak bisa diubah atau dihapus</p>
        <p style="margin-top:6px"><span @class(['pill', 'b' => $rantai['utuh'], 'm' => ! $rantai['utuh']]) title="Diperiksa {{ \Illuminate\Support\Carbon::parse($rantai['waktu'])->diffForHumans() }}">{{ $rantai['utuh'] ? '✓ Rantai hash utuh · '.number_format($rantai['jumlah'], 0, ',', '.').' catatan' : '✗ Rantai hash RUSAK' }}</span></p>
        @unless ($rantai['utuh'])<div class="note" style="margin-top:8px;background:var(--m);color:var(--cr)"><svg class="ic"><use href="#i-alert"/></svg><span>{{ implode(' ', $rantai['masalah']) }}</span></div>@endunless</div>
      <form method="get" action="{{ route('owner.aktivitas') }}" class="log-tools">
        @if ($kategori)<input type="hidden" name="kategori" value="{{ $kategori }}">@endif
        @if ($peringatan)<input type="hidden" name="peringatan" value="{{ request('peringatan') }}">@endif
        <div class="in"><input type="date" name="dari" value="{{ $dari->toDateString() }}" max="{{ today()->toDateString() }}" aria-label="Dari tanggal"></div>
        <div class="in"><input type="date" name="sampai" value="{{ $sampai->toDateString() }}" max="{{ today()->toDateString() }}" aria-label="Sampai tanggal"></div>
        <div class="search keep"><svg class="ic"><use href="#i-search"/></svg><input name="q" value="{{ $q }}" placeholder="Cari nama, aktivitas, atau IP…"></div>
        <button class="btn" type="submit">Tampilkan</button>
      </form></div>
    <div class="chips" style="margin-bottom:12px">
      @if ($peringatan)<a class="on" href="{{ $filter(['peringatan' => null]) }}">{{ $peringatan['label'] }} ✕</a>@endif
      <a @class(['on' => ! $kategori && ! $peringatan]) href="{{ $filter(['kategori' => null, 'peringatan' => null]) }}">Semua</a>
      @foreach (LogAktivitas::KATEGORI as $k => $info)<a @class(['on' => $kategori === $k]) href="{{ $filter(['kategori' => $k, 'peringatan' => null]) }}">{{ $info['label'] }}</a>@endforeach
    </div>
    <div class="tbl-wrap"><table class="tbl-stack"><thead><tr><th>Waktu</th><th>Pengguna</th><th>Aktivitas</th><th class="hide-md">Perangkat</th></tr></thead><tbody>
      @forelse ($log as $l)
        <tr @class(['penting' => $l->penting()])>
          <td class="waktu"><b>{{ $l->created_at->format('d/m/Y') }}</b><small>{{ $l->created_at->format('H.i.s') }}</small></td>
          <td>@if ($l->nama_user)<b>{{ $l->nama_user }}</b><br><span class="pill b" style="margin-top:4px">{{ \App\Models\User::LABEL_ROLE[$l->role_user] ?? $l->role_user }}</span>@else<span style="opacity:.6">Sistem</span>@endif</td>
          <td class="ket">@if ($l->penting())<span class="pill m" style="margin-right:6px">Perlu dicek</span>@endif{{ $l->keterangan }}<small>{{ $l->labelKategori() }}</small></td>
          <td class="hide-md" style="white-space:normal">{{ $l->perangkatSingkat() }}<br><small style="opacity:.65">IP {{ $l->ip ?? '—' }}</small></td>
        </tr>
      @empty
        <tr><td colspan="4"><div class="empty">{{ $q !== '' || $kategori ? 'Tidak ada aktivitas yang cocok.' : 'Belum ada aktivitas pada periode ini.' }}</div></td></tr>
      @endforelse
    </tbody></table></div>
    <div class="tfoot"><span style="opacity:.7">Menampilkan {{ $log->count() }} dari {{ $log->total() }} aktivitas</span>
      @include('partials.panel.pager', ['paginator' => $log])</div>
  </div>
@endsection
