<?php

namespace App\Support;

use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Verifikasi 2 langkah (TOTP) untuk Owner & Admin: setelah password (dan cek outlet/wajah) lolos,
 * masih diminta kode 6 angka dari aplikasi authenticator di HP, atau salah satu kode cadangan.
 */
class DuaFaktor
{
    /** Role yang bisa (dan disarankan) memakai verifikasi 2 langkah. */
    public const ROLE = [User::ROLE_OWNER, User::ROLE_ADMIN];

    public const JUMLAH_CADANGAN = 8;

    /** Kode salah per login sebelum harus mengulang dari password. */
    public const MAKS_GAGAL = 5;

    public const BERLAKU_MENIT = 5;

    public static function bisaDipakai(User $user): bool
    {
        return in_array($user->role, self::ROLE, true);
    }

    public static function aktif(User $user): bool
    {
        return self::bisaDipakai($user) && $user->dua_faktor_aktif_at !== null && filled($user->dua_faktor_rahasia);
    }

    /**
     * Tahan login di langkah 2FA bila akun memakainya dan langkah itu belum dilewati di sesi ini.
     * Mengembalikan redirect ke halaman kode, atau null bila boleh langsung masuk.
     */
    public static function tahanLogin(Request $request, User $user, bool $ingat, string $via, ?int $idKode): ?RedirectResponse
    {
        if (! self::aktif($user) || $request->session()->get('dua_faktor_lolos') === $user->id) {
            return null;
        }
        $request->session()->put('login_2fa', [
            'id' => $user->id, 'ingat' => $ingat, 'via' => $via, 'kode' => $idKode,
            'sampai' => now()->addMinutes(self::BERLAKU_MENIT)->timestamp, 'gagal' => 0,
        ]);

        return to_route('dua-faktor');
    }

    /**
     * Cek kode authenticator atau kode cadangan. Kode yang berhasil tidak bisa dipakai ulang.
     *
     * @return 'totp'|'cadangan'|null
     */
    public static function periksa(User $user, string $kode): ?string
    {
        $kode = trim($kode);
        $langkah = Totp::cocok((string) $user->dua_faktor_rahasia, $kode, $user->dua_faktor_langkah_terakhir);
        if ($langkah !== null) {
            $user->forceFill(['dua_faktor_langkah_terakhir' => $langkah])->saveQuietly();

            return 'totp';
        }

        $sidik = self::sidikCadangan($kode);
        $cadangan = (array) $user->dua_faktor_cadangan;
        foreach ($cadangan as $i => $simpan) {
            if (hash_equals($simpan, $sidik)) {
                unset($cadangan[$i]);
                $user->forceFill(['dua_faktor_cadangan' => array_values($cadangan)])->saveQuietly();
                LogAktivitas::catat('akun.2fa_cadangan', "{$user->nama} masuk memakai kode cadangan 2 langkah (sisa ".count($cadangan).')', $user, pelaku: $user);

                return 'cadangan';
            }
        }

        return null;
    }

    /**
     * Buat kode cadangan baru (format XXXXX-XXXXX, 50 bit acak). Yang disimpan hanya sidik SHA-256-nya.
     *
     * @return list<string>
     */
    public static function buatCadangan(User $user): array
    {
        $kode = [];
        for ($i = 0; $i < self::JUMLAH_CADANGAN; $i++) {
            $acak = Totp::base32(random_bytes(7));
            $kode[] = substr($acak, 0, 5).'-'.substr($acak, 5, 5);
        }
        $user->forceFill(['dua_faktor_cadangan' => array_map(fn ($k) => self::sidikCadangan($k), $kode)])->saveQuietly();

        return $kode;
    }

    public static function matikan(User $user): void
    {
        $user->forceFill([
            'dua_faktor_rahasia' => null, 'dua_faktor_cadangan' => null, 'dua_faktor_aktif_at' => null, 'dua_faktor_langkah_terakhir' => null,
        ])->saveQuietly();
    }

    public static function sidikCadangan(string $kode): string
    {
        return hash('sha256', strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $kode) ?? ''));
    }
}
