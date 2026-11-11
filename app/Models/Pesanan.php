<?php

namespace App\Models;

use App\Models\Concerns\IdTerenkripsiDiUrl;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('pesanan')]
#[Fillable(['id_pelanggan', 'id_kasir', 'sumber', 'jenis_layanan', 'nomor_meja', 'nama_pemesan', 'no_hp_pemesan', 'email_pemesan', 'waktu_pesan', 'subtotal', 'ongkir', 'total', 'status', 'alasan_batal', 'selesai_pada'])]
class Pesanan extends Model
{
    use IdTerenkripsiDiUrl;

    /** Dipesan pelanggan lewat aplikasi (dengan akun atau sebagai tamu) */
    public const SUMBER_ONLINE = 'online';

    /** Diinput kasir untuk pembeli di restoran */
    public const SUMBER_KASIR = 'kasir';

    public const LAYANAN_DINE_IN = 'dine_in';

    public const LAYANAN_TAKE_AWAY = 'take_away';

    public const LAYANAN_DELIVERY = 'delivery';

    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_DIPROSES = 'diproses';

    public const STATUS_SIAP = 'siap';

    public const STATUS_DIANTAR = 'diantar';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    protected function casts(): array
    {
        return [
            'no_hp_pemesan' => 'encrypted',
            'selesai_pada' => 'datetime',
            'waktu_pesan' => 'datetime',
            'subtotal' => 'decimal:2',
            'ongkir' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pelanggan');
    }

    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_kasir');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(DetailPesanan::class, 'id_pesanan');
    }

    public function pembayaran(): HasOne
    {
        return $this->hasOne(Pembayaran::class, 'id_pesanan');
    }

    public function tiketDapur(): HasOne
    {
        return $this->hasOne(TiketDapur::class, 'id_pesanan');
    }

    public function pengiriman(): HasOne
    {
        return $this->hasOne(Pengiriman::class, 'id_pesanan');
    }

    public const LABEL_LAYANAN = [
        self::LAYANAN_DINE_IN => 'Dine In',
        self::LAYANAN_TAKE_AWAY => 'Take Away',
        self::LAYANAN_DELIVERY => 'Delivery',
    ];

    public const LABEL_STATUS = [
        self::STATUS_MENUNGGU => 'Menunggu Konfirmasi',
        self::STATUS_DIPROSES => 'Diproses',
        self::STATUS_SIAP => 'Siap',
        self::STATUS_DIANTAR => 'Diantar',
        self::STATUS_SELESAI => 'Selesai',
        self::STATUS_DIBATALKAN => 'Dibatalkan',
    ];

    /**
     * Nomor pesanan untuk ditampilkan, contoh: ORD-0007.
     */
    public function kode(): string
    {
        return 'ORD-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function layananLabel(): string
    {
        return self::LABEL_LAYANAN[$this->jenis_layanan] ?? $this->jenis_layanan;
    }

    public function statusLabel(): string
    {
        // pesanan online lama yang belum memilih metode bayar tidak terlihat di antrean kasir
        if ($this->status === self::STATUS_MENUNGGU && $this->isOnline() && ! $this->pembayaran) {
            return 'Belum Pilih Pembayaran';
        }

        return self::LABEL_STATUS[$this->status] ?? $this->status;
    }

    /**
     * Kelas warna .pill untuk status pesanan.
     */
    public function statusPill(): string
    {
        return match ($this->status) {
            self::STATUS_MENUNGGU => 'b',
            self::STATUS_DIPROSES, self::STATUS_DIANTAR => 'm',
            self::STATUS_SELESAI, self::STATUS_DIBATALKAN => 'p',
            default => '',
        };
    }

    /**
     * Nama yang ditampilkan: akun pelanggan, nama pembeli langsung, atau nomor meja.
     */
    public function namaPemesan(): string
    {
        return $this->pelanggan?->nama
            ?? ($this->nama_pemesan ?: ($this->nomor_meja ? 'Meja '.$this->nomor_meja : 'Pembeli di tempat'));
    }

    /**
     * Contoh: "Dine In · Meja 07", "Delivery · Zona B", "Take Away".
     */
    public function layananDetail(): string
    {
        $tambahan = match ($this->jenis_layanan) {
            self::LAYANAN_DINE_IN => $this->nomor_meja ? 'Meja '.$this->nomor_meja : null,
            self::LAYANAN_DELIVERY => $this->pengiriman?->zona?->nama_zona,
            default => null,
        };

        return $this->layananLabel().($tambahan ? ' · '.$tambahan : '');
    }

    /**
     * Pajak + biaya layanan yang tersimpan di total (total - subtotal - ongkir).
     */
    public function pajakDanLayanan(): float
    {
        return round((float) $this->total - (float) $this->subtotal - (float) $this->ongkir, 2);
    }

    public function isOnline(): bool
    {
        return $this->sumber === self::SUMBER_ONLINE;
    }

    /**
     * Pesanan online tanpa akun (pesan tanpa login).
     */
    public function isTamu(): bool
    {
        return $this->isOnline() && $this->id_pelanggan === null;
    }

    /**
     * Inisial untuk avatar: dari akun, atau dari nama yang diisi tamu / kasir.
     */
    public function inisialPemesan(): string
    {
        if ($this->pelanggan) {
            return $this->pelanggan->inisial();
        }
        $kata = preg_split('/\s+/', trim((string) $this->nama_pemesan)) ?: [];

        return mb_strtoupper(collect($kata)->filter()->take(2)->map(fn ($k) => mb_substr($k, 0, 1))->join('')) ?: 'PL';
    }

    /**
     * Nomor HP pemesan: dari akun pelanggan, atau yang diisi tamu.
     */
    public function noHpPemesan(): ?string
    {
        return $this->pelanggan?->no_hp ?: $this->no_hp_pemesan;
    }

    public function isLunas(): bool
    {
        return $this->pembayaran?->status_bayar === Pembayaran::STATUS_LUNAS;
    }

    /**
     * Label pembayaran untuk daftar: "Belum dipilih", "Bayar Tunai", "Menunggu Verifikasi", "Lunas", "Ditolak".
     */
    public function bayarLabel(): string
    {
        $bayar = $this->pembayaran;

        if (! $bayar) {
            return $this->isOnline() ? 'Belum Bayar' : 'Belum Lunas';
        }

        return match (true) {
            $bayar->status_bayar === Pembayaran::STATUS_LUNAS => 'Lunas',
            $bayar->status_bayar === Pembayaran::STATUS_DITOLAK, $bayar->isKedaluwarsa() => 'Gagal / Kedaluwarsa',
            $bayar->metode === Pembayaran::METODE_TUNAI => 'Bayar Tunai',
            default => 'Menunggu Pembayaran',
        };
    }

    /**
     * Pesanan yang diproses kasir di menu Pembayaran:
     * pesanan di tempat yang belum lunas, atau pesanan online yang dibayar tunai.
     * Tidak termasuk: VA & QRIS (lunas otomatis lewat Midtrans), Delivery yang tunainya dibayar ke kurir,
     * dan pesanan online yang belum dikonfirmasi kasir. Delivery yang diinput kasir dengan "bayar di kasir"
     * (belum punya data pembayaran) ikut dilunasi di sini.
     */
    #[Scope]
    protected function belumLunas(Builder $query): void
    {
        $query->whereNotIn('status', [self::STATUS_DIBATALKAN, self::STATUS_MENUNGGU])
            ->where(fn ($l) => $l->where('jenis_layanan', '!=', self::LAYANAN_DELIVERY)
                ->orWhere(fn ($d) => $d->where('sumber', self::SUMBER_KASIR)->whereDoesntHave('pembayaran')))
            ->where(fn ($w) => $w
                ->where(fn ($a) => $a->where('sumber', self::SUMBER_KASIR)
                    ->whereDoesntHave('pembayaran', fn ($b) => $b->where('status_bayar', Pembayaran::STATUS_LUNAS)))
                ->orWhereHas('pembayaran', fn ($b) => $b->where('metode', Pembayaran::METODE_TUNAI)->where('status_bayar', Pembayaran::STATUS_MENUNGGU)));
    }

    /**
     * Pesanan online bayar tunai yang menunggu konfirmasi kasir (FIFO: paling lama di atas).
     * Pesanan VA/QRIS otomatis masuk dapur setelah lunas.
     */
    #[Scope]
    protected function menungguKonfirmasi(Builder $query): void
    {
        $query->where('sumber', self::SUMBER_ONLINE)->where('status', self::STATUS_MENUNGGU)
            ->whereHas('pembayaran', fn ($b) => $b->where('metode', Pembayaran::METODE_TUNAI))
            ->orderBy('waktu_pesan')->orderBy('id');
    }

    public function isDelivery(): bool
    {
        return $this->jenis_layanan === self::LAYANAN_DELIVERY;
    }

    /**
     * Delivery yang diinput kasir dan dibayar di kasir (bukan COD ke kurir): belum ada data pembayaran, atau sudah lunas tanpa kurir.
     */
    public function isDeliveryBayarDiKasir(): bool
    {
        return $this->isDelivery() && ! $this->isOnline()
            && ($this->pembayaran === null || ($this->pembayaran->id_kurir === null && $this->pembayaran->status_bayar === Pembayaran::STATUS_LUNAS));
    }
}
