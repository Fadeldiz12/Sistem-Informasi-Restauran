{{-- Navigasi bawah di HP (≤ 760px): Menu · Keranjang · Pesanan · Akun --}}
@once
<style>
  .bnav { display: none; }
  @media (max-width: 760px) {
    .bnav { position: fixed; left: 0; right: 0; bottom: 0; z-index: 60; display: grid; grid-template-columns: repeat(4, 1fr);
            background: var(--cr); border-top: 1.5px solid var(--m15); padding: 6px 6px calc(6px + env(safe-area-inset-bottom)); }
    .bnav a { position: relative; display: grid; justify-items: center; gap: 2px; padding: 6px 0; border-radius: 14px; font: 700 11.5px var(--f-accent); color: var(--m); }
    .bnav a.on { background: var(--pp); }
    .bnav .ic { width: 22px; height: 22px; }
    .bnav .n { position: absolute; top: 2px; left: calc(50% + 6px); min-width: 18px; height: 18px; padding: 0 5px; border-radius: 999px; background: var(--m); color: var(--cr); font: 700 10.5px/18px var(--f-title); }
    body { padding-bottom: calc(72px + env(safe-area-inset-bottom)); }
    .p-toast { bottom: calc(86px + env(safe-area-inset-bottom)) !important; }
  }
</style>
@endonce
<nav class="bnav" aria-label="Navigasi utama">
  <a href="{{ route('pelanggan.beranda') }}#menu" @class(['on' => request()->routeIs('pelanggan.beranda')])><svg class="ic"><use href="#i-dine"/></svg>Menu</a>
  <a href="{{ route('pelanggan.keranjang') }}" @class(['on' => request()->routeIs('pelanggan.keranjang', 'pelanggan.checkout')])><svg class="ic"><use href="#i-cart"/></svg>Keranjang<span class="n" data-cart-count @if (! ($keranjangInfo['jumlah'] ?? 0)) hidden @endif>{{ $keranjangInfo['jumlah'] ?? 0 }}</span></a>
  <a href="{{ route('pelanggan.tracking') }}" @class(['on' => request()->routeIs('pelanggan.tracking', 'pelanggan.pembayaran')])><svg class="ic"><use href="#i-receipt"/></svg>Pesanan</a>
  <a href="{{ route('pelanggan.profil') }}" @class(['on' => request()->routeIs('pelanggan.profil', 'pelanggan.bantuan')])><svg class="ic"><use href="#i-user"/></svg>Akun</a>
</nav>
