<?php

namespace App\Support;

use App\Models\Menu;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

/**
 * Keranjang pelanggan disimpan di session (tidak ada tabel keranjang di rancangan database).
 * Isi: [id_menu => ['jumlah' => int, 'catatan' => string|null]]
 */
class Keranjang
{
    public const KEY = 'keranjang';

    public const MAKS_JUMLAH = 50;

    /**
     * Nama menu yang baru saja dibuang dari keranjang karena habis / dihapus admin.
     *
     * @var array<int, string>
     */
    private array $dibuang = [];

    public function __construct(private Session $session) {}

    /**
     * @return array<int, string>
     */
    public function dibuang(): array
    {
        return $this->dibuang;
    }

    /** @return array<int, array{jumlah:int, catatan:?string}> */
    public function raw(): array
    {
        return $this->session->get(self::KEY, []);
    }

    /**
     * Item keranjang beserta data menunya. Menu yang sudah dihapus / habis dibuang otomatis.
     *
     * @return Collection<int, array{menu: Menu, jumlah: int, catatan: ?string, subtotal: float}>
     */
    public function items(): Collection
    {
        $raw = $this->raw();
        if (! $raw) {
            return collect();
        }

        $menu = Menu::with('kategori')->whereIn('id', array_keys($raw))->get()->keyBy('id');
        $items = collect();

        foreach ($raw as $id => $row) {
            $m = $menu->get($id);
            if (! $m || ! $m->tersedia) {
                unset($raw[$id]);
                $this->dibuang[] = $m?->nama_menu ?? 'Salah satu menu';

                continue;
            }
            $items->push([
                'menu' => $m,
                'jumlah' => $row['jumlah'],
                'catatan' => $row['catatan'] ?? null,
                'subtotal' => (float) $m->harga * $row['jumlah'],
            ]);
        }

        $this->session->put(self::KEY, $raw);

        return $items;
    }

    /**
     * Tambah porsi. Mengembalikan jumlah porsi yang benar-benar ditambahkan (0 bila sudah di batas maksimal).
     */
    public function tambah(Menu $menu, int $jumlah = 1, ?string $catatan = null): int
    {
        $raw = $this->raw();
        $lama = $raw[$menu->id]['jumlah'] ?? 0;
        $baru = min(self::MAKS_JUMLAH, $lama + max(1, $jumlah));
        $raw[$menu->id] = [
            'jumlah' => $baru,
            'catatan' => $raw[$menu->id]['catatan'] ?? ($catatan !== null && trim($catatan) !== '' ? trim($catatan) : null),
        ];
        $this->session->put(self::KEY, $raw);

        return $baru - $lama;
    }

    public function ubah(int $idMenu, ?int $jumlah, ?string $catatan, bool $ubahCatatan): void
    {
        $raw = $this->raw();
        if (! isset($raw[$idMenu])) {
            return;
        }
        if ($jumlah !== null) {
            $raw[$idMenu]['jumlah'] = max(1, min(self::MAKS_JUMLAH, $jumlah));
        }
        if ($ubahCatatan) {
            $raw[$idMenu]['catatan'] = $catatan !== null && trim($catatan) !== '' ? trim($catatan) : null;
        }
        $this->session->put(self::KEY, $raw);
    }

    public function hapus(int $idMenu): void
    {
        $raw = $this->raw();
        unset($raw[$idMenu]);
        $this->session->put(self::KEY, $raw);
    }

    public function kosongkan(): void
    {
        $this->session->forget(self::KEY);
    }

    /**
     * Ringkasan untuk badge keranjang di header.
     *
     * @return array{jumlah:int, subtotal:float, menu:int}
     */
    public function ringkasan(?Collection $items = null): array
    {
        $items ??= $this->items();

        return [
            'jumlah' => (int) $items->sum('jumlah'),
            'subtotal' => (float) $items->sum('subtotal'),
            'menu' => $items->count(),
        ];
    }
}
