<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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
        ];
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
