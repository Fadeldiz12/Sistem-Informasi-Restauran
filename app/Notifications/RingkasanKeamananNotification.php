<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email harian ke Owner: kejadian keamanan 24 jam terakhir (hanya dikirim bila ada kejadian).
 */
class RingkasanKeamananNotification extends Notification
{
    /**
     * @param  array{kelompok: list<array{kunci: string, label: string, jumlah: int, aksi: list<string>, saran: string}>, beruntun: list<array{jenis: string, nilai: string, jumlah: int}>, total: int}  $ringkasan
     */
    public function __construct(public array $ringkasan) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resto = config('resto.nama');
        $pesan = (new MailMessage)
            ->subject("Ringkasan keamanan 24 jam: {$this->ringkasan['total']} kejadian · {$resto}")
            ->greeting('Halo, '.$notifiable->namaDepan().'!')
            ->line('Berikut kejadian keamanan di '.$resto.' selama 24 jam terakhir:');

        foreach ($this->ringkasan['kelompok'] as $k) {
            $pesan->line("- **{$k['label']}**: {$k['jumlah']} kali. {$k['saran']}");
        }
        if ($this->ringkasan['beruntun']) {
            $pesan->line('**Percobaan gagal beruntun** (perlu dicek):');
            foreach ($this->ringkasan['beruntun'] as $b) {
                $pesan->line("- {$b['jenis']} {$b['nilai']}: {$b['jumlah']} kali gagal");
            }
        }

        return $pesan->action('Buka Log Aktivitas', route('owner.aktivitas', ['kategori' => 'keamanan']))
            ->salutation('Salam, '.$resto);
    }
}
