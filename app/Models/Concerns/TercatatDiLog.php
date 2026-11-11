<?php

namespace App\Models\Concerns;

use App\Models\LogAktivitas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Tambah, ubah, dan hapus data oleh pengguna yang login otomatis masuk audit log.
 *
 * Model pemakai mendefinisikan:
 * - LOG_KODE  : awalan kode aksi, contoh "menu" → menu.tambah / menu.ubah / menu.hapus
 * - LOG_JENIS : nama data untuk kalimat log, contoh "Menu"
 * - LOG_JUDUL : kolom nama data, contoh "nama_menu" (null bila datanya tunggal, mis. pengaturan)
 * - LOG_ABAIKAN (opsional): kolom yang perubahannya tidak dicatat (mis. hasil hitungan sistem)
 *
 * @mixin Model
 */
trait TercatatDiLog
{
    /**
     * Kolom yang tidak pernah dicatat.
     */
    private static array $logSelaluAbaikan = ['created_at', 'updated_at', 'deleted_at', 'remember_token', 'gagal_login', 'terkunci_at', 'token_sesi', 'email_verified_at'];

    /**
     * Kolom yang isinya rahasia atau data pribadi terenkripsi: hanya dicatat "diganti", isinya tidak masuk log.
     */
    private static array $logRahasia = ['password', 'kode_hash', 'no_hp', 'no_hp_hash', 'alamat', 'patokan', 'no_hp_penerima', 'no_hp_pemesan'];

    /**
     * Kolom uang (ditulis Rp) dan label kolom yang lebih mudah dibaca.
     */
    private static array $logUang = ['harga', 'nominal', 'tarif_per_km', 'ongkir', 'jumlah'];

    public static function bootTercatatDiLog(): void
    {
        static::created(fn (Model $m) => $m->catatKeLog('tambah', 'ditambahkan'));
        static::updated(fn (Model $m) => $m->catatKeLog('ubah', 'diubah'));
        static::deleted(fn (Model $m) => $m->catatKeLog('hapus', 'dihapus'));
    }

    protected function catatKeLog(string $jenis, string $kata): void
    {
        if (! auth()->check()) {
            return; // seeder, perintah konsol, webhook: bukan aksi pengguna
        }

        $abaikan = array_merge(self::$logSelaluAbaikan, defined(static::class.'::LOG_ABAIKAN') ? static::LOG_ABAIKAN : []);
        $lama = [];
        $baru = [];

        if ($jenis === 'ubah') {
            foreach (array_diff_key($this->getChanges(), array_flip($abaikan)) as $kolom => $nilai) {
                $lama[$kolom] = $this->getOriginal($kolom);
                $baru[$kolom] = $nilai;
            }
            if (! $baru) {
                return;
            }
        } elseif ($jenis === 'tambah') {
            $baru = array_diff_key($this->attributesToArray(), array_flip(array_merge($abaikan, ['id'])));
        }

        $rincian = $jenis === 'ubah'
            ? ': '.collect($baru)->map(fn ($v, $k) => in_array($k, self::$logRahasia, true)
                ? self::labelKolomLog($k).' diganti'
                : self::labelKolomLog($k).' '.self::nilaiLog($k, $lama[$k]).' → '.self::nilaiLog($k, $v))->implode('; ')
            : '';

        foreach (self::$logRahasia as $r) {
            unset($lama[$r], $baru[$r]);
        }

        LogAktivitas::catat(
            static::LOG_KODE.'.'.$jenis,
            static::LOG_JENIS.(static::LOG_JUDUL ? ' "'.$this->getAttribute(static::LOG_JUDUL).'"' : '').' '.$kata.$rincian,
            $this,
            array_filter(['lama' => $lama, 'baru' => $baru]),
        );
    }

    private static function labelKolomLog(string $kolom): string
    {
        return str_replace('_', ' ', Str::after($kolom, 'id_'));
    }

    private static function nilaiLog(string $kolom, mixed $nilai): string
    {
        return match (true) {
            $nilai === null || $nilai === '' => '–',
            is_bool($nilai) || in_array($kolom, ['tersedia', 'tampil_navbar', 'cek_gps', 'wajib_perangkat', 'wajib_shift'], true) => $nilai ? 'Ya' : 'Tidak',
            in_array($kolom, self::$logUang, true) && is_numeric($nilai) => 'Rp '.number_format((float) $nilai, 0, ',', '.'),
            default => Str::limit((string) $nilai, 60),
        };
    }
}
