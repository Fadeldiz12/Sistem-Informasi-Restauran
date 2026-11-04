@extends('layouts.panel')

@section('title', 'Data Pengguna')
@section('crumb', 'Admin / Data Pengguna')
@section('heading', 'Data Pengguna')
@section('sub', 'Kelola akun pengguna beserta perannya')
@section('delete-title', 'Hapus pengguna ini?')

@use('App\Models\User')
@php
  $staf = $total - ($perRole[User::ROLE_PELANGGAN] ?? 0);
  $editId = old('_modal') === 'mUser' ? old('_id') : null;
@endphp

@section('content')
  <div class="grid g4" style="margin-bottom:18px">
    <div class="card stat f"><div class="top"><span class="eyebrow">Total akun</span><span class="ico"><svg class="ic"><use href="#i-users"/></svg></span></div><div class="v">{{ $total }}</div><div class="t">Terdaftar di sistem</div></div>
    <div class="card stat "><div class="top"><span class="eyebrow">Staf internal</span><span class="ico"><svg class="ic"><use href="#i-user"/></svg></span></div><div class="v">{{ $staf }}</div><div class="t">Admin, Owner, Kasir, Dapur, Kurir</div></div>
    <div class="card stat b"><div class="top"><span class="eyebrow">Pelanggan</span><span class="ico"><svg class="ic"><use href="#i-users"/></svg></span></div><div class="v">{{ $perRole[User::ROLE_PELANGGAN] ?? 0 }}</div><div class="t">Mendaftar lewat halaman registrasi</div></div>
    <div class="card stat p"><div class="top"><span class="eyebrow">Pelanggan baru</span><span class="ico"><svg class="ic"><use href="#i-plus"/></svg></span></div><div class="v">{{ $pelangganBaru }}</div><div class="t">Mendaftar bulan ini</div></div>
  </div>

  <div class="card">
    <div class="card-head">
      <div class="chips">
        <a @class(['on' => ! $role]) href="{{ route('admin.pengguna', array_filter(['q' => $q])) }}">Semua<span>{{ $total }}</span></a>
        @foreach (User::ROLES as $r)
          <a @class(['on' => $role === $r]) href="{{ route('admin.pengguna', array_filter(['role' => $r, 'q' => $q])) }}">{{ ucfirst($r) }}<span>{{ $perRole[$r] ?? 0 }}</span></a>
        @endforeach
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <form method="get" action="{{ route('admin.pengguna') }}" class="search keep" style="background:var(--pp)">
          @if ($role)<input type="hidden" name="role" value="{{ $role }}">@endif
          <svg class="ic"><use href="#i-search"/></svg><input name="q" value="{{ $q }}" placeholder="Cari nama, email, atau HP…">
        </form>
        <button class="btn" id="btnAdd"><svg class="ic"><use href="#i-plus"/></svg>Tambah Pengguna</button>
      </div>
    </div>

    <div class="tbl-wrap"><table><thead><tr><th>Nama</th><th>Email</th><th>Nomor HP</th><th>Peran</th><th class="hide-md">Terdaftar</th><th>Aksi</th></tr></thead>
      <tbody id="tbUser">
        @forelse ($users as $u)
          <tr data-id="{{ $u->id }}" data-url="{{ route('admin.pengguna.update', $u) }}" data-nama="{{ $u->nama }}" data-email="{{ $u->email }}" data-hp="{{ $u->no_hp }}" data-role="{{ $u->role }}">
            <td><div class="who"><span @class(['av', 'm' => $u->hasRole(User::ROLE_ADMIN, User::ROLE_OWNER)])>{{ $u->inisial() }}</span><b>{{ $u->nama }}</b>@if ($u->is(auth()->user()))<span class="pill c">Anda</span>@endif</div></td>
            <td>{{ $u->email }}</td>
            <td>{{ $u->no_hp }}</td>
            <td><span class="pill b">{{ ucfirst($u->role) }}</span></td>
            <td class="hide-md">{{ $u->created_at?->translatedFormat('d M Y') }}</td>
            <td><div class="acts">
              <button class="ab js-edit" title="Ubah"><svg class="ic"><use href="#i-edit"/></svg></button>
              @unless ($u->is(auth()->user()))
                <button class="ab f js-del" data-url="{{ route('admin.pengguna.destroy', $u) }}" data-name="{{ $u->nama }}" title="Hapus"><svg class="ic"><use href="#i-trash"/></svg></button>
              @endunless
            </div></td>
          </tr>
        @empty
          <tr><td colspan="6"><div class="empty">Tidak ada pengguna yang cocok.</div></td></tr>
        @endforelse
      </tbody></table></div>
    <div class="tfoot"><span style="opacity:.7">Menampilkan {{ $users->count() }} dari {{ $users->total() }} pengguna</span>
      @include('partials.panel.pager', ['paginator' => $users])</div>
  </div>
@endsection

@push('modals')
<div class="ovl" id="mUser"><form class="modal" method="post" id="fUser"
    action="{{ $editId ? route('admin.pengguna.update', $editId) : route('admin.pengguna.store') }}">
  @csrf
  <input type="hidden" name="_method" value="PUT" @disabled(! $editId)>
  <input type="hidden" name="_modal" value="mUser">
  <input type="hidden" name="_id" value="{{ $editId }}">
  <div class="mh"><div><div class="eyebrow" style="opacity:.7">Data pengguna</div><h3 id="uTitle">{{ $editId ? 'Ubah Pengguna' : 'Tambah Pengguna' }}</h3></div><button type="button" class="icon-btn" data-close><svg class="ic"><use href="#i-x"/></svg></button></div>
  <div class="mb">
    @include('partials.panel.form-errors', ['modal' => 'mUser'])
    <div class="fgrid">
      <div class="field"><label>Nama lengkap</label><div class="in"><svg class="ic"><use href="#i-user"/></svg><input name="nama" id="uName" value="{{ old('nama') }}" maxlength="100" placeholder="Nama sesuai identitas" required></div></div>
      <div class="field"><label>Nomor HP</label><div class="in"><svg class="ic"><use href="#i-phone"/></svg><input name="no_hp" id="uHp" value="{{ old('no_hp') }}" maxlength="20" inputmode="tel" placeholder="08xx xxxx xxxx" required></div></div>
      <div class="field full"><label>Email</label><div class="in"><svg class="ic"><use href="#i-mail"/></svg><input name="email" id="uEmail" type="email" value="{{ old('email') }}" maxlength="100" placeholder="nama@simresto.test" required></div></div>
      <div class="field full"><label>Peran (role)</label>
        <input type="hidden" name="role" id="uRoleInput" value="{{ old('role', User::ROLE_KASIR) }}">
        <div class="seg" id="uRole" data-input="uRoleInput">
          @foreach (User::ROLES as $r)
            <button type="button" data-value="{{ $r }}" @class(['on' => old('role', User::ROLE_KASIR) === $r])>{{ ucfirst($r) }}</button>
          @endforeach
        </div></div>
      <div class="field"><label>Password</label><div class="in"><svg class="ic"><use href="#i-lock"/></svg><input type="password" name="password" id="uPass" minlength="8" placeholder="Minimal 8 karakter" @required(! $editId)></div></div>
      <div class="field"><label>Konfirmasi password</label><div class="in"><svg class="ic"><use href="#i-lock"/></svg><input type="password" name="password_confirmation" placeholder="Ulangi password"></div></div>
    </div>
    <p id="uPassNote" style="font-size:12.5px;opacity:.7;margin-top:12px" @if (! $editId) hidden @endif>Kosongkan password jika tidak ingin mengubahnya.</p>
  </div>
  <div class="mf"><button type="button" class="btn o" data-close>Batal</button><button class="btn" type="submit"><svg class="ic"><use href="#i-check"/></svg>Simpan</button></div>
</form></div>
@endpush

@push('scripts')
<script>
  (function () {
    var form = document.getElementById('fUser');
    function setRole(role) {
      document.getElementById('uRoleInput').value = role;
      document.querySelectorAll('#uRole button').forEach(function (x) { x.classList.toggle('on', x.dataset.value === role); });
    }
    function mode(edit, tr) {
      form.querySelector('.ferr') && form.querySelector('.ferr').remove();
      form.action = edit ? tr.dataset.url : @json(route('admin.pengguna.store'));
      form.querySelector('[name=_method]').disabled = !edit;
      form.querySelector('[name=_id]').value = edit ? tr.dataset.id : '';
      uTitle.textContent = edit ? 'Ubah Pengguna' : 'Tambah Pengguna';
      uName.value = edit ? tr.dataset.nama : ''; uHp.value = edit ? tr.dataset.hp : ''; uEmail.value = edit ? tr.dataset.email : '';
      uPass.required = !edit; uPassNote.hidden = !edit;
      form.querySelectorAll('[type=password]').forEach(function (p) { p.value = ''; });
      setRole(edit ? tr.dataset.role : 'kasir');
      openModal('mUser');
    }
    document.getElementById('btnAdd').addEventListener('click', function () { mode(false); });
    document.addEventListener('click', function (e) {
      var b = e.target.closest('.js-edit'); if (b) mode(true, b.closest('tr'));
    });
  })();
</script>
@endpush
