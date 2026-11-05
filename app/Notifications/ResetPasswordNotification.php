<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Email reset password berbahasa Indonesia.
 */
class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $menit = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Atur Ulang Password · '.config('resto.nama'))
            ->greeting('Halo, '.$notifiable->nama.'!')
            ->line('Kami menerima permintaan untuk mengatur ulang password akun '.config('resto.nama').' kamu.')
            ->action('Atur Ulang Password', $this->resetUrl($notifiable))
            ->line("Tautan ini berlaku selama {$menit} menit.")
            ->line('Jika kamu tidak meminta reset password, abaikan email ini. Password kamu tidak akan berubah.')
            ->salutation('Salam, '.config('resto.nama'));
    }
}
