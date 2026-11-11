<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email ke Owner: sebuah akun terkunci karena password salah berulang kali.
 */
class AkunTerkunciNotification extends Notification
{
    public function __construct(public User $akun, public ?string $ip, public string $sebab = '') {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resto = config('resto.nama');

        return (new MailMessage)
            ->subject('Peringatan keamanan: akun '.$this->akun->nama.' terkunci · '.$resto)
            ->greeting('Halo, '.$notifiable->namaDepan().'!')
            ->line("Akun **{$this->akun->nama}** ({$this->akun->roleLabel()}, {$this->akun->email}) terkunci otomatis setelah "
                .($this->sebab ?: User::MAKS_GAGAL_LOGIN.' kali password salah').($this->ip ? " dari alamat IP {$this->ip}" : '').'.')
            ->line('Jika itu memang staf yang lupa password, buka kuncinya di menu Pengguna & Role. Jika bukan, kemungkinan ada orang lain yang mencoba masuk ke akun tersebut: periksa Log Aktivitas.')
            ->action('Buka Pengguna & Role', route('owner.pengguna', ['q' => $this->akun->email]))
            ->salutation('Salam, '.$resto);
    }
}
