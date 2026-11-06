<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\TiketDapur;
use App\Models\User;
use App\Services\DapurService;
use App\Services\PesananService;
use App\Support\TimelinePesanan;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimelinePesananTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string> label => state
     */
    private function state($pesanan): array
    {
        return collect(TimelinePesanan::untuk($pesanan->fresh()))->pluck('state', 'label')->all();
    }

    public function test_langkah_dimasak_aktif_selama_dimasak_dan_dicentang_setelah_siap(): void
    {
        $this->seed(MasterDataSeeder::class);
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);
        $p = app(PesananService::class)->buat([['id_menu' => Menu::where('tersedia', true)->value('id'), 'jumlah' => 1]], 'take_away', kasir: $kasir);
        $tiket = $p->tiketDapur;

        $this->assertSame('now', $this->state($p)['Dimasak']); // antre: menunggu giliran

        app(DapurService::class)->mulaiMasak($tiket);
        $this->assertSame(TiketDapur::STATUS_DIMASAK, $tiket->fresh()->status_masak);
        $state = $this->state($p);
        $this->assertSame(['now', 'todo'], [$state['Dimasak'], $state['Siap Diambil']]);

        app(DapurService::class)->selesaiMasak($tiket->fresh());
        $state = $this->state($p);
        $this->assertSame(['done', 'done'], [$state['Dimasak'], $state['Siap Diambil']]);
    }
}
