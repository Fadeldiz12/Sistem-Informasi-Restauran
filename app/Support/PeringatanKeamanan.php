<?php

namespace App\Support;

use App\Models\LogAktivitas;
use Illuminate\Support\Carbon;

/**
 * Ringkasan kejadian keamanan dari Log Aktivitas: login gagal beruntun, percobaan dari luar outlet,
 * penolakan wajah, dan lainnya. Ditampilkan di Log Aktivitas dan dikirim ke Owner lewat email harian.
 */
class PeringatanKeamanan
{
    /**
     * Kelompok kejadian yang dipantau.
     *
     * @var array<string, array{label: string, aksi: list<string>, saran: string}>
     */
    public const KELOMPOK = [
        'password' => ['label' => 'Password salah', 'aksi' => ['akun.login_gagal'],
            'saran' => 'Banyak percobaan pada satu akun atau dari satu IP bisa berarti ada yang menebak password.'],
        'dua_faktor' => ['label' => 'Kode verifikasi 2 langkah salah', 'aksi' => ['akun.2fa_gagal'],
            'saran' => 'Password akun ini sudah benar. Bila bukan pemiliknya, segera ganti password akun tersebut.'],
        'luar_outlet' => ['label' => 'Ditolak: luar outlet / di luar shift / kode darurat salah', 'aksi' => ['akun.ditolak', 'akun.keluar_area'],
            'saran' => 'Cek apakah staf memang sedang di luar outlet, atau ada yang memakai akun staf dari tempat lain.'],
        'wajah' => ['label' => 'Verifikasi wajah gagal / dugaan wajah palsu', 'aksi' => ['akun.wajah_gagal', 'akun.spoofing', 'wajah.ditolak'],
            'saran' => 'Dugaan wajah palsu (foto/video) perlu ditanyakan langsung ke staf terkait.'],
        'terkunci' => ['label' => 'Akun terkunci otomatis', 'aksi' => ['akun.terkunci'],
            'saran' => 'Pastikan itu memang staf yang lupa password sebelum membuka kunci.'],
        'diambil_alih' => ['label' => 'Akun dipakai masuk di perangkat lain', 'aksi' => ['akun.diambil_alih'],
            'saran' => 'Satu akun dipakai dua orang, atau password staf sudah diketahui orang lain.'],
        'kode_darurat' => ['label' => 'Masuk memakai kode darurat', 'aksi' => ['akun.kode_darurat'],
            'saran' => 'Wajar bila WiFi/GPS outlet bermasalah; cabut kode bila sudah tidak diperlukan.'],
        'pembayaran' => ['label' => 'Notifikasi pembayaran palsu', 'aksi' => ['pembayaran.palsu'],
            'saran' => 'Ada yang mencoba memalsukan notifikasi lunas. Pesanan tidak ikut berubah.'],
    ];

    /** Percobaan gagal dari satu IP atau ke satu akun yang dianggap beruntun. */
    public const AMBANG_BERUNTUN = 5;

    /**
     * @return array{kelompok: list<array{kunci: string, label: string, jumlah: int, aksi: list<string>, saran: string}>, beruntun: list<array{jenis: string, nilai: string, jumlah: int}>, total: int}
     */
    public static function ringkas(Carbon $sejak): array
    {
        $semuaAksi = array_merge(...array_column(self::KELOMPOK, 'aksi'));
        $hitung = LogAktivitas::where('created_at', '>=', $sejak)->whereIn('aksi', $semuaAksi)
            ->selectRaw('aksi, count(*) as jumlah')->groupBy('aksi')->pluck('jumlah', 'aksi');

        $kelompok = [];
        foreach (self::KELOMPOK as $kunci => $k) {
            $jumlah = (int) collect($k['aksi'])->sum(fn ($a) => $hitung[$a] ?? 0);
            if ($jumlah > 0) {
                $kelompok[] = ['kunci' => $kunci, 'jumlah' => $jumlah] + $k;
            }
        }

        // login gagal beruntun: dari satu IP atau ke satu akun
        $gagal = LogAktivitas::where('created_at', '>=', $sejak)->whereIn('aksi', ['akun.login_gagal', 'akun.2fa_gagal']);
        $perIp = (clone $gagal)->whereNotNull('ip')->selectRaw('ip as nilai, count(*) as jumlah')->groupBy('ip')
            ->havingRaw('count(*) >= ?', [self::AMBANG_BERUNTUN])->get()->map(fn ($r) => ['jenis' => 'IP', 'nilai' => (string) $r->nilai, 'jumlah' => (int) $r->jumlah]);
        $perAkun = (clone $gagal)->whereNotNull('nama_user')->selectRaw('nama_user as nilai, count(*) as jumlah')->groupBy('nama_user')
            ->havingRaw('count(*) >= ?', [self::AMBANG_BERUNTUN])->get()->map(fn ($r) => ['jenis' => 'Akun', 'nilai' => (string) $r->nilai, 'jumlah' => (int) $r->jumlah]);

        return [
            'kelompok' => $kelompok,
            'beruntun' => $perAkun->concat($perIp)->sortByDesc('jumlah')->values()->all(),
            'total' => (int) $hitung->sum(),
        ];
    }

    /**
     * Daftar aksi dari satu kelompok, untuk filter Log Aktivitas.
     *
     * @return list<string>
     */
    public static function aksiKelompok(?string $kunci): array
    {
        return self::KELOMPOK[$kunci]['aksi'] ?? [];
    }
}
