@extends('layouts.panel')

@use('App\Models\JadwalShift')
@use('App\Services\AksesOutletService')
@use('App\Http\Controllers\Owner\KeamananOutletController')

@section('title', 'Keamanan Outlet')
@section('crumb', 'Owner / Keamanan Outlet')
@section('heading', 'Keamanan Outlet')
@section('sub', 'Staf hanya bisa masuk dari outlet, dari perangkat terdaftar, dan sesuai jadwal shift')
@section('error-sendiri', '1')
@section('delete-title', 'Hapus data ini?')
@section('delete-text', 'Perangkat yang dihapus tidak bisa dipakai staf lagi; data wajah yang dihapus harus didaftarkan ulang.')

@php
  $ipLama = old('ip_outlet', $pengaturan->ip_outlet);
  $aktif = fn (string $f) => (bool) old($f, $pengaturan->{$f});
@endphp

@push('styles')
<style>
  .sec-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 18px; align-items: start; }
  .switch-row + .switch-row { margin-top: 10px; }
  .ip-box textarea { width: 100%; min-height: 96px; font: 600 14px/1.6 ui-monospace, monospace; resize: vertical; }
  .kv { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; font-size: 13.5px; margin-top: 10px; }
  .daftar { display: grid; gap: 10px; }
  .daftar .it { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; padding: 12px 14px; border-radius: 14px; background: var(--pp); }
  .daftar .it > div { flex: 1; min-width: 180px; }
  .daftar .it small { display: block; opacity: .7; font-size: 12.5px; margin-top: 2px; }
  .kode-baru { font: 700 30px var(--f-title); letter-spacing: .12em; text-align: center; padding: 14px; border-radius: 16px; background: var(--bl); margin-bottom: 14px; }
  .shift-tbl td { vertical-align: middle; }
  .hari-row { display: grid; grid-template-columns: 110px 1fr 1fr; gap: 10px; align-items: center; padding: 6px 0; }
  .hari-row label.cek { display: flex; align-items: center; gap: 8px; font-weight: 700; }
  .hari-row .in { height: 42px; }
  .roster { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 10px; margin-bottom: 16px; }
  .roster .hr { padding: 10px 12px; border-radius: 14px; background: var(--pp); min-width: 0; }
  .roster .hr.kini { outline: 2px solid var(--m); }
  .roster .hr > b { display: flex; justify-content: space-between; gap: 6px; font-size: 13px; margin-bottom: 6px; }
  .roster .hr > b span { opacity: .6; font-weight: 600; }
  .roster ul { list-style: none; padding: 0; margin: 0; display: grid; gap: 6px; font-size: 12.5px; line-height: 1.3; }
  .roster li small { display: block; opacity: .7; }
  .roster .kosong { font-size: 12.5px; opacity: .55; }
  .modal.shift { display: flex; flex-direction: column; max-height: calc(100vh - 40px); }
  .modal.shift .mh { padding-bottom: 14px; border-bottom: 1.5px solid var(--m15); }
  .modal.shift .mb { overflow-y: auto; }
  .hari-row .rekan { grid-column: 2 / -1; font-size: 12px; opacity: .7; margin-top: -2px; }
  @media (max-width: 1100px) { .roster { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
  @media (max-width: 900px) { .sec-grid { grid-template-columns: 1fr; } }
  @media (max-width: 520px) { .hari-row { grid-template-columns: 1fr 1fr; } .hari-row label.cek, .hari-row .rekan { grid-column: 1 / -1; } .roster { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>
@endpush

@section('content')
  @unless (old('_modal'))@include('partials.auth-alert')@endunless

  <form method="post" action="{{ route('owner.keamanan.aturan') }}">
    @csrf
    @method('PUT')
    <div class="sec-grid">
      <div class="card"><div class="card-head"><div><h2>Lokasi Outlet</h2><p>Berlaku untuk Admin, Kasir, dan Dapur. Kurir dan Owner tidak dibatasi lokasi.</p></div></div>
        <div class="field ip-box"><label for="ipOutlet">IP WiFi outlet (satu per baris)</label>
          <div class="in ta"><textarea id="ipOutlet" name="ip_outlet" placeholder="Kosongkan bila tidak memakai cek IP">{{ $ipLama }}</textarea></div></div>
        <div class="kv"><span>IP perangkat ini sekarang: <b>{{ $ipSaya }}</b></span>
          @if ($ipDiOutlet)<span class="pill b">sudah terdaftar</span>@else<button type="button" class="btn sm o" id="tambahIp" data-ip="{{ $ipSaya }}"><svg class="ic"><use href="#i-plus"/></svg>Tambahkan IP ini</button>@endif</div>
        <div class="note" style="margin-top:12px"><svg class="ic"><use href="#i-info"/></svg><span>Buka halaman ini dari komputer yang tersambung ke WiFi outlet, lalu tekan <b>Tambahkan IP ini</b>. Cek IP hanya bisa diandalkan bila internet outlet memakai <b>IP statis</b>; kalau IP-nya sering berganti, nyalakan juga cek GPS sebagai cadangan.</span></div>
        <div style="margin-top:14px">
          <label class="switch-row"><span>Cek GPS bila tidak di WiFi outlet<small>Browser meminta izin lokasi; posisi dicek ulang tiap 5 menit selama sesi</small></span><input type="hidden" name="cek_gps" value="0"><span class="toggle" style="flex:none"><input type="checkbox" name="cek_gps" value="1" @checked($aktif('cek_gps'))><i></i></span></label>
        </div>
        <div class="field" style="margin-top:12px"><label for="radius">Radius outlet</label><div class="in"><svg class="ic"><use href="#i-pin"/></svg><input id="radius" type="number" name="radius_outlet_m" value="{{ old('radius_outlet_m', $pengaturan->radius_outlet_m) }}" min="20" max="1000" required><span class="suf">meter</span></div></div>
        @if ($titikDefault)
          <div class="note" style="margin-top:12px;background:var(--m);color:var(--cr)"><svg class="ic"><use href="#i-alert"/></svg><span>Titik restoran di peta masih titik bawaan. Minta Admin menyesuaikannya di Pengaturan Sistem sebelum memakai cek GPS.</span></div>
        @endif
      </div>

      <div class="card"><div class="card-head"><div><h2>Aturan Masuk Staf</h2><p>Semua aturan mati secara bawaan</p></div></div>
        <label class="switch-row"><span>Wajib perangkat terdaftar<small>Admin, Kasir, dan Dapur hanya bisa masuk dari PC/tablet outlet di bawah</small></span><input type="hidden" name="wajib_perangkat" value="0"><span class="toggle" style="flex:none"><input type="checkbox" name="wajib_perangkat" value="1" @checked($aktif('wajib_perangkat'))><i></i></span></label>
        <label class="switch-row"><span>Wajib sesuai jadwal shift<small>Masuk mulai {{ JadwalShift::MASUK_LEBIH_AWAL_MENIT }} menit sebelum shift sampai {{ JadwalShift::TOLERANSI_PULANG_MENIT }} menit sesudahnya; sesi berakhir setelahnya</small></span><input type="hidden" name="wajib_shift" value="0"><span class="toggle" style="flex:none"><input type="checkbox" name="wajib_shift" value="1" @checked($aktif('wajib_shift'))><i></i></span></label>
        <label class="switch-row"><span>Wajib verifikasi wajah<small>Staf yang wajahnya sudah didaftarkan wajib lolos cek wajah saat masuk; aksi sensitif minta wajah + password lagi</small></span><input type="hidden" name="wajib_wajah" value="0"><span class="toggle" style="flex:none"><input type="checkbox" name="wajib_wajah" value="1" @checked($aktif('wajib_wajah'))><i></i></span></label>
        @if ($tanpaJadwal)
          <div class="note" style="margin-top:12px"><svg class="ic"><use href="#i-info"/></svg><span>{{ $tanpaJadwal }} staf belum punya jadwal shift. Staf tanpa jadwal tetap bisa masuk kapan saja.</span></div>
        @endif
        <div class="note" style="margin-top:12px"><svg class="ic"><use href="#i-lock"/></svg><span>Staf yang ditolak bisa masuk dengan <b>kode darurat</b> dari Owner. Setiap percobaan dari luar outlet dan setiap pemakaian kode tercatat di <a href="{{ route('owner.aktivitas', ['kategori' => 'keamanan']) }}"><b>Log Aktivitas</b></a>.</span></div>
        <button class="btn" type="submit" style="margin-top:16px;width:100%"><svg class="ic"><use href="#i-check"/></svg>Simpan Aturan</button>
      </div>
    </div>
  </form>

  <div class="sec-grid" style="margin-top:18px">
    <div class="card"><div class="card-head"><div><h2>Perangkat Terdaftar</h2><p>PC/tablet kasir &amp; dapur yang boleh dipakai staf</p></div><span class="pill b">{{ $perangkat->count() }} perangkat</span></div>
      @if ($perangkatIni)
        <div class="note" style="margin-bottom:12px"><svg class="ic"><use href="#i-check"/></svg><span>Perangkat yang sedang kamu pakai terdaftar sebagai <b>{{ $perangkatIni->nama }}</b>.</span></div>
      @else
        <form method="post" action="{{ route('owner.keamanan.perangkat') }}" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px">
          @csrf
          <div class="in" style="flex:1;min-width:180px"><svg class="ic"><use href="#i-grid"/></svg><input name="nama" maxlength="60" placeholder="Nama, contoh: PC Kasir 1" value="{{ old('nama') }}" required></div>
          <button class="btn" type="submit"><svg class="ic"><use href="#i-plus"/></svg>Daftarkan perangkat ini</button>
        </form>
      @endif
      <div class="daftar">
        @forelse ($perangkat as $p)
          <div class="it"><svg class="ic"><use href="#i-grid"/></svg>
            <div><b>{{ $p->nama }}</b> @if ($perangkatIni?->is($p))<span class="pill c">perangkat ini</span>@endif
              <small>{{ $p->keterangan_perangkat }} · didaftarkan {{ $p->pendaftar?->nama ?? '—' }} {{ $p->created_at->translatedFormat('d M Y') }} · {{ $p->terakhir_dipakai_at ? 'terakhir dipakai '.$p->terakhir_dipakai_at->diffForHumans() : 'belum dipakai' }}</small></div>
            <button class="ab f js-del" data-url="{{ route('owner.keamanan.perangkat.hapus', $p) }}" data-name="{{ $p->nama }}" title="Hapus"><svg class="ic"><use href="#i-trash"/></svg></button>
          </div>
        @empty
          <div class="empty">Belum ada perangkat. Buka halaman ini dari tiap PC/tablet outlet (login sebagai Owner), lalu daftarkan.</div>
        @endforelse
      </div>
    </div>

    <div class="card"><div class="card-head"><div><h2>Kode Darurat</h2><p>Saat internet/GPS outlet bermasalah, staf tetap bisa masuk</p></div></div>
      @if (session('kode_baru'))
        <div class="kode-baru" id="kodeBaru">{{ session('kode_baru') }}</div>
        <p style="font-size:13px;opacity:.75;text-align:center;margin:-6px 0 14px">Kode hanya ditampilkan sekali. Catat sekarang.</p>
      @endif
      <form method="post" action="{{ route('owner.keamanan.kode') }}" style="display:grid;gap:10px">
        @csrf
        <div class="in"><svg class="ic"><use href="#i-info"/></svg><input name="alasan" maxlength="200" placeholder="Alasan, contoh: WiFi outlet mati" value="{{ old('alasan') }}" required></div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <div class="in" style="flex:1;min-width:150px"><svg class="ic"><use href="#i-clock"/></svg><select name="durasi" style="border:0;background:transparent;font:inherit;width:100%">
            @foreach (KeamananOutletController::DURASI_KODE_JAM as $j)<option value="{{ $j }}" @selected((int) old('durasi', 4) === $j)>Berlaku {{ $j }} jam</option>@endforeach
          </select></div>
          <button class="btn" type="submit"><svg class="ic"><use href="#i-plus"/></svg>Buat kode</button>
        </div>
      </form>
      <div class="daftar" style="margin-top:14px">
        @forelse ($kode as $k)
          <div class="it"><svg class="ic"><use href="#i-lock"/></svg>
            <div><b>{{ $k->alasan }}</b><small>sampai {{ $k->berlaku_sampai->translatedFormat('d M, H.i') }} · dipakai {{ $k->dipakai }} kali · dibuat {{ $k->owner?->nama ?? '—' }}</small></div>
            <form method="post" action="{{ route('owner.keamanan.kode.cabut', $k) }}">@csrf @method('DELETE')<button class="btn sm o" type="submit">Cabut</button></form>
          </div>
        @empty
          <div class="empty">Tidak ada kode darurat yang aktif.</div>
        @endforelse
      </div>
    </div>
  </div>

  <div class="card" style="margin-top:18px"><div class="card-head"><div><h2>Jadwal Shift</h2><p>Sekaligus absensi: jam masuk dan keterlambatan tercatat di Log Aktivitas</p></div></div>
    <div class="roster" aria-label="Siapa masuk tiap hari">
      @foreach (JadwalShift::HARI as $h => $namaHari)
        <div @class(['hr', 'kini' => now()->dayOfWeekIso === $h])>
          <b>{{ $namaHari }}<span>{{ count($shiftPerHari[$h]) }} staf</span></b>
          @if ($shiftPerHari[$h])
            <ul>@foreach ($shiftPerHari[$h] as $s)<li><b>{{ $s['nama'] }}</b><small>{{ $s['peran'] }} · {{ $s['jam'] }}</small></li>@endforeach</ul>
          @else
            <div class="kosong">Belum ada yang dijadwalkan</div>
          @endif
        </div>
      @endforeach
    </div>
    <div class="tbl-wrap"><table class="tbl-stack shift-tbl"><thead><tr><th>Staf</th><th>Peran</th><th>Jadwal mingguan</th><th>Aksi</th></tr></thead><tbody>
      @forelse ($staf as $u)
        <tr>
          <td><b>{{ $u->nama }}</b></td>
          <td><span class="pill b">{{ $u->roleLabel() }}</span></td>
          <td style="white-space:normal">{!! AksesOutletService::ringkasJadwal($u) ? e(AksesOutletService::ringkasJadwal($u)) : '<span style="opacity:.6">Belum diatur · bebas masuk</span>' !!}</td>
          <td><button class="btn sm o" data-open="mShift{{ $u->id }}"><svg class="ic"><use href="#i-cal"/></svg>Atur</button></td>
        </tr>
      @empty
        <tr><td colspan="4"><div class="empty">Belum ada akun staf.</div></td></tr>
      @endforelse
    </tbody></table></div>
  </div>
  <div class="card" style="margin-top:18px" id="server"><div class="card-head"><div><h2>Pemeriksaan Server</h2><p>Pengaturan server yang perlu dibereskan sebelum dipakai sungguhan</p></div>
      <span @class(['pill', 'b' => collect($cekServer)->every('ok'), 'm' => ! collect($cekServer)->every('ok')])>{{ collect($cekServer)->where('ok', true)->count() }} / {{ count($cekServer) }} aman</span></div>
    <div class="daftar">
      @foreach ($cekServer as $c)
        <div class="it"><svg class="ic" style="color:{{ $c['ok'] ? 'inherit' : 'var(--m)' }}"><use href="#{{ $c['ok'] ? 'i-check' : 'i-alert' }}"/></svg>
          <div><b>{{ $c['label'] }}</b>@unless ($c['ok'])<small>{{ $c['saran'] }}</small>@endunless</div>
          <span @class(['pill', 'b' => $c['ok'], 'm' => ! $c['ok']])>{{ $c['ok'] ? 'Aman' : 'Perlu dibenahi' }}</span>
        </div>
      @endforeach
    </div>
  </div>

  <div class="card" style="margin-top:18px" id="wajah"><div class="card-head"><div><h2>Verifikasi Wajah</h2><p>Hanya angka ciri wajah (terenkripsi) yang disimpan, bukan foto. Daftarkan di outlet dengan staf hadir dan sudah memberi persetujuan tertulis.</p></div>
      <span class="pill b">{{ $semuaStaf->filter(fn ($u) => $u->wajah)->count() }} / {{ $semuaStaf->count() }} terdaftar</span></div>
    <div class="tbl-wrap"><table class="tbl-stack shift-tbl"><thead><tr><th>Staf</th><th>Peran</th><th>Data wajah</th><th>Aksi</th></tr></thead><tbody>
      @foreach ($semuaStaf as $u)
        <tr>
          <td><b>{{ $u->nama }}</b>@if ($u->is(auth()->user())) <span class="pill c">Anda</span>@endif</td>
          <td><span class="pill b">{{ $u->roleLabel() }}</span></td>
          <td style="white-space:normal">@if ($u->wajah)Terdaftar {{ $u->wajah->created_at->translatedFormat('d M Y') }} · {{ $u->wajah->jumlah_sampel }} sampel @else<span style="opacity:.6">Belum didaftarkan · masuk tanpa cek wajah</span>@endif</td>
          <td><div class="acts">
            <a class="btn sm o" href="{{ route('owner.wajah', $u) }}"><svg class="ic"><use href="#i-user"/></svg>{{ $u->wajah ? 'Daftar ulang' : 'Daftarkan' }}</a>
            @if ($u->wajah)<button class="ab f js-del" data-url="{{ route('owner.wajah.hapus', $u) }}" data-name="Data wajah {{ $u->nama }}" title="Hapus data wajah"><svg class="ic"><use href="#i-trash"/></svg></button>@endif
          </div></td>
        </tr>
      @endforeach
    </tbody></table></div>
  </div>
@endsection

@push('modals')
@foreach ($staf as $u)
  @php $jadwal = $u->jadwalShift->keyBy('hari'); $modal = 'mShift'.$u->id; $pakaiOld = old('_modal') === $modal; @endphp
  <div class="ovl" id="{{ $modal }}"><form class="modal shift" method="post" action="{{ route('owner.keamanan.shift', $u) }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="_modal" value="{{ $modal }}">
    <div class="mh"><div><div class="eyebrow" style="opacity:.7">Atur jadwal shift untuk</div><h3>{{ $u->nama }} <span class="pill b" style="vertical-align:middle">{{ $u->roleLabel() }}</span></h3></div><button type="button" class="icon-btn" data-close><svg class="ic"><use href="#i-x"/></svg></button></div>
    <div class="mb">
      @if ($pakaiOld) @include('partials.panel.form-errors', ['modal' => $modal]) @endif
      @foreach (JadwalShift::HARI as $h => $nama)
        @php
          $ada = $jadwal->get($h);
          $cek = $pakaiOld ? (bool) old("hari.$h.aktif") : (bool) $ada;
          $mulai = $pakaiOld ? old("hari.$h.mulai") : ($ada ? substr($ada->jam_mulai, 0, 5) : '08:00');
          $selesai = $pakaiOld ? old("hari.$h.selesai") : ($ada ? substr($ada->jam_selesai, 0, 5) : '16:00');
          $rekan = collect($shiftPerHari[$h])->reject(fn ($s) => $s['id'] === $u->id);
        @endphp
        <div class="hari-row">
          <label class="cek"><input type="checkbox" name="hari[{{ $h }}][aktif]" value="1" @checked($cek)>{{ $nama }}</label>
          <div class="in"><input type="time" name="hari[{{ $h }}][mulai]" value="{{ $mulai }}" aria-label="Jam mulai {{ $nama }}"></div>
          <div class="in"><input type="time" name="hari[{{ $h }}][selesai]" value="{{ $selesai }}" aria-label="Jam selesai {{ $nama }}"></div>
          <div class="rekan">{{ $rekan->isEmpty() ? 'Belum ada staf lain yang masuk' : 'Juga masuk: '.$rekan->map(fn ($s) => "{$s['nama']} ({$s['peran']}) {$s['jam']}")->implode(', ') }}</div>
        </div>
      @endforeach
      <div class="note" style="margin-top:10px"><svg class="ic"><use href="#i-info"/></svg><span>Hari yang tidak dicentang = libur. Jam selesai lebih awal dari jam mulai berarti selesai keesokan harinya (contoh 22:00–06:00). Kosongkan semua centang agar staf bebas masuk.</span></div>
    </div>
    <div class="mf"><button type="button" class="btn o" data-close>Batal</button><button class="btn" type="submit"><svg class="ic"><use href="#i-check"/></svg>Simpan Jadwal</button></div>
  </form></div>
@endforeach
@endpush

@push('scripts')
<script>
  (function () {
    var b = document.getElementById('tambahIp'), t = document.getElementById('ipOutlet');
    if (b) b.addEventListener('click', function () {
      var ip = b.dataset.ip, baris = t.value.split(/\n/).map(function (x) { return x.trim(); }).filter(Boolean);
      if (baris.indexOf(ip) < 0) baris.push(ip);
      t.value = baris.join('\n'); b.disabled = true; b.textContent = 'Ditambahkan · tekan Simpan Aturan';
    });
  })();
</script>
@endpush
