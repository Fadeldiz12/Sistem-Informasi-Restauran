<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->owner = User::factory()->create(['role' => User::ROLE_OWNER, 'nama' => 'Pak Owner']);
    }

    private function isi(User $u, string $role): array
    {
        return ['nama' => $u->nama, 'email' => $u->email, 'no_hp' => '0812', 'role' => $role];
    }

    public function test_admin_mengatur_role_staf_tapi_tidak_owner(): void
    {
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);

        $this->actingAs($this->admin)->put("/admin/pengguna/{$kasir->id}", $this->isi($kasir, 'dapur'))->assertSessionHasNoErrors();
        $this->assertSame('dapur', $kasir->fresh()->role);

        // admin tidak bisa memberi role owner
        $this->actingAs($this->admin)->put("/admin/pengguna/{$kasir->id}", $this->isi($kasir, 'owner'))->assertSessionHasErrors('role');
        $this->actingAs($this->admin)->post('/admin/pengguna', ['nama' => 'X', 'email' => 'x@a.test', 'no_hp' => '08', 'role' => 'owner',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'x@a.test']);

        // admin tidak bisa mengubah atau menghapus akun owner
        $this->actingAs($this->admin)->put("/admin/pengguna/{$this->owner->id}", $this->isi($this->owner, 'kasir'))->assertForbidden();
        $this->actingAs($this->admin)->delete("/admin/pengguna/{$this->owner->id}")->assertForbidden();
        $this->assertSame('owner', $this->owner->fresh()->role);

        $this->actingAs($this->admin)->get('/admin/pengguna')->assertOk()
            ->assertSee('Akun owner hanya bisa diubah oleh owner')
            ->assertDontSee('data-value="owner"', false);
    }

    public function test_owner_mengatur_semua_role_termasuk_owner(): void
    {
        $admin2 = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($this->owner)->get('/owner/pengguna')->assertOk()->assertSee('Pengguna &amp; Role', false)->assertSee('data-value="owner"', false);

        $this->actingAs($this->owner)->from('/owner/pengguna?role=admin')->put("/owner/pengguna/{$admin2->id}", $this->isi($admin2, 'owner'))->assertSessionHasNoErrors()->assertRedirect('/owner/pengguna?role=admin');
        $this->assertSame('owner', $admin2->fresh()->role);

        $this->actingAs($this->owner)->put("/owner/pengguna/{$admin2->id}", $this->isi($admin2, 'admin'))->assertSessionHasNoErrors();
        $this->assertSame('admin', $admin2->fresh()->role);

        // tidak bisa menurunkan role sendiri → selalu ada minimal satu owner
        $this->actingAs($this->owner)->put("/owner/pengguna/{$this->owner->id}", $this->isi($this->owner, 'admin'))->assertSessionHasErrors('role');
        $this->assertSame('owner', $this->owner->fresh()->role);

        // role lain tidak bisa membuka halaman pengguna
        $this->actingAs(User::factory()->create(['role' => User::ROLE_KASIR]))->get('/owner/pengguna')->assertForbidden();
        $this->actingAs($this->admin)->get('/owner/pengguna')->assertForbidden();
    }

    public function test_pengguna_yang_rolenya_berubah_diarahkan_ke_halamannya(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_KASIR]);
        $this->actingAs($user)->get('/kasir/dashboard')->assertOk();

        $user->update(['role' => User::ROLE_KURIR]);
        $this->actingAs($user->fresh())->get('/kasir/dashboard')->assertForbidden()
            ->assertSee('Kurir')->assertSee(route('kurir.tugas'), false);
    }
}
