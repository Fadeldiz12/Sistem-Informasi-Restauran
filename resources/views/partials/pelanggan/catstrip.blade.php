{{-- Baris kategori yang bisa digeser, menggantikan navbar di layar kecil --}}
@once
<style>
  /* "header .wrap" di halaman pelanggan memberi display:flex + tinggi 76px, jadi aturan ini perlu lebih spesifik */
  header .wrap.catstrip { display: none; }
  @media (max-width: 1180px) {
    .mainnav { display: none !important; }
    header .wrap.catstrip { display: flex; height: auto; gap: 8px; overflow-x: auto; padding: 0 0 12px; scrollbar-width: none; -webkit-overflow-scrolling: touch; scroll-snap-type: x proximity; }
    .catstrip::-webkit-scrollbar { display: none; }
    .catstrip a { flex: none; height: 36px; padding: 0 16px; border-radius: 999px; box-shadow: inset 0 0 0 1.5px var(--m); display: inline-flex; align-items: center;
                  font: 700 13px var(--f-accent); white-space: nowrap; scroll-snap-align: start; }
    .catstrip a.on { background: var(--m); color: var(--cr); box-shadow: none; }
  }
</style>
@endonce
<nav class="wrap catstrip" aria-label="Kategori menu">
  @if ($adaPromo)<a href="{{ route('pelanggan.beranda') }}#promo" data-promo>Promo</a>@endif
  <a href="{{ route('pelanggan.beranda') }}#menu" data-kat="" @class(['on' => request()->routeIs('pelanggan.beranda') && ! ($aktif ?? null)])>Semua</a>
  @foreach ($stripKategori as $k)
    <a href="{{ route('pelanggan.beranda', ['kategori' => $k->slug()]) }}#menu" data-kat="{{ $k->slug() }}" @class(['on' => ($aktif ?? null) === $k->slug()])>{{ $k->nama_kategori }}</a>
  @endforeach
</nav>
