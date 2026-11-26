<?php

namespace App\Models;

use App\Support\RantaiLog;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

/**
 * Audit log: siapa melakukan apa dan kapan. Baris hanya ditambah: mengubah atau menghapus lewat aplikasi ditolak,
 * dan perubahan langsung di database ketahuan lewat rantai hash (App\Support\RantaiLog).
 */
#[Table('log_aktivitas')]
#[Fillable(['id_user', 'nama_user', 'role_user', 'aksi', 'keterangan', 'subjek_tipe', 'subjek_id', 'data', 'ip', 'perangkat'])]
class LogAktivitas extends Model
{
    public const UPDATED_AT = null;

    /**
     * Kelompok aksi untuk filter halaman log; kunci = awalan kode aksi.
     *
     * @var array<string, array{label: string, awalan: array<int, string>}>
     */
    public const KATEGORI = [
        'keamanan' => ['label' => 'Login & keamanan', 'awalan' => ['akun.']],
        'pengguna' => ['label' => 'Pengguna & role', 'awalan' => ['pengguna.']],
        'menu' => ['label' => 'Menu, harga & kategori', 'awalan' => ['menu.', 'kategori.']],
        'transaksi' => ['label' => 'Pesanan & pembayaran', 'awalan' => ['pesanan.', 'pembayaran.', 'setoran.', 'eod.']],
        'stok' => ['label' => 'Stok bahan baku', 'awalan' => ['bahan.']],
        'keuangan' => ['label' => 'Keuangan & laporan', 'awalan' => ['pengeluaran.', 'laporan.']],
        'pengaturan' => ['label' => 'Pengaturan, zona & outlet', 'awalan' => ['pengaturan.', 'zona.', 'perangkat.', 'shift.', 'darurat.', 'wajah.']],
    ];

    /**
     * Aksi yang ditampilkan sebagai peringatan (merah) di halaman log.
     */
    public const AKSI_PENTING = ['akun.terkunci', 'akun.login_gagal', 'akun.diambil_alih', 'akun.ditolak', 'akun.kode_darurat', 'akun.keluar_area', 'perangkat.hapus', 'pembayaran.palsu', 'akun.spoofing', 'akun.wajah_gagal', 'akun.wajah_disetujui', 'wajah.hapus', 'wajah.ditolak', 'pengguna.hapus', 'pesanan.batal', 'menu.hapus'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /**
     * Tambah satu baris log. Pelaku bawaan: pengguna yang sedang login.
     *
     * @param  array<string, mixed>  $data
     */
    public static function catat(string $aksi, string $keterangan, ?Model $subjek = null, array $data = [], ?User $pelaku = null): self
    {
        $pelaku ??= auth()->user();
        $request = request();

        $log = new static([
            'id_user' => $pelaku?->id,
            'nama_user' => $pelaku?->nama,
            'role_user' => $pelaku?->role,
            'aksi' => $aksi,
            'keterangan' => Str::limit($keterangan, 497),
            'subjek_tipe' => $subjek ? class_basename($subjek) : null,
            'subjek_id' => $subjek?->getKey(),
            'data' => $data ?: null,
            'ip' => $request->ip(),
            'perangkat' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);
        $log->created_at = now();

        // nilai mentah persis seperti yang akan disimpan (JSON dari cast), supaya hash bisa dicek ulang dari database
        return RantaiLog::tambah($log->getAttributes(), function (?string $hash) use ($log) {
            if ($hash !== null) {
                $log->hash = $hash;
            }
            $log->save();

            return $log;
        });
    }

    /**
     * Catatan log tidak boleh diubah atau dihapus lewat aplikasi.
     */
    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Log aktivitas tidak bisa diubah.'));
        static::deleting(fn () => throw new LogicException('Log aktivitas tidak bisa dihapus.'));
    }

    #[Scope]
    protected function kategori(Builder $query, string $kategori): void
    {
        $awalan = self::KATEGORI[$kategori]['awalan'] ?? [];
        $query->where(function (Builder $w) use ($awalan) {
            foreach ($awalan as $a) {
                $w->orWhere('aksi', 'like', $a.'%');
            }
        });
    }

    public function penting(): bool
    {
        return in_array($this->aksi, self::AKSI_PENTING, true);
    }

    /**
     * Label kategori aksi ini, contoh "Login & keamanan".
     */
    public function labelKategori(): string
    {
        foreach (self::KATEGORI as $k) {
            foreach ($k['awalan'] as $a) {
                if (str_starts_with($this->aksi, $a)) {
                    return $k['label'];
                }
            }
        }

        return 'Lainnya';
    }

    /**
     * Nama browser & sistem singkat dari user agent, contoh "Chrome · Windows".
     */
    public function perangkatSingkat(): string
    {
        $ua = (string) $this->perangkat;
        $browser = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') => 'Opera',
            str_contains($ua, 'Chrome/') => 'Chrome',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Safari/') => 'Safari',
            default => '',
        };
        $os = match (true) {
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => '',
        };

        return implode(' · ', array_filter([$browser, $os])) ?: 'Tidak dikenal';
    }
}
