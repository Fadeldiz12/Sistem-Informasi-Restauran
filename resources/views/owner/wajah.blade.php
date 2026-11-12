@extends('layouts.panel')

@section('title', 'Daftarkan Wajah')
@section('crumb', 'Owner / Keamanan Outlet / Daftarkan Wajah')
@section('heading', 'Daftarkan Wajah '.$staf->nama)
@section('sub', $staf->roleLabel().' · '.$staf->email)
@section('error-sendiri', '1')

@section('content')
  @if ($errors->any())<div class="note" role="alert" style="margin-bottom:14px;background:var(--m);color:var(--cr)"><svg class="ic"><use href="#i-alert"/></svg><span>{{ $errors->first() }}</span></div>@endif

  <div class="grid split" style="grid-template-columns:minmax(0,1.1fr) minmax(0,.9fr);align-items:start">
    <div class="card">
        <div class="card-head"><div><h2>Ambil Ciri Wajah</h2><p>{{ $staf->nama }} berdiri di depan kamera dan mengikuti gerakan. Diambil 6 sampel ciri wajah; foto tidak disimpan.</p></div></div>
        <form method="post" action="{{ route('owner.wajah.store', $staf) }}" style="display:grid;gap:14px">
          @csrf
          <label class="switch-row"><span>{{ $staf->nama }} sudah memberi persetujuan tertulis untuk pengambilan data wajah<small>Data wajah hanya dipakai untuk masuk ke Resto Flow dan dihapus saat staf berhenti</small></span><input type="checkbox" name="setuju" value="1" required @checked(old('setuju')) style="width:22px;height:22px"></label>
          @include('partials.kamera-wajah', ['tantangan' => $tantangan, 'tantanganBaru' => route('owner.wajah.tantangan', $staf), 'sampel' => 5, 'setelah' => 'kirim', 'tombol' => 'Mulai ambil wajah'])
        </form>
    </div>
    <div class="card"><div class="card-head"><div><h2>Petunjuk</h2></div></div>
      <ol style="padding-left:18px;display:grid;gap:8px;font-size:14px;line-height:1.5">
        <li>Lakukan di outlet, dengan staf hadir langsung. Jangan daftarkan dari foto atau video.</li>
        <li>Cahaya cukup dari depan, tanpa masker atau kacamata hitam. Hanya satu wajah di kamera.</li>
        <li>Layar akan berkedip warna sebentar (cek pantulan cahaya di kulit, menolak wajah dari layar HP/video). Naikkan kecerahan layar.</li>
        <li>Ikuti 3 gerakan yang muncul: kedip plus 2 acak (menoleh kiri/kanan atau buka mulut). Jangan bergerak sebelum instruksi muncul.</li>
        <li>Kamera virtual (OBS, ManyCam, DroidCam, dll.) ditolak; pakai kamera asli perangkat.</li>
        <li><b>Satu wajah hanya untuk satu akun.</b> Wajah yang sudah terdaftar di akun lain (role apa pun) akan ditolak.</li>
        <li>Kamera hanya berjalan lewat <b>HTTPS</b> atau <b>http://localhost</b>.</li>
      </ol>
    </div>
  </div>
@endsection
