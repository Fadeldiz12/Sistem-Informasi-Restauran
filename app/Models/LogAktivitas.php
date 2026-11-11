<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Audit log: siapa melakukan apa dan kapan. Baris hanya ditambah, tidak ada fitur ubah atau hapus.
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
        'pengaturan' => ['label' => 'Pengaturan & zona', 'awalan' => ['pengaturan.', 'zona.']],
    ];

    /**
     * Aksi yang ditampilkan sebagai peringatan (merah) di halaman log.
     */
    public const AKSI_PENTING = ['akun.terkunci', 'akun.login_gagal', 'akun.diambil_alih', 'pengguna.hapus', 'pesanan.batal', 'menu.hapus'];

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

        return static::create([
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
