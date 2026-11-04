{{-- Navbar pelanggan: Promo + kategori pilihan admin (maks 6). Di beranda, klik kategori langsung menyaring menu. --}}
<nav class="mainnav">
  <a href="{{ route('pelanggan.beranda') }}#promo" data-promo>Promo</a>
  @foreach ($navKategori as $k)
    <a href="{{ route('pelanggan.beranda', ['kategori' => $k->slug()]) }}#menu" data-kat="{{ $k->slug() }}" @class(['on' => ($aktif ?? null) === $k->slug()])>{{ $k->nama_kategori }}</a>
  @endforeach
</nav>
