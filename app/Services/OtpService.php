<?php

namespace App\Services;

use App\Models\KodeOtp;
use App\Models\User;
use App\Notifications\KodeOtpNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Kode OTP lewat email: 6 digit, berlaku 10 menit, maksimal 5 kali salah,
 * kirim ulang paling cepat 60 detik sekali.
 */
class OtpService
{
    public const BERLAKU_MENIT = 10;

    public const JEDA_DETIK = 60;

    public const MAKS_PERCOBAAN = 5;

    /**
     * Buat dan kirim kode baru. Kode lama untuk email & tujuan yang sama tidak berlaku lagi.
     * Untuk email yang tidak terdaftar tidak ada email yang dikirim (tanpa memberi tahu pengguna).
     */
    public function kirim(string $email, string $tujuan): void
    {
        $email = Str::lower($email);
        $sisa = $this->sisaJeda($email, $tujuan);
        if ($sisa > 0) {
            throw ValidationException::withMessages(['kode' => "Tunggu {$sisa} detik sebelum meminta kode baru."]);
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            return;
        }

        $kode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        KodeOtp::where('email', $email)->where('tujuan', $tujuan)->delete();
        KodeOtp::create([
            'email' => $email,
            'tujuan' => $tujuan,
            'kode_hash' => Hash::make($kode),
            'kedaluwarsa_pada' => now()->addMinutes(self::BERLAKU_MENIT),
        ]);

        Notification::route('mail', $email)->notify(new KodeOtpNotification($kode, $tujuan, $user->namaDepan()));

        // Pengembangan lokal tanpa server email: tampilkan kode di halaman OTP.
        if (app()->isLocal() && config('mail.default') === 'log') {
            session()->flash('otp_dev', $kode);
        }
    }

    /**
     * Detik tersisa sebelum boleh kirim ulang.
     */
    public function sisaJeda(string $email, string $tujuan): int
    {
        $terakhir = KodeOtp::where('email', Str::lower($email))->where('tujuan', $tujuan)->latest('id')->value('created_at');

        return $terakhir ? max(0, self::JEDA_DETIK - (int) $terakhir->diffInSeconds(now())) : 0;
    }

    /**
     * Cocokkan kode. Gagal → ValidationException pada field "kode". Berhasil → kode dihapus (sekali pakai).
     */
    public function cocokkan(string $email, string $tujuan, string $kode): void
    {
        $otp = KodeOtp::where('email', Str::lower($email))->where('tujuan', $tujuan)->latest('id')->first();

        if (! $otp || $otp->kedaluwarsa_pada->isPast()) {
            throw ValidationException::withMessages(['kode' => 'Kode sudah kedaluwarsa atau tidak ditemukan. Tekan "Kirim ulang kode".']);
        }
        if ($otp->percobaan >= self::MAKS_PERCOBAAN) {
            throw ValidationException::withMessages(['kode' => 'Terlalu banyak percobaan salah. Minta kode baru.']);
        }
        if (! Hash::check($kode, $otp->kode_hash)) {
            $otp->increment('percobaan');
            $sisa = self::MAKS_PERCOBAAN - $otp->percobaan;

            throw ValidationException::withMessages(['kode' => $sisa > 0
                ? "Kode salah. Sisa {$sisa} kali percobaan."
                : 'Kode salah dan percobaan sudah habis. Minta kode baru.']);
        }

        $otp->delete();
    }

    /**
     * Contoh: rina.amalia@gmail.com → ri********@gmail.com
     */
    public static function samarkan(string $email): string
    {
        [$nama, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($nama, 0, 2).str_repeat('•', max(3, mb_strlen($nama) - 2)).'@'.$domain;
    }
}
