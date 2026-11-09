<?php

namespace App\Support;

use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cookie;

/**
 * Pesanan tamu (tanpa login) yang pernah dibuat / dibuka di browser ini, disimpan di cookie terenkripsi,
 * supaya menu Lacak Pesanan tetap berisi walau tanpa akun.
 */
class PesananTamu
{
    public const COOKIE = 'pesanan_tamu';

    private const MAKS = 20;

    private const MENIT = 60 * 24 * 60;

    /**
     * @return array<int, int>
     */
    public static function ids(): array
    {
        // daftar yang baru diubah di permintaan ini (cookie baru terkirim di respons)
        if (request()->attributes->has(self::COOKIE)) {
            return request()->attributes->get(self::COOKIE);
        }
        $isi = json_decode((string) request()->cookie(self::COOKIE), true);

        return is_array($isi) ? array_values(array_filter(array_map('intval', $isi))) : [];
    }

    public static function ingat(Pesanan $pesanan): void
    {
        if (! $pesanan->isTamu()) {
            return;
        }
        $ids = array_slice(array_values(array_unique([$pesanan->id, ...self::ids()])), 0, self::MAKS);
        request()->attributes->set(self::COOKIE, $ids);
        Cookie::queue(self::COOKIE, json_encode($ids), self::MENIT);
    }

    public static function lupakan(): void
    {
        request()->attributes->set(self::COOKIE, []);
        Cookie::queue(Cookie::forget(self::COOKIE));
    }

    /**
     * Pesanan milik pengunjung ini: riwayat akun bila login, atau pesanan tamu yang tersimpan di browser.
     *
     * @return Builder<Pesanan>|HasMany<Pesanan, User>
     */
    public static function milik(?User $user): Builder|HasMany
    {
        return $user
            ? $user->pesanan()
            : Pesanan::query()->where('sumber', Pesanan::SUMBER_ONLINE)->whereNull('id_pelanggan')->whereIn('id', self::ids());
    }

    /**
     * Pemilik akun melihat pesanannya sendiri. Pesanan tamu bisa dibuka siapa pun yang memegang
     * link terenkripsinya (link lacak dikirim ke tamu), karena tamu tidak punya akun untuk dicocokkan.
     */
    public static function bolehLihat(Pesanan $pesanan, ?User $user): bool
    {
        return ($user && $pesanan->id_pelanggan === $user->id) || $pesanan->isTamu();
    }

    /**
     * Saat tamu membuat akun: pesanan tamu dengan email yang sama (sudah diverifikasi OTP)
     * atau yang tersimpan di browser ini dipindahkan ke riwayat akun.
     */
    public static function tautkanKe(User $user): int
    {
        $jumlah = Pesanan::where('sumber', Pesanan::SUMBER_ONLINE)->whereNull('id_pelanggan')
            ->where(fn ($q) => $q->where('email_pemesan', $user->email)->orWhereIn('id', self::ids()))
            ->update(['id_pelanggan' => $user->id]);
        self::lupakan();

        return $jumlah;
    }

    /**
     * Rapikan kode pesanan yang diketik tamu: "ORD-0007", "ord 7", "7" → 7.
     */
    public static function idDariNomor(string $nomor): ?int
    {
        return preg_match('/^\s*(?:ORD[\s-]*)?0*(\d{1,9})\s*$/i', $nomor, $m) ? (int) $m[1] : null;
    }
}
