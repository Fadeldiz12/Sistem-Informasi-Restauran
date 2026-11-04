{{--
  Tombol profil + kotak informasi akun dan tombol Keluar.
  Pakai: @include('partials.profile-menu', ['trigger' => 'user'])   (navbar pelanggan)
         @include('partials.profile-menu', ['trigger' => 'me'])     (topbar panel staf)
  Butuh partials.logout di halaman yang sama (sudah ada di semua halaman role).
--}}
@php
  $u = auth()->user();
  $trigger = $trigger ?? 'user';
  $links = $u->hasRole(\App\Models\User::ROLE_PELANGGAN)
    ? [['pelanggan.beranda', 'Beranda & Menu'], ['pelanggan.keranjang', 'Keranjang'], ['pelanggan.tracking', 'Lacak Pesanan']]
    : [[$u->homeRoute(), 'Dashboard']];
@endphp
@once
<style>
  .pm { position: relative; }
  .pm > button { cursor: pointer; font: inherit; color: inherit; background: none; border: 0; }
  .pm > button.user { display: flex; align-items: center; gap: 10px; font: 700 14px var(--f-accent); padding: 4px 14px 4px 4px; border-radius: 999px; box-shadow: inset 0 0 0 1.5px var(--m); color: var(--m); }
  .pm > button.me { color: var(--m); }
  .pm > button .pm-car { width: 14px; height: 14px; transition: transform .2s; }
  .pm.open > button .pm-car { transform: rotate(180deg); }
  .pm-pop { position: absolute; right: 0; top: calc(100% + 10px); z-index: 90; width: min(320px, calc(100vw - 32px));
            background: var(--cr); color: var(--m); border-radius: 22px; box-shadow: 0 0 0 1.5px var(--m), 0 18px 40px rgba(40, 40, 40, .18);
            padding: 18px; display: none; text-align: left; }
  .pm.open .pm-pop { display: block; animation: pm-in .16s ease-out; }
  @keyframes pm-in { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }
  .pm-head { display: flex; gap: 12px; align-items: center; padding-bottom: 14px; border-bottom: 1.5px dashed rgba(151, 23, 58, .3); }
  .pm-head .pm-av { width: 48px; height: 48px; border-radius: 50%; background: var(--m); color: var(--cr); display: grid; place-items: center; font: 700 16px var(--f-title); flex: none; }
  .pm-head b { display: block; font: 700 16px/1.25 var(--f-title); overflow-wrap: anywhere; }
  .pm-role { display: inline-block; margin-top: 4px; padding: 2px 10px; border-radius: 999px; background: var(--bl); font: 700 11px var(--f-accent); text-transform: capitalize; }
  .pm-info { display: grid; gap: 9px; padding: 14px 0; font: 500 13px/1.45 var(--f-body); }
  .pm-info div { display: grid; grid-template-columns: 18px minmax(0, 1fr); gap: 10px; align-items: start; }
  .pm-info svg { width: 16px; height: 16px; margin-top: 1px; fill: none; stroke: currentColor; stroke-width: 1.9; stroke-linecap: round; stroke-linejoin: round; opacity: .8; }
  .pm-info span { overflow-wrap: anywhere; }
  .pm-info small { display: block; font: 800 10px var(--f-accent); letter-spacing: .1em; text-transform: uppercase; opacity: .6; }
  .pm-links { display: grid; gap: 2px; padding: 10px 0; border-top: 1.5px solid rgba(151, 23, 58, .12); }
  .pm-links a { padding: 9px 12px; border-radius: 12px; font: 700 13.5px var(--f-accent); }
  .pm-links a:hover, .pm-links a.on { background: var(--pp); }
  .pm-out { width: 100%; height: 44px; margin-top: 6px; border: 0; border-radius: 999px; background: var(--m); color: var(--cr); cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 8px; font: 600 14px var(--f-title); }
  .pm-out:hover { filter: brightness(.92); }
  .pm-out svg { width: 17px; height: 17px; fill: none; stroke: currentColor; stroke-width: 1.9; stroke-linecap: round; stroke-linejoin: round; }
  /* di HP: tetap tampil, cukup avatar saja */
  @media (max-width: 900px) {
    .pm > button.user { display: flex; padding: 3px; }
    .pm > button.user .pm-nm, .pm > button.user .pm-car { display: none; }
  }
  /* layar kecil: kotak menempel ke layar (16px kiri-kanan), posisi atas diatur JS */
  @media (max-width: 900px) {
    .pm-pop { position: fixed; left: 16px; right: 16px; width: auto; max-height: calc(100vh - 100px); overflow-y: auto; }
  }
  @media (max-width: 860px) {
    .pm > button.me { padding: 4px; }
    .pm > button.me .av { display: grid; }
    .pm > button.me .pm-car { display: none; }
  }
</style>
@endonce
<div class="pm" data-pm>
  <button type="button" class="{{ $trigger }}" aria-haspopup="true" aria-expanded="false" title="Profil & Keluar">
    <span @class(['av', 'm' => $trigger === 'me'])>{{ $u->inisial() }}</span>
    <span class="pm-nm">{{ $trigger === 'me' ? $u->nama : $u->namaDepan() }}</span>
    <svg class="pm-car" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
  </button>
  <div class="pm-pop" role="dialog" aria-label="Informasi akun">
    <div class="pm-head">
      <span class="pm-av">{{ $u->inisial() }}</span>
      <div style="min-width:0"><b>{{ $u->nama }}</b><span class="pm-role">{{ $u->role }}</span></div>
    </div>
    <div class="pm-info">
      <div><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg><span><small>Email</small>{{ $u->email }}</span></div>
      <div><svg viewBox="0 0 24 24"><path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2z"/></svg><span><small>Nomor HP</small>{{ $u->no_hp }}</span></div>
      @if ($u->hasRole(\App\Models\User::ROLE_PELANGGAN))
        <div><svg viewBox="0 0 24 24"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg><span><small>Alamat</small>{{ $u->alamat ?: 'Belum diisi' }}</span></div>
      @endif
      <div><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4"/></svg><span><small>Bergabung</small>{{ $u->created_at?->translatedFormat('j F Y') }}</span></div>
    </div>
    <div class="pm-links">
      @foreach ($links as [$route, $label])
        <a href="{{ route($route) }}" @class(['on' => request()->routeIs($route)])>{{ $label }}</a>
      @endforeach
    </div>
    <button type="button" class="pm-out" data-logout><svg viewBox="0 0 24 24"><path d="M15 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-4"/><path d="M10 17l-5-5 5-5M5 12h11"/></svg>Keluar</button>
  </div>
</div>
@once
<script>
  (function () {
    function closeAll(except) {
      document.querySelectorAll('[data-pm].open').forEach(function (m) {
        if (m !== except) { m.classList.remove('open'); m.firstElementChild.setAttribute('aria-expanded', 'false'); }
      });
    }
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-pm] > button');
      if (btn) {
        e.preventDefault();
        var m = btn.parentElement, open = !m.classList.contains('open');
        closeAll(m); m.classList.toggle('open', open); btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        var pop = m.querySelector('.pm-pop');
        pop.style.top = window.innerWidth <= 900 ? (btn.getBoundingClientRect().bottom + 10) + 'px' : '';
        return;
      }
      if (!e.target.closest('.pm-pop')) closeAll();
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAll(); });
  })();
</script>
@endonce
