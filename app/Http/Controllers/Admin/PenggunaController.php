<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengiriman;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PenggunaController extends Controller
{
    public function index(Request $request): View
    {
        $role = in_array($request->query('role'), User::ROLES, true) ? $request->query('role') : null;
        $q = trim((string) $request->query('q'));

        $users = User::query()
            ->when($role, fn ($query) => $query->where('role', $role))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('nama', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('no_hp', 'like', "%{$q}%")))
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        $perRole = User::query()->selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role');

        return view('admin.pengguna', [
            'rute' => $this->rute($request),
            'roleBoleh' => $request->user()->roleYangBolehDiatur(),
            'users' => $users,
            'role' => $role,
            'q' => $q,
            'perRole' => $perRole,
            'total' => $perRole->sum(),
            'pelangganBaru' => User::where('role', User::ROLE_PELANGGAN)->where('created_at', '>=', now()->startOfMonth())->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        User::create($data)->forceFill(['email_verified_at' => now()])->save(); // dibuat admin/owner: langsung aktif

        return to_route($this->rute($request))->with('status', 'Pengguna berhasil ditambahkan.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->pastikanBolehKelola($request, $user);
        $data = $this->validated($request, $user);

        if ($user->is($request->user()) && $data['role'] !== $user->role) {
            return back()->withInput()->withErrors(['role' => 'Anda tidak bisa mengubah peran akun sendiri.']);
        }
        if ($data['role'] !== $user->role && $this->masihMengantar($user)) {
            return back()->withInput()->withErrors(['role' => "{$user->nama} masih mengantar pesanan. Ubah role setelah pengiriman selesai."]);
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return back()->with('status', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->pastikanBolehKelola($request, $user);

        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak bisa menghapus akun yang sedang dipakai login.');
        }

        if ($this->masihMengantar($user)) {
            return back()->with('error', "{$user->nama} masih mengantar pesanan. Hapus akun setelah pengiriman selesai.");
        }

        if ($user->pengeluaran()->exists()) {
            return back()->with('error', "Akun {$user->nama} tidak bisa dihapus karena masih tercatat di data pengeluaran.");
        }

        $user->delete();

        return back()->with('status', 'Pengguna berhasil dihapus.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user)],
            'no_hp' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'role' => ['required', Rule::in($request->user()->roleYangBolehDiatur())],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ], [
            'no_hp.regex' => 'Nomor HP hanya boleh berisi angka.',
            'role.in' => 'Anda tidak berhak memberikan role tersebut. Role owner hanya bisa diberikan oleh owner.',
        ]);
    }

    /**
     * Route halaman ini: admin.pengguna atau owner.pengguna.
     */
    private function rute(Request $request): string
    {
        return $request->user()->role.'.pengguna';
    }

    private function pastikanBolehKelola(Request $request, User $user): void
    {
        abort_unless($request->user()->bolehKelola($user), 403, 'Akun owner hanya bisa diubah oleh owner.');
    }

    private function masihMengantar(User $user): bool
    {
        return $user->role === User::ROLE_KURIR
            && $user->tugasPengiriman()->where('status_kirim', Pengiriman::STATUS_DALAM_PERJALANAN)->exists();
    }
}
