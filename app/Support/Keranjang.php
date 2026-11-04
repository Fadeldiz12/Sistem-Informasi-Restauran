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

    public function __construct(private Session $session) {}

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

    public function tambah(Menu $menu, int $jumlah = 1): void
    {
        $raw = $this->raw();
        $lama = $raw[$menu->id]['jumlah'] ?? 0;
        $raw[$menu->id] = [
            'jumlah' => min(self::MAKS_JUMLAH, $lama + max(1, $jumlah)),
            'catatan' => $raw[$menu->id]['catatan'] ?? null,
        ];
        $this->session->put(self::KEY, $raw);
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
