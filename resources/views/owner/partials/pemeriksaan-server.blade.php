{{-- Isi kartu Pemeriksaan Server di Keamanan Outlet (dimuat lewat tombol Tampilkan) --}}
@php $aman = collect($cekServer)->where('ok', true)->count(); @endphp
<div style="display:flex;justify-content:flex-end;margin-bottom:10px">
  <span @class(['pill', 'b' => $aman === count($cekServer), 'm' => $aman < count($cekServer)])>{{ $aman }} / {{ count($cekServer) }} aman</span>
</div>
<div class="daftar">
  @foreach ($cekServer as $c)
    <div class="it"><svg class="ic" style="color:{{ $c['ok'] ? 'inherit' : 'var(--m)' }}"><use href="#{{ $c['ok'] ? 'i-check' : 'i-alert' }}"/></svg>
      <div><b>{{ $c['label'] }}</b>@unless ($c['ok'])<small>{{ $c['saran'] }}</small>@endunless</div>
      <span @class(['pill', 'b' => $c['ok'], 'm' => ! $c['ok']])>{{ $c['ok'] ? 'Aman' : 'Perlu dibenahi' }}</span>
    </div>
  @endforeach
</div>
