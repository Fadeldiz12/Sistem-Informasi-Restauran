@extends('layouts.auth')

@section('title', 'Daftar Akun')

@section('form')
    {{-- novalidate: semua pesan validasi dari server berbahasa Indonesia (pesan bawaan browser mengikuti bahasa browser) --}}
    <form class="form wide" action="{{ route('registrasi.submit') }}" method="post" novalidate>
      @csrf
      <h1>Buat Akun Baru</h1>
      <p class="lead">Daftar untuk memesan menu, memilih jenis layanan, dan melacak pesananmu.</p>
      <div class="gap s"></div>
      @include('partials.auth.status')
      <div class="stack">
        <div class="row2">
          <label class="field"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg><input name="nama" value="{{ old('nama') }}" maxlength="100" placeholder="Nama lengkap" autocomplete="name" required @error('nama') aria-invalid="true" @enderror></label>
          <label class="field"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2z"/></svg><input name="hp" value="{{ old('hp') }}" maxlength="20" inputmode="tel" placeholder="Nomor HP (contoh 081234567890)" autocomplete="tel" required @error('hp') aria-invalid="true" @enderror></label>
        </div>
        <label class="field"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg><input type="email" name="email" value="{{ old('email') }}" maxlength="100" placeholder="Email" autocomplete="email" required @error('email') aria-invalid="true" @enderror></label>
        <label class="field ta"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg><textarea name="alamat" rows="2" maxlength="1000" placeholder="Alamat lengkap untuk pengantaran (opsional)">{{ old('alamat') }}</textarea></label>
        <div class="row2">
          @include('partials.auth.password', ['name' => 'password', 'placeholder' => 'Password', 'autocomplete' => 'new-password', 'min' => true])
          @include('partials.auth.password', ['name' => 'password_confirmation', 'placeholder' => 'Konfirmasi password', 'autocomplete' => 'new-password'])
        </div>
        <p class="hint" id="cocok" aria-live="polite" style="margin-top:-4px">Minimal 8 karakter, berisi huruf dan angka.</p>
      </div>
      <label class="agree"><input type="checkbox" name="setuju" value="1" required @checked(old('setuju'))> <span>Saya menyetujui <a href="{{ route('syarat') }}" target="_blank" rel="noopener" style="font-weight:700;text-decoration:underline">syarat &amp; ketentuan</a></span></label>
      <div class="gap s"></div>
      <button class="btn" type="submit">Daftar</button>
      <p class="foot">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
    </form>
@endsection

@push('scripts')
<script>
  (function () {
    // petunjuk langsung, sama seperti halaman password baru
    var a = document.querySelector('[name=password]'), b = document.querySelector('[name=password_confirmation]'), h = document.getElementById('cocok');
    function cek() {
      if (!a.value) { h.textContent = 'Minimal 8 karakter, berisi huruf dan angka.'; return; }
      if (a.value.length < 8) { h.textContent = 'Password minimal 8 karakter (' + a.value.length + '/8)'; return; }
      if (!/[A-Za-z]/.test(a.value) || !/\d/.test(a.value)) { h.textContent = 'Password harus berisi huruf dan angka'; return; }
      h.textContent = !b.value ? 'Ulangi password di kolom konfirmasi' : (a.value === b.value ? '✓ Password cocok' : '✗ Konfirmasi password belum sama');
    }
    a.addEventListener('input', cek); b.addEventListener('input', cek);
  })();
</script>
@endpush
