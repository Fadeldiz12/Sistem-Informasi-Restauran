<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\RingkasanKeamananNotification;
use App\Support\PeringatanKeamanan;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

#[Signature('resto:ringkasan-keamanan')]
#[Description('Email ringkasan kejadian keamanan 24 jam terakhir ke semua Owner (hanya bila ada kejadian)')]
class KirimRingkasanKeamanan extends Command
{
    public function handle(): int
    {
        $ringkasan = PeringatanKeamanan::ringkas(now()->subDay());
        if ($ringkasan['total'] === 0) {
            $this->info('Tidak ada kejadian keamanan 24 jam terakhir. Email tidak dikirim.');

            return self::SUCCESS;
        }

        $owner = User::where('role', User::ROLE_OWNER)->whereNull('terkunci_at')->get();
        Notification::send($owner, new RingkasanKeamananNotification($ringkasan));
        $this->info("Ringkasan {$ringkasan['total']} kejadian dikirim ke {$owner->count()} Owner.");

        return self::SUCCESS;
    }
}
