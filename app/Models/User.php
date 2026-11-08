<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\IdTerenkripsiDiUrl;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['nama', 'email', 'password', 'role', 'no_hp', 'alamat'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use IdTerenkripsiDiUrl;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_OWNER = 'owner';

    public const ROLE_KASIR = 'kasir';

    public const ROLE_PELANGGAN = 'pelanggan';

    public const ROLE_DAPUR = 'dapur';

    public const ROLE_KURIR = 'kurir';

    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_OWNER,
        self::ROLE_KASIR,
        self::ROLE_PELANGGAN,
        self::ROLE_DAPUR,
        self::ROLE_KURIR,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
        ];
    }

    public const LABEL_ROLE = [
        self::ROLE_OWNER => 'Owner',
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_KASIR => 'Kasir',
        self::ROLE_DAPUR => 'Dapur',
        self::ROLE_KURIR => 'Kurir',
        self::ROLE_PELANGGAN => 'Pelanggan',
    ];

    /**
     * Akses tiap role, ditampilkan saat memilih role di form pengguna.
     */
    public const AKSES_ROLE = [
        self::ROLE_OWNER => 'Dashboard penjualan, laporan keuangan, pengeluaran, dan semua pengguna termasuk owner.',
        self::ROLE_ADMIN => 'Data pengguna (kecuali owner), kategori & menu, zona, dan pengaturan sistem.',
        self::ROLE_KASIR => 'Input pesanan, konfirmasi pesanan online, pembayaran, dan setoran kurir.',
        self::ROLE_DAPUR => 'Antrean tiket masak dan stok bahan baku.',
        self::ROLE_KURIR => 'Tugas pengiriman, status antar, dan foto bukti.',
        self::ROLE_PELANGGAN => 'Memesan menu, membayar, dan melacak pesanan.',
    ];

    public function roleLabel(): string
    {
        return self::LABEL_ROLE[$this->role] ?? ucfirst((string) $this->role);
    }

    /**
     * Role yang boleh diberikan oleh pengguna ini. Owner mengatur semua role;
     * admin mengatur semua kecuali owner.
     *
     * @return array<int, string>
     */
    public function roleYangBolehDiatur(): array
    {
        return match ($this->role) {
            self::ROLE_OWNER => array_keys(self::LABEL_ROLE),
            self::ROLE_ADMIN => array_values(array_diff(array_keys(self::LABEL_ROLE), [self::ROLE_OWNER])),
            default => [],
        };
    }

    /**
     * Boleh mengubah / menghapus akun $target? Akun owner hanya bisa diatur oleh owner.
     */
    public function bolehKelola(self $target): bool
    {
        return in_array($target->role, $this->roleYangBolehDiatur(), true);
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Nama route halaman utama (dashboard) sesuai role.
     */
    public function homeRoute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'admin.dashboard',
            self::ROLE_OWNER => 'owner.dashboard',
            self::ROLE_KASIR => 'kasir.dashboard',
            self::ROLE_DAPUR => 'dapur.antrean',
            self::ROLE_KURIR => 'kurir.tugas',
            default => 'pelanggan.beranda',
        };
    }

    /**
     * Inisial nama untuk avatar, contoh: "Dewi Lestari" -> "DL".
     */
    public function inisial(): string
    {
        $kata = preg_split('/\s+/', trim($this->nama)) ?: [];

        return mb_strtoupper(collect($kata)->take(2)->map(fn ($k) => mb_substr($k, 0, 1))->implode(''));
    }

    public function namaDepan(): string
    {
        return strtok(trim($this->nama), ' ') ?: $this->nama;
    }

    /** Pesanan yang dibuat pelanggan. */
    public function pesanan(): HasMany
    {
        return $this->hasMany(Pesanan::class, 'id_pelanggan');
    }

    /** Pesanan yang diinput / dikonfirmasi kasir. */
    public function pesananKasir(): HasMany
    {
        return $this->hasMany(Pesanan::class, 'id_kasir');
    }

    /** Pembayaran yang diproses kasir. */
    public function pembayaranKasir(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'id_kasir');
    }

    /** Tugas pengiriman milik kurir. */
    public function tugasPengiriman(): HasMany
    {
        return $this->hasMany(Pengiriman::class, 'id_kurir');
    }

    /** Pengeluaran yang dicatat owner. */
    public function pengeluaran(): HasMany
    {
        return $this->hasMany(Pengeluaran::class, 'id_owner');
    }
}
