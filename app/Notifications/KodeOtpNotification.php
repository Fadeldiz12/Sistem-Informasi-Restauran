<?php

namespace App\Notifications;

use App\Models\KodeOtp;
use App\Services\OtpService;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email berisi kode OTP (berbahasa Indonesia).
 */
class KodeOtpNotification extends Notification
{
    public function __construct(public string $kode, public string $tujuan, public string $nama = '') {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resto = config('resto.nama');
        $daftar = $this->tujuan === KodeOtp::TUJUAN_REGISTRASI;

        return (new MailMessage)
            ->subject(($daftar ? 'Kode Verifikasi Akun' : 'Kode Atur Ulang Password').' · '.$resto)
            ->greeting('Halo'.($this->nama ? ', '.$this->nama : '').'!')
            ->line($daftar
                ? "Terima kasih sudah mendaftar di {$resto}. Masukkan kode berikut untuk mengaktifkan akunmu:"
                : 'Kami menerima permintaan untuk mengatur ulang password akunmu. Masukkan kode berikut:')
            ->line('**'.implode(' ', str_split($this->kode)).'**')
            ->line('Kode berlaku '.OtpService::BERLAKU_MENIT.' menit. Jangan berikan kode ini kepada siapa pun, termasuk pihak yang mengaku dari '.$resto.'.')
            ->line($daftar ? 'Jika kamu tidak merasa mendaftar, abaikan email ini.' : 'Jika kamu tidak meminta reset password, abaikan email ini. Password kamu tidak berubah.')
            ->salutation('Salam, '.$resto);
    }
}
